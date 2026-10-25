<?php

declare(strict_types=1);

namespace StoreYar\Modules\Inventory\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class ReceiveStockRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'warehouse_id' => ['required', 'string', 'size:26'],
            'product_id' => ['required', 'string', 'size:26'],
            'quantity' => ['required', 'integer', 'min:1'],
            'reason' => ['sometimes', 'nullable', 'string', 'max:255'],
            'unit_cost_amount' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'currency' => ['sometimes', 'string', 'size:3'],
            'document_id' => ['sometimes', 'nullable', 'string', 'size:26'],
        ];
    }
}
