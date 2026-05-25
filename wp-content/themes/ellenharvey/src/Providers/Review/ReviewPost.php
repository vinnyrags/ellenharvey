<?php

declare(strict_types=1);

namespace EllenHarvey\Providers\Review;

use IX\Models\Post;

/**
 * Review post model.
 *
 * A single press pull-quote. The quote body + attribution live in ACF
 * fields; the production it belongs to is the `production` taxonomy term
 * (used to group the Reviews archive and to gather homepage highlights).
 */
class ReviewPost extends Post
{
    public const POST_TYPE = 'review';

    public function quote(): string
    {
        return (string) $this->meta('quote');
    }

    public function source(): string
    {
        return (string) $this->meta('source');
    }

    public function isFeatured(): bool
    {
        return (bool) $this->meta('featured');
    }
}
