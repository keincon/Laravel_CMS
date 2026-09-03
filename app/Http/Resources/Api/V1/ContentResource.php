<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Content */
class ContentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $status = $this->status instanceof \BackedEnum ? $this->status->value : $this->status;

        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'type' => $this->whenLoaded('type', fn () => [
                'slug' => $this->type->slug,
                'singular_label' => $this->type->singular_label,
                'plural_label' => $this->type->plural_label,
            ]),
            'title' => $this->title,
            'slug' => $this->slug,
            'excerpt' => $this->excerpt,
            'body' => $this->body,
            'blocks' => $this->blocks,
            'status' => $status,
            'visibility' => $this->visibility,
            'comment_status' => $this->comment_status,
            'template' => $this->template,
            'menu_order' => $this->menu_order,
            'published_at' => $this->published_at?->toIso8601String(),
            'scheduled_at' => $this->scheduled_at?->toIso8601String(),
            'author' => $this->whenLoaded('author', fn () => [
                'id' => $this->author?->id,
                'username' => $this->author?->username,
                'display_name' => $this->author?->publicName(),
            ]),
            'featured_media_id' => $this->featured_media_id,
            'parent_id' => $this->parent_id,
            'terms' => $this->whenLoaded('terms', fn () => $this->terms->map(fn ($term) => [
                'id' => $term->id,
                'slug' => $term->slug,
                'name' => $term->name,
                'taxonomy_id' => $term->taxonomy_id,
            ])),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
