<?php

declare(strict_types=1);

namespace StoreYar\Modules\Organization\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class CreateBranchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
        ];
    }
}
