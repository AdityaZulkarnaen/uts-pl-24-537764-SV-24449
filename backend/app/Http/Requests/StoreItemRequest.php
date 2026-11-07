<?php

namespace App\Http\Requests;

use App\Models\Item;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class StoreItemRequest extends FormRequest
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
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:30', Rule::unique('items', 'code')],
            'name' => ['required', 'string', 'max:255'],
            'category' => ['required', 'string', 'max:100'],
            'quantity' => ['required', 'integer', 'min:0', 'max:100000'],
            'condition' => ['required', Rule::in(Item::CONDITIONS)],
            'location' => ['required', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * Handle a failed validation attempt.
     */
    protected function failedValidation(Validator $validator)
    {
        $failedRules = [];
        foreach ($validator->failed() as $field => $rules) {
            $failedRules[$field] = array_map('strtolower', array_keys($rules));
        }

        $itemRoute = $this->route('item');
        $resourceId = $itemRoute instanceof Item ? $itemRoute->id : (is_numeric($itemRoute) ? (int) $itemRoute : null);

        Log::warning('item.validation_failed', [
            'user_id' => $this->user()?->id ?? 'guest',
            'action' => $this->isMethod('post') ? 'create' : 'update',
            'resource' => 'item',
            'resource_id' => $resourceId,
            'reason' => 'validation_error',
            'failed_fields' => array_keys($failedRules),
            'failed_rules' => $failedRules,
            'request_id' => (string) ($this->header('X-Request-Id') ?: Str::uuid()),
        ]);

        parent::failedValidation($validator);
    }
}
