<?php

namespace App\Support\Facades;

use App\Services\ThemeService;
use Illuminate\Support\Facades\Facade;

/**
 * @method static string primary()
 * @method static string secondary()
 * @method static string accent()
 * @method static string background()
 * @method static string text()
 * @method static string mode()
 * @method static array colors()
 * @method static array cssVariables()
 * @method static string cssBlock()
 * @method static array publicConfig()
 *
 * @see ThemeService
 */
class Theme extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return ThemeService::class;
    }
}
