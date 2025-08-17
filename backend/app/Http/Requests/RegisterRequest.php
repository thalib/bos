<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Authorization is handled in the controller for better error handling
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email'],
            'username' => ['required', 'string', 'unique:users,username'],
            'whatsapp' => ['required', 'string', 'unique:users,whatsapp', 'regex:/^[+][0-9]{8,15}$/'],
            'password' => ['required', 'string', 'confirmed', 'min:8'],
            'role' => ['nullable', 'in:user,admin'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Name is required.',
            'email.required' => 'Email is required.',
            'email.email' => 'Please provide a valid email address.',
            'email.unique' => 'This email is already registered.',
            'username.required' => 'Username is required.',
            'username.unique' => 'This username is already taken.',
            'whatsapp.required' => 'WhatsApp number is required.',
            'whatsapp.unique' => 'This WhatsApp number is already registered.',
            'whatsapp.regex' => 'WhatsApp number must be in E.164 format (e.g., +628123456789).',
            'password.required' => 'Password is required.',
            'password.confirmed' => 'Password confirmation does not match.',
            'password.min' => 'Password must be at least 8 characters.',
            'role.in' => 'Role must be either user or admin.',
        ];
    }

    /**
     * Get custom attribute names for validator errors.
     */
    public function attributes(): array
    {
        return [
            'whatsapp' => 'WhatsApp number',
        ];
    }
}
