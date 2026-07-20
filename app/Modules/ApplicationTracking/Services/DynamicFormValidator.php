<?php

namespace App\Modules\ApplicationTracking\Services;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

/**
 * Turns an application_categories.form_schema JSON definition into Laravel
 * validation rules at submit/resubmit time, so form fields stay admin-
 * configurable data instead of hard-coded per application category.
 */
class DynamicFormValidator
{
    private const ALLOWED_FILE_MIMES = 'pdf,doc,docx,jpg,jpeg,png,webp';

    private const MAX_FILE_KB = 10240; // 10MB

    /**
     * @param  array<int, array<string, mixed>>  $schema
     * @param  array<string, mixed>  $formData
     * @param  array<string, \Illuminate\Http\UploadedFile>  $files  keyed by field key
     * @return array<string, mixed> the validated non-file field values
     */
    public function validate(array $schema, array $formData, array $files = []): array
    {
        $rules = [];

        foreach ($schema as $field) {
            if (($field['type'] ?? null) === 'file') {
                continue; // files are validated separately below
            }

            $key = $field['key'];
            $required = $field['required'] ?? false;

            $rules[$key] = array_merge(
                [$required ? 'required' : 'nullable'],
                match ($field['type'] ?? 'text') {
                    'number' => ['numeric'],
                    'date' => ['date'],
                    'select' => array_filter([
                        'string',
                        ! empty($field['options']) ? 'in:'.implode(',', $field['options']) : null,
                    ]),
                    default => ['string'],
                },
            );

            if (isset($field['max']) && in_array($field['type'] ?? 'text', ['text', 'textarea', 'number'], true)) {
                $rules[$key][] = 'max:'.$field['max'];
            }
        }

        $validator = Validator::make($formData, $rules);
        $validator->validate();

        foreach ($schema as $field) {
            if (($field['type'] ?? null) !== 'file') {
                continue;
            }

            $file = $files[$field['key']] ?? null;

            if (($field['required'] ?? false) && ! $file) {
                throw ValidationException::withMessages([
                    "attachments.{$field['key']}" => ["The {$field['label']} field is required."],
                ]);
            }

            if ($file) {
                Validator::make(['file' => $file], [
                    'file' => ['file', 'mimes:'.self::ALLOWED_FILE_MIMES, 'max:'.self::MAX_FILE_KB],
                ])->validate();
            }
        }

        return $validator->validated();
    }
}
