<?php

declare(strict_types=1);

namespace StoreYar\Modules\Inventory\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class AdjustStockRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'amount' => ['required', 'integer', 'min:1'],
            'direction' => ['required', 'string', Rule::in(['increase', 'decrease'])],
        ];
    }
}
