<?php

declare(strict_types=1);

namespace EllenHarvey\Providers\Credit;

use IX\Models\Post;

/**
 * Credit post model.
 *
 * One résumé line: the production is the post title, the role and venue
 * live in ACF fields, and an optional note carries a parenthetical such as
 * a director credit or an award. The section it belongs to is the
 * `credit_category` taxonomy term.
 */
class CreditPost extends Post
{
    public const POST_TYPE = 'credit';

    public function role(): string
    {
        return (string) $this->meta('role');
    }

    public function venue(): string
    {
        return (string) $this->meta('venue');
    }

    public function note(): string
    {
        return (string) $this->meta('note');
    }
}
