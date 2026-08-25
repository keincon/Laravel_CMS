<?php

namespace App\Http\Requests\Setup;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreWebsiteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'url' => ['required', 'url', 'max:255'],
            'timezone' => ['required', 'timezone:all'],
            'language' => ['required', Rule::in(array_keys(config('cms.languages', [])))],
            'date_format' => ['required', Rule::in(array_keys(config('cms.date_formats', [])))],
        ];
    }
}
