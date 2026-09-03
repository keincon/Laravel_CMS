<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreContentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', \App\Models\Content::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'type' => ['required', 'string', Rule::exists('content_types', 'slug')],
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
            'body' => ['nullable', 'string'],
            'excerpt' => ['nullable', 'string'],
            'blocks' => ['nullable', 'array'],
            'status' => ['nullable', 'string', Rule::in(['draft', 'pending', 'private', 'scheduled', 'published', 'trash', 'publish', 'future'])],
            'visibility' => ['nullable', 'string', 'max:32'],
            'parent_id' => ['nullable', 'integer', 'exists:contents,id'],
            'featured_media_id' => ['nullable', 'integer', 'exists:media,id'],
            'comment_status' => ['nullable', Rule::in(['open', 'closed'])],
            'template' => ['nullable', 'string', 'max:100'],
            'menu_order' => ['nullable', 'integer', 'min:0'],
            'published_at' => ['nullable', 'date'],
            'scheduled_at' => ['nullable', 'date'],
            'term_ids' => ['nullable', 'array'],
            'term_ids.*' => ['integer', 'exists:terms,id'],
        ];
    }
}
