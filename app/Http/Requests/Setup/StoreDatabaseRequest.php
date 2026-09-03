<?php

namespace App\Http\Requests\Setup;

use App\Services\SystemRequirementsService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDatabaseRequest extends FormRequest
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
        $available = array_keys(app(SystemRequirementsService::class)->availableDriverOptions());

        return [
            'type' => ['required', 'string', Rule::in($available ?: ['pgsql', 'mysql'])],
            'host' => ['required', 'string', 'max:255'],
            'port' => ['required', 'integer', 'min:1', 'max:65535'],
            'database' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:255'],
            'password' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'type.in' => 'Please choose a supported database type available on this server.',
            'host.required' => 'Please enter the database host.',
            'database.required' => 'Please enter the database name.',
            'username.required' => 'Please enter the database username.',
        ];
    }
}
