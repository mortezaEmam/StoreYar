<?php

declare(strict_types=1);

namespace StoreYar\Modules\Shipping\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class CreateShipmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'order_id' => ['required', 'string', 'size:26'],
        ];
    }
}
