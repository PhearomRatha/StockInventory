<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class RequestOTPRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Public route
    }

    public function rules(): array
    {
        return [
            'email' => 'required|email|exists:users,email',
        ];
    }

    public function messages(): array
    {
        return [
            'email.required' => 'Email is required.',
            'email.email' => 'Please enter a valid email address.',
            'email.exists' => 'No account found with this email.',
        ];
    }
}

class VerifyOTPRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => 'required|email',
            'otp' => 'required|string|size:6',
        ];
    }

    public function messages(): array
    {
        return [
            'email.required' => 'Email is required.',
            'otp.required' => 'OTP is required.',
            'otp.size' => 'OTP must be 6 digits.',
        ];
    }
}

class ResetPasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => 'required|email|exists:users,email',
            'password' => [
                'required',
                'string',
                'confirmed',
                Password::min(8)->mixedCase()->numbers(),
            ],
            'password_confirmation' => 'required|string',
        ];
    }

    public function messages(): array
    {
        return [
            'email.required' => 'Email is required.',
            'password.required' => 'New password is required.',
            'password.min' => 'Password must be at least 8 characters.',
            'password.confirmed' => 'Passwords do not match.',
            'password_confirmation.required' => 'Please confirm your new password.',
        ];
    }
}