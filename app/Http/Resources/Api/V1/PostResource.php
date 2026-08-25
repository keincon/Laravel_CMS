<?php

namespace App\Http\Resources\Api\V1;

use App\Services\PermalinkService;
use App\Services\SeoService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Post */
class PostResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $seo = app(SeoService::class)->resolve($this->resource);

        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'excerpt' => $this->excerpt,
            'content' => $this->when($request->routeIs('*.show') || $request->routeIs('api.v1.posts.show'), $this->content),
            'status' => $this->when($request->user(), $this->status),
            'published_at' => optional($this->published_at)?->toIso8601String(),
            'author' => $this->whenLoaded('author', fn () => [
                'id' => $this->author?->id,
                'name' => $this->author?->name,
                'username' => $this->author?->username,
            ]),
            'categories' => $this->whenLoaded('categories', fn () => $this->categories->map(fn ($c) => [
                'id' => $c->id,
                'name' => $c->name,
                'slug' => $c->slug,
            ])),
            'tags' => $this->whenLoaded('tags', fn () => $this->tags->map(fn ($t) => [
                'id' => $t->id,
                'name' => $t->name,
                'slug' => $t->slug,
            ])),
            'url' => app(PermalinkService::class)->postUrl($this->resource),
            'seo' => [
                'title' => $seo['title'],
                'description' => $seo['description'],
                'canonical' => $seo['canonical'],
                'image' => $seo['image'],
                'robots' => $seo['robots'],
            ],
        ];
    }
}
