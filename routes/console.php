<?php

use App\Enums\ContentStatus;
use App\Jobs\PublishScheduledContent;
use App\Models\Content;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::call(function () {
    if (! \Illuminate\Support\Facades\Schema::hasTable('contents')) {
        return;
    }

    Content::query()
        ->where('status', ContentStatus::Scheduled)
        ->whereNotNull('scheduled_at')
        ->where('scheduled_at', '<=', now())
        ->orderBy('id')
        ->limit(100)
        ->pluck('id')
        ->each(fn (int $id) => PublishScheduledContent::dispatch($id));
})->everyMinute()->name('publish-scheduled-content')->withoutOverlapping();
