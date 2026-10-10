<?php

declare(strict_types=1);

namespace StoreYar\Modules\Inventory\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class InitializeStockRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'product_id' => ['required', 'string', 'size:26'],
            'quantity' => ['sometimes', 'integer', 'min:0'],
        ];
    }
}
