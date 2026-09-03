<?php

namespace Tests\Feature;

use App\Services\InstallationService;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class SetupWizardTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $file = config('cms.installed_file');
        if (File::exists($file)) {
            File::delete($file);
        }
    }

    public function test_root_redirects_to_setup_when_not_installed(): void
    {
        $this->mock(InstallationService::class, function ($mock) {
            $mock->shouldReceive('isInstalled')->andReturn(false);
        });

        $response = $this->get('/');

        $response->assertRedirect(route('setup.welcome'));
    }

    public function test_welcome_page_is_accessible_when_not_installed(): void
    {
        $this->mock(InstallationService::class, function ($mock) {
            $mock->shouldReceive('isInstalled')->andReturn(false);
        });

        $response = $this->get(route('setup.welcome'));

        $response->assertOk();
        $response->assertSee('Welcome to');
        $response->assertSee('Start Installation');
    }

    public function test_setup_returns_404_when_already_installed(): void
    {
        $this->mock(InstallationService::class, function ($mock) {
            $mock->shouldReceive('isInstalled')->andReturn(true);
        });

        $response = $this->get(route('setup.welcome'));

        $response->assertNotFound();
    }

    public function test_requirements_page_lists_checks(): void
    {
        $this->mock(InstallationService::class, function ($mock) {
            $mock->shouldReceive('isInstalled')->andReturn(false);
        });

        $response = $this->get(route('setup.requirements'));

        $response->assertOk();
        $response->assertSee('System Requirements');
        $response->assertSee('PHP');
        $response->assertSee('PostgreSQL');
        $response->assertSee('MySQL');
    }
}
