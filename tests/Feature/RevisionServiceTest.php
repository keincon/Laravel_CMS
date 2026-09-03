<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\ContentType;
use App\Models\User;
use App\Services\Content\ContentService;
use App\Services\Content\RevisionService;
use App\Services\LaravelPressBootstrapService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RevisionServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        app(LaravelPressBootstrapService::class)->seedBuiltins();
    }

    #[Test]
    public function it_snapshots_and_restores_content_transactionally(): void
    {
        $user = User::factory()->create(['username' => 'revuser']);
        $service = app(ContentService::class);
        $revisions = app(RevisionService::class);

        $content = $service->create('post', [
            'title' => 'Original',
            'body' => 'v1',
            'status' => 'draft',
        ], $user);

        $snapshot = $revisions->snapshot($content, $user, 'manual');
        $this->assertSame(1, $snapshot->revision_number);

        $service->update($content, ['title' => 'Changed', 'body' => 'v2']);
        $content->refresh();
        $this->assertSame('Changed', $content->title);

        $revisions->restore($content, $snapshot);
        $content->refresh();

        $this->assertSame('Original', $content->title);
        $this->assertSame('v1', $content->body);
        $this->assertGreaterThan(1, $content->revisions()->count());
    }
}
