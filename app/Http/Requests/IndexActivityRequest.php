<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class IndexActivityRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'token' => ['required', 'string'],
            'entity_type_id' => ['required'],
            'element_id' => ['required']
        ];
    }
}
