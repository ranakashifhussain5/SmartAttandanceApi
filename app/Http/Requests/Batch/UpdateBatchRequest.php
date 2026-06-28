<?php

namespace App\Http\Requests\Batch;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBatchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'program_id' => ['sometimes', 'required', 'integer', 'exists:programs,id'],
            'batch_name' => ['sometimes', 'required', 'string', 'max:50'],
            'start_year' => ['sometimes', 'required', 'integer', 'min:2000', 'max:2100'],
            'end_year' => ['sometimes', 'required', 'integer', 'min:2000', 'max:2100', 'gte:start_year'],
            'semester' => ['sometimes', 'required', 'integer', 'min:1', 'max:8'],
            'shift' => ['sometimes', 'required', 'string', 'in:Morning,Evening'],
        ];
    }
}
