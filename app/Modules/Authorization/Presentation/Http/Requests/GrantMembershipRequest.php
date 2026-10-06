<?php

declare(strict_types=1);

namespace StoreYar\Modules\Authorization\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use StoreYar\Modules\Authorization\Domain\Enums\Role;

final class GrantMembershipRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'user_id' => ['required', 'string', 'size:26'],
            'role' => ['required', 'string', Rule::in([
                Role::ADMIN->value,
                Role::MEMBER->value,
            ])],
        ];
    }
}
