<?php

declare(strict_types=1);

namespace StoreYar\Modules\Inventory\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class CreateWarehouseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:32'],
            'name' => ['required', 'string', 'max:255'],
            'type' => ['sometimes', 'string', 'in:MAIN,STORE,TRANSIT,QUARANTINE'],
            'branch_id' => ['sometimes', 'nullable', 'string', 'size:26'],
            'address' => ['sometimes', 'nullable', 'string'],
        ];
    }
}
