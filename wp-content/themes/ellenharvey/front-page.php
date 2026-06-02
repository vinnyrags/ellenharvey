<?php
/**
 * Front page.
 *
 * The homepage crossfades the original site's pre-rendered quote slides over
 * the hero (see front-page.twig) — no extra context beyond the standard post
 * is needed. The `review` CPT still powers the /reviews/ archive.
 *
 * @package EllenHarvey
 */

use Timber\Timber;

$context         = Timber::context();
$context['post'] = Timber::get_post();

Timber::render(['front-page.twig', 'page.twig'], $context);
