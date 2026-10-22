<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

class UpdateItemRequest extends StoreItemRequest
{
    /**
     * Same rules as creating an item, except the code may stay unchanged.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            ...parent::rules(),
            'code' => ['required', 'string', 'max:30', Rule::unique('items', 'code')->ignore($this->route('item'))],
        ];
    }
}
