<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class UserPasswordRequest extends FormRequest
{
    /** Error ditampung di bag terpisah agar tidak tercampur dengan form profil. */
    protected $errorBag = 'password';

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::min(8), 'different:current_password'],
        ];
    }

    public function attributes(): array
    {
        return [
            'current_password' => 'Password lama',
            'password' => 'Password baru',
        ];
    }

    public function messages(): array
    {
        return [
            'password.different' => 'Password baru harus berbeda dengan password lama.',
        ];
    }
}
