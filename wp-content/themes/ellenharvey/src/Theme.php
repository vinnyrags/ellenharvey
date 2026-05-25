<?php

declare(strict_types=1);

namespace EllenHarvey;

use EllenHarvey\Providers\Credit\CreditProvider;
use EllenHarvey\Providers\Review\ReviewProvider;
use EllenHarvey\Providers\Theme\ThemeProvider;
use IX\Theme as BaseTheme;

/**
 * Main theme class.
 *
 * Bootstraps the theme by registering service providers.
 * Extends IX's base Theme class.
 */
class Theme extends BaseTheme
{
    /**
     * @var array<class-string>
     */
    protected array $providers = [
        ThemeProvider::class,
        CreditProvider::class,
        ReviewProvider::class,
    ];
}
