<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Content;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ContentPublished
{
    use Dispatchable, SerializesModels;

    public function __construct(public Content $content) {}
}
