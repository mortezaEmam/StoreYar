<?php

declare(strict_types=1);

namespace StoreYar\Modules\Catalog\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class CreateCustomerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:64'],
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['sometimes', 'nullable', 'string', 'max:32'],
            'email' => ['sometimes', 'nullable', 'email', 'max:255'],
            'customer_type' => ['sometimes', 'string', 'in:individual,business'],
            'credit_limit_amount' => ['sometimes', 'integer', 'min:0'],
            'currency' => ['sometimes', 'string', 'size:3'],
            'payment_terms_days' => ['sometimes', 'integer', 'min:0'],
            'address' => ['sometimes', 'nullable', 'string'],
            'notes' => ['sometimes', 'nullable', 'string'],
        ];
    }
}
