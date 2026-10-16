<?php

declare(strict_types=1);

namespace StoreYar\Modules\Shipping\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class ShipShipmentRequest extends FormRequest
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
            'tracking_number' => ['required', 'string', 'max:128'],
        ];
    }
}
