<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\User;
use App\Services\Auth\TwoFactorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TwoFactorServiceTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_enables_confirms_and_verifies_totp(): void
    {
        $user = User::factory()->create(['username' => 'tfauser', 'email' => 'tfa@example.com']);
        $service = app(TwoFactorService::class);

        $setup = $service->enable($user);
        $this->assertNotEmpty($setup['secret']);
        $this->assertStringContainsString('otpauth://totp/', $setup['otpauth_url']);

        $code = (new \ReflectionClass($service))->getMethod('totp');
        $code->setAccessible(true);
        $totp = $code->invoke($service, $setup['secret'], (int) floor(time() / 30));

        $this->assertTrue($service->confirm($user->fresh(), $totp));
        $this->assertTrue($user->fresh()->hasTwoFactorEnabled());

        $totp2 = $code->invoke($service, $setup['secret'], (int) floor(time() / 30));
        $this->assertTrue($service->verify($user->fresh(), $totp2));

        $service->disable($user->fresh());
        $this->assertFalse($user->fresh()->hasTwoFactorEnabled());
    }

    #[Test]
    public function login_requires_two_factor_challenge_when_enabled(): void
    {
        app(\App\Services\InstallationService::class)->markInstalled('chal@example.com');

        $user = User::factory()->create([
            'username' => 'chaluser',
            'email' => 'chal@example.com',
            'password' => bcrypt('secret-pass'),
        ]);
        $service = app(TwoFactorService::class);
        $setup = $service->enable($user);
        $codeMethod = (new \ReflectionClass($service))->getMethod('totp');
        $codeMethod->setAccessible(true);
        $totp = $codeMethod->invoke($service, $setup['secret'], (int) floor(time() / 30));
        $this->assertTrue($service->confirm($user->fresh(), $totp));

        $this->post('/login', [
            'email' => 'chal@example.com',
            'password' => 'secret-pass',
        ])->assertRedirect(route('login.two-factor'));

        $this->assertGuest();

        $totp2 = $codeMethod->invoke($service, $setup['secret'], (int) floor(time() / 30));
        $this->post('/login/two-factor', ['code' => $totp2])
            ->assertRedirect(route('admin.dashboard'));

        $this->assertAuthenticatedAs($user->fresh());
    }
}
