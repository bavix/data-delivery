<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FileRequest extends FormRequest
{

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return \auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'file' => ['required', 'file'],
            'uuid' => ['uuid']
        ];
    }

    /**
     * @return array
     */
    public function validated(): array
    {
        return \array_merge(parent::validated(), [
            'uuid' => $this->header('Idempotency-Key'),
        ]);
    }

}
