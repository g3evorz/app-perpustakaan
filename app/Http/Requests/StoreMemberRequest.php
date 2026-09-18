<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreMemberRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'nama' => 'required|string|max:50',
            'nim' => 'required|string|digits_between:8,12',
            'email' => 'required|email|max:100',
            'nomor_telepon' => 'required|string|max:15',
            'alamat' => 'required|string|max:200',
            'status' => 'required|in:aktif,nonaktif',
        ];
    }

    public function messages(): array
    {
        return [
            'nama.required' => 'Nama anggota wajib diisi.',
            'nama.max' => 'Nama anggota maksimal 50 karakter.',
            'nim.required' => 'NIM wajib diisi.',
            'nim.digits_between' => 'NIM harus berupa angka 8-12 digit.',
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'email.max' => 'Email maksimal 100 karakter.',
            'nomor_telepon.required' => 'Nomor telepon wajib diisi.',
            'nomor_telepon.max' => 'Nomor telepon maksimal 15 karakter.',
            'alamat.required' => 'Alamat wajib diisi.',
            'alamat.max' => 'Alamat maksimal 200 karakter.',
            'status.required' => 'Status wajib diisi.',
            'status.in' => 'Status harus berisi aktif atau nonaktif.',
        ];
    }
}
