<?php

namespace App\Http\Resources\Api\V1;

use App\Services\SeoService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Page */
class PageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $seo = app(SeoService::class)->resolve($this->resource);

        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'content' => $this->when($request->routeIs('api.v1.pages.show') || $request->routeIs('api.v1.admin.pages.*'), $this->content),
            'status' => $this->when($request->user(), $this->status),
            'published_at' => optional($this->published_at)?->toIso8601String(),
            'url' => app(SeoService::class)->contentUrl($this->resource),
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
