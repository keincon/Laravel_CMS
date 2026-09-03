<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateContentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $content = $this->route('content');

        return $this->user()?->can('update', $content) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'slug' => ['sometimes', 'nullable', 'string', 'max:255'],
            'body' => ['sometimes', 'nullable', 'string'],
            'excerpt' => ['sometimes', 'nullable', 'string'],
            'blocks' => ['sometimes', 'nullable', 'array'],
            'status' => ['sometimes', 'string', Rule::in(['draft', 'pending', 'private', 'scheduled', 'published', 'trash', 'publish', 'future'])],
            'visibility' => ['sometimes', 'nullable', 'string', 'max:32'],
            'parent_id' => ['sometimes', 'nullable', 'integer', 'exists:contents,id'],
            'featured_media_id' => ['sometimes', 'nullable', 'integer', 'exists:media,id'],
            'comment_status' => ['sometimes', 'nullable', Rule::in(['open', 'closed'])],
            'template' => ['sometimes', 'nullable', 'string', 'max:100'],
            'menu_order' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'published_at' => ['sometimes', 'nullable', 'date'],
            'scheduled_at' => ['sometimes', 'nullable', 'date'],
            'term_ids' => ['sometimes', 'nullable', 'array'],
            'term_ids.*' => ['integer', 'exists:terms,id'],
        ];
    }
}
