<?php

namespace Tests\Feature;

use App\Services\InstallationService;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    public function test_root_redirects_to_setup_when_not_installed(): void
    {
        $this->mock(InstallationService::class, function ($mock) {
            $mock->shouldReceive('isInstalled')->andReturn(false);
        });

        $this->get('/')->assertRedirect(route('setup.welcome'));
    }
}
