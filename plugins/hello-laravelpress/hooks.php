<?php

declare(strict_types=1);

use App\Support\Hooks\Hooks;

Hooks::addFilter('content.rendered', static function (string $html): string {
    return $html."\n<!-- hello-laravelpress -->";
}, 50);
