<?php

declare(strict_types=1);

namespace EllenHarvey\Providers\Theme;

use DI\Container;
use EllenHarvey\Providers\Credit\CreditPost;
use EllenHarvey\Providers\Gallery\GalleryPost;
use EllenHarvey\Providers\Review\ReviewPost;
use IX\Providers\Theme\ThemeProvider as BaseThemeProvider;
use IX\Services\IconServiceFactory;

/**
 * Theme-level provider.
 *
 * Extends IX's ThemeProvider. Add features/hooks specific to this site here.
 * See vincentragosta.io for examples of features (ScrollReveal, WpForms*) and
 * hooks (ContainerBlockStyles, CoverBlockStyles, SocialIconChoices, etc.).
 */
class ThemeProvider extends BaseThemeProvider
{
    /**
     * Features to register (toggleable; opt out of parent features via ClassName::class => false).
     *
     * @var array<class-string|string, mixed>
     */
    protected array $features = [];

    /**
     * Hooks to register (always-active; additive only).
     *
     * @var array<class-string>
     */
    protected array $hooks = [];

    public function __construct(
        Container $container,
        IconServiceFactory $iconFactory,
    ) {
        parent::__construct($container, $iconFactory);
    }

    public function register(): void
    {
        add_action('wp_enqueue_scripts', [$this, 'enqueueAssets']);
        add_action('after_setup_theme', [$this, 'registerMenus']);
        add_filter('body_class', [$this, 'addPageSlugBodyClass']);

        parent::register();
    }

    /**
     * Map this site's CPTs to their Timber models.
     *
     * Adds the Ellen Harvey post types on top of IX's core mappings so
     * templates get typed accessors (e.g. a gallery's resolved poster /
     * photo images) instead of raw meta.
     *
     * @param array<string, class-string> $classMap
     * @return array<string, class-string|callable>
     */
    public function registerClassMap(array $classMap): array
    {
        $classMap = parent::registerClassMap($classMap);

        $classMap[ReviewPost::POST_TYPE]  = ReviewPost::class;
        $classMap[CreditPost::POST_TYPE]  = CreditPost::class;
        $classMap[GalleryPost::POST_TYPE] = GalleryPost::class;

        return $classMap;
    }

    public function enqueueAssets(): void
    {
        $this->enqueueStyle('ellenharvey-theme', 'theme.css');
        $this->enqueueScript('ellenharvey-theme', 'index.js');
    }

    /**
     * Register the primary navigation menu location consumed by header.twig.
     * IX adds `add_theme_support('menus')` but registers no locations.
     */
    public function registerMenus(): void
    {
        register_nav_menus([
            'primary' => __('Primary Navigation', 'ellenharvey'),
        ]);
    }

    /**
     * Add a `page-{slug}` body class for static pages.
     *
     * The per-page photographic background system maps backgrounds by
     * route in SCSS. WP already emits `.home` for the front page and
     * `.post-type-archive-{cpt}` for CPT archives, but for ordinary
     * pages it only emits `.page-id-{id}` (environment-specific). Adding
     * a slug-based class lets the SCSS map backgrounds stably across
     * local / staging / production.
     *
     * @param string[] $classes
     * @return string[]
     */
    public function addPageSlugBodyClass(array $classes): array
    {
        if (is_page()) {
            $post = get_queried_object();
            if ($post instanceof \WP_Post && $post->post_name !== '') {
                $classes[] = 'page-' . $post->post_name;
            }
        }

        return $classes;
    }
}
