<?php

declare(strict_types=1);

namespace StoreYar\Modules\Identity\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class ResetPasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'email', 'max:255'],
            'token' => ['required', 'string', 'min:32', 'max:128'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ];
    }
}
