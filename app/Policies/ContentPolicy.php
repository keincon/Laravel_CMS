<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Content;
use App\Models\User;

class ContentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('edit_posts')
            || $user->can('edit_pages')
            || $user->can('manage_posts')
            || $user->can('manage_pages');
    }

    public function view(User $user, Content $content): bool
    {
        return $this->viewAny($user)
            || ($content->author_id === $user->id && $user->can('edit_posts'));
    }

    public function create(User $user): bool
    {
        return $user->can('create_posts')
            || $user->can('create_pages')
            || $user->can('manage_posts')
            || $user->can('manage_pages');
    }

    public function update(User $user, Content $content): bool
    {
        if ($user->can('edit_others_posts') || $user->can('edit_others_pages') || $user->can('manage_posts') || $user->can('manage_pages')) {
            return true;
        }

        return $content->author_id === $user->id
            && ($user->can('edit_posts') || $user->can('edit_pages') || $user->can('manage_posts'));
    }

    public function delete(User $user, Content $content): bool
    {
        if ($user->can('delete_posts') || $user->can('delete_pages') || $user->can('manage_posts') || $user->can('manage_pages')) {
            return true;
        }

        return $content->author_id === $user->id && $user->can('edit_posts');
    }

    public function publish(User $user, Content $content): bool
    {
        return $user->can('publish_posts')
            || $user->can('publish_pages')
            || $user->can('manage_posts')
            || $user->can('manage_pages');
    }
}
