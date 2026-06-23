<?php

declare(strict_types=1);

namespace EllenHarvey\Providers\Theme;

use DI\Container;
use EllenHarvey\Providers\Gallery\GalleryPost;
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

    /**
     * Child blocks (merged additively with IX's parent blocks).
     *
     * @var array<string>
     */
    protected array $blocks = [
        'photo-gallery',
    ];

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

        // Hide the front-end admin bar for everyone.
        add_filter('show_admin_bar', '__return_false');

        // Re-enable core blocks IX disables by default (DisableBlocks feature):
        // - core/pullquote powers the homepage press-quote slider.
        // - core/social-links powers the News page's follow row.
        // - core/quote powers the Reviews page press quotes.
        add_filter('theme/disabled_block_types', static function (array $blocks): array {
            return array_values(array_diff($blocks, [
                'core/pullquote',
                'core/social-links',
                'core/social-link',
                'core/quote',
            ]));
        });

        // core/columns ships without dimensions support, so it has no
        // Min-height control. Add it so each hero's full-viewport column height
        // is a block setting (visible/editable in the editor) instead of a
        // hard-coded theme CSS rule.
        add_filter('register_block_type_args', static function (array $args, string $name): array {
            if ($name === 'core/columns') {
                $args['supports']['dimensions'] = array_merge(
                    $args['supports']['dimensions'] ?? [],
                    ['minHeight' => true],
                );
            }

            return $args;
        }, 10, 2);

        add_action('init', [$this, 'registerBlockStyles']);

        parent::register();

        // Load/save this provider's ACF JSON (the Page Layout field group).
        $this->acfManager->registerSavePath();
    }

    /**
     * Register block style variants for the résumé / reviews (reusable anywhere).
     *
     * Selectable from each block's Styles panel; styled as `.is-style-{name}`
     * in _block-styles.scss (theme.css + the editor stylesheet). Each variant is
     * a complete preset including its font size.
     *  - core/heading "Section"    — gold uppercase section titles.
     *  - core/heading "Page Title" — the page <h1>: xx-large with a dotted rule.
     *  - core/paragraph "Lead"    — the gold credential / lead line.
     *  - core/table "Plain"       — borderless rows for clean lists.
     *  - core/quote "Press quote" — a press pull-quote (straight-quoted text +
     *    italic, dash-prefixed source; no default rule).
     */
    public function registerBlockStyles(): void
    {
        register_block_style('core/heading', ['name' => 'section', 'label' => __('Section', 'ellenharvey')]);
        register_block_style('core/heading', ['name' => 'page-title', 'label' => __('Page Title', 'ellenharvey')]);
        register_block_style('core/paragraph', ['name' => 'lead', 'label' => __('Lead', 'ellenharvey')]);
        register_block_style('core/table', ['name' => 'plain', 'label' => __('Plain', 'ellenharvey')]);
        register_block_style('core/quote', ['name' => 'press', 'label' => __('Press quote', 'ellenharvey')]);
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

        $classMap[GalleryPost::POST_TYPE] = GalleryPost::class;

        return $classMap;
    }

    public function enqueueAssets(): void
    {
        $this->enqueueStyle('ellenharvey-theme', 'theme.css');
        $this->enqueueScript('ellenharvey-theme', 'index.js');
    }

    /**
     * Enqueue block-editor scripts.
     *
     * Keeps IX's parent-block editor scripts, then adds this theme's own
     * block(s). The child build emits each block's editor bundle to
     * dist/js/{block}.js; it's enqueued straight from the child dist here so
     * Gutenberg registers the block (without it, the editor shows "block not
     * supported"). Mirrors how IX enqueues its own blocks from the parent dist.
     */
    public function enqueueEditorAssets(): void
    {
        parent::enqueueEditorAssets();

        $editorScript = get_stylesheet_directory() . '/dist/js/photo-gallery.js';
        if (file_exists($editorScript)) {
            wp_enqueue_script(
                'ellenharvey-photo-gallery-block-editor',
                get_stylesheet_directory_uri() . '/dist/js/photo-gallery.js',
                ['wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-i18n', 'wp-server-side-render'],
                filemtime($editorScript),
                true
            );
        }
    }

    /**
     * Add the child theme's block-editor styles on top of IX's.
     *
     * Loads the homepage hero styles into the editor canvas so editing the
     * quote slider matches the front end (translucent card, Marcellus
     * pull-quotes, no core/pullquote borders). See assets/scss/features/editor.scss.
     */
    public function addThemeSupports(): void
    {
        parent::addThemeSupports();
        add_editor_style('dist/css/features/editor.css');
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
     * Also emits `has-overlay-header` when the page's "Overlay header"
     * toggle (Page Layout field group, Theme acf-json) is on. The header
     * is absolutely positioned over the page's first section ONLY under
     * this class — the default is normal document flow. See
     * layout/_frame.scss.
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

            if ($post instanceof \WP_Post && function_exists('get_field') && get_field('overlay_header', $post->ID)) {
                $classes[] = 'has-overlay-header';
            }
        }

        return $classes;
    }
}
