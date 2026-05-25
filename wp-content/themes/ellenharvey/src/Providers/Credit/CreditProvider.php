<?php

declare(strict_types=1);

namespace EllenHarvey\Providers\Credit;

use IX\Providers\Provider;

/**
 * Credit provider.
 *
 * Registers the `credit` CPT (a single résumé line — a production with a
 * role and venue) and the `credit_category` taxonomy that groups them into
 * the résumé's sections (Broadway, Off-Broadway, Film, Television, …).
 *
 * The Résumé archive lives at /resume/ (archive-credit.php →
 * archive-credit.twig), rendering each category as a heading followed by a
 * three-column table of credits, mirroring the original resume.htm. The
 * static header credentials and the training/skills/awards footer come from
 * an ACF options page (acf-json/options-page-resume.json) so Ellen can edit
 * them without touching code.
 */
class CreditProvider extends Provider
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
     * The `credit_category` taxonomy — the résumé's sections, in the order
     * they were seeded (the archive orders by term id). Non-hierarchical:
     * these are flat section labels, not a tree.
     */
    public function registerTaxonomy(): void
    {
        register_taxonomy('credit_category', ['credit'], [
            'labels' => [
                'name'          => __('Résumé Sections', 'ellenharvey'),
                'singular_name' => __('Résumé Section', 'ellenharvey'),
                'add_new_item'  => __('Add New Section', 'ellenharvey'),
                'edit_item'     => __('Edit Section', 'ellenharvey'),
                'search_items'  => __('Search Sections', 'ellenharvey'),
                'all_items'     => __('All Sections', 'ellenharvey'),
                'menu_name'     => __('Sections', 'ellenharvey'),
            ],
            'public'            => true,
            'hierarchical'      => false,
            'show_ui'           => true,
            'show_admin_column' => true,
            'show_in_nav_menus' => false,
            'show_in_rest'      => true,
            'rewrite'           => false,
        ]);
    }
}
