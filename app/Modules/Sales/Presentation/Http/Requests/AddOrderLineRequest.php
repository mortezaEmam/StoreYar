<?php

declare(strict_types=1);

namespace StoreYar\Modules\Sales\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class AddOrderLineRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'product_id' => ['required', 'string', 'size:26'],
            'quantity' => ['required', 'integer', 'min:1'],
            'unit_price_amount' => ['required', 'integer', 'min:0'],
            'currency' => ['sometimes', 'string', 'size:3'],
        ];
    }
}
