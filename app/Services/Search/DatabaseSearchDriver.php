<?php

declare(strict_types=1);

namespace App\Services\Search;

use App\Models\Content;
use App\Models\Media;
use App\Models\Term;
use App\Models\User;
use Illuminate\Support\Collection;

final class DatabaseSearchDriver implements SearchDriver
{
    public function search(string $query, array $types = [], int $limit = 50): Collection
    {
        $q = trim($query);
        if ($q === '') {
            return collect();
        }

        $like = '%'.$q.'%';
        $types = $types === [] ? ['content', 'media', 'user', 'term'] : $types;
        $results = collect();

        if (in_array('content', $types, true)) {
            Content::query()
                ->published()
                ->with('type')
                ->where(function ($builder) use ($q, $like) {
                    if ($this->supportsPostgresFullText()) {
                        $builder->whereRaw(
                            "to_tsvector('english', coalesce(title,'') || ' ' || coalesce(excerpt,'') || ' ' || coalesce(body,'')) @@ plainto_tsquery('english', ?)",
                            [$q]
                        );
                    } else {
                        $builder->where('title', 'like', $like)
                            ->orWhere('excerpt', 'like', $like)
                            ->orWhere('body', 'like', $like);
                    }
                })
                ->limit($limit)
                ->get()
                ->each(function (Content $content) use ($results) {
                    $results->push([
                        'type' => 'content:'.($content->type?->slug ?? 'content'),
                        'id' => $content->id,
                        'title' => $content->title,
                        'url' => '/'.$content->slug,
                        'excerpt' => $content->excerpt,
                    ]);
                });
        }

        if (in_array('media', $types, true)) {
            Media::query()
                ->where(function ($builder) use ($like) {
                    $builder->where('filename', 'like', $like)
                        ->orWhere('alt', 'like', $like);
                })
                ->limit($limit)
                ->get()
                ->each(function (Media $media) use ($results) {
                    $results->push([
                        'type' => 'media',
                        'id' => $media->id,
                        'title' => $media->filename,
                        'url' => $media->url(),
                        'excerpt' => $media->alt,
                    ]);
                });
        }

        if (in_array('user', $types, true)) {
            User::query()
                ->where(function ($builder) use ($like) {
                    $builder->where('username', 'like', $like)
                        ->orWhere('name', 'like', $like)
                        ->orWhere('display_name', 'like', $like)
                        ->orWhere('email', 'like', $like);
                })
                ->limit($limit)
                ->get()
                ->each(function (User $user) use ($results) {
                    $results->push([
                        'type' => 'user',
                        'id' => $user->id,
                        'title' => $user->publicName(),
                        'url' => '/author/'.$user->username,
                        'excerpt' => $user->bio,
                    ]);
                });
        }

        if (in_array('term', $types, true)) {
            Term::query()
                ->with('taxonomy')
                ->where(function ($builder) use ($like) {
                    $builder->where('name', 'like', $like)
                        ->orWhere('slug', 'like', $like)
                        ->orWhere('description', 'like', $like);
                })
                ->limit($limit)
                ->get()
                ->each(function (Term $term) use ($results) {
                    $tax = $term->taxonomy?->rest_base ?: $term->taxonomy?->slug ?: 'term';
                    $results->push([
                        'type' => 'term:'.$tax,
                        'id' => $term->id,
                        'title' => $term->name,
                        'url' => '/'.$tax.'/'.$term->slug,
                        'excerpt' => $term->description,
                    ]);
                });
        }

        return $results->take($limit)->values();
    }

    private function supportsPostgresFullText(): bool
    {
        return config('database.default') === 'pgsql'
            || config('database.connections.'.config('database.default').'.driver') === 'pgsql';
    }
}
