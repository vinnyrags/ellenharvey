<?php

declare(strict_types=1);

namespace EllenHarvey\Providers\Review;

use IX\Providers\Provider;

/**
 * Review provider.
 *
 * Registers the `review` CPT (press pull-quotes) and the shared
 * `production` taxonomy used to group them by show. The Reviews archive
 * (archive-review.php → archive-review.twig) renders them grouped by
 * production, mirroring the original reviews.htm; featured reviews also
 * feed the home page highlights (see front-page.php).
 */
class ReviewProvider extends Provider
{
    public function register(): void
    {
        add_action('init', [$this, 'registerPostType']);
        add_action('init', [$this, 'registerTaxonomy']);

        parent::register();

        $this->acfManager->registerSavePath();
    }

    public function registerPostType(): void
    {
        $this->registerPostTypeFromConfig('post-type.json');
    }

    /**
     * The `production` taxonomy (a show / production) — the grouping axis
     * for the Reviews archive. Registered against `review` now; the Gallery
     * provider attaches itself to the same taxonomy when it loads so a show
     * can own both reviews and a photo gallery.
     */
    public function registerTaxonomy(): void
    {
        register_taxonomy('production', ['review'], [
            'labels' => [
                'name'          => __('Productions', 'ellenharvey'),
                'singular_name' => __('Production', 'ellenharvey'),
                'add_new_item'  => __('Add New Production', 'ellenharvey'),
                'edit_item'     => __('Edit Production', 'ellenharvey'),
                'search_items'  => __('Search Productions', 'ellenharvey'),
                'all_items'     => __('All Productions', 'ellenharvey'),
                'menu_name'     => __('Productions', 'ellenharvey'),
            ],
            'public'            => true,
            'hierarchical'      => false,
            'show_ui'           => true,
            'show_admin_column' => true,
            'show_in_nav_menus' => false,
            'show_in_rest'      => true,
            'rewrite'           => ['slug' => 'production', 'with_front' => false],
        ]);
    }
}
