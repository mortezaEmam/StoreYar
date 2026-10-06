<?php

declare(strict_types=1);

namespace StoreYar\Modules\Authorization\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use StoreYar\Modules\Authorization\Domain\Enums\Role;

final class ChangeMemberRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'role' => ['required', 'string', Rule::in([
                Role::OWNER->value,
                Role::ADMIN->value,
                Role::MEMBER->value,
            ])],
        ];
    }
}
