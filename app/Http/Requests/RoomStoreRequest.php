<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RoomStoreRequest extends FormRequest
{
 
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'nameroom' => 'required|string|max:100|unique:rooms,nameroom',
            'capacity' => 'required|integer|min:1',
            'detail'   => 'required|string|min:10',
            'images'   => 'required|array|min:1', // Harus berupa array dan minimal 1 file
            'images.*' => 'image|mimes:jpeg,png,jpg,webp|max:2048', // Validasi tiap file di dalam array
        ];
    }
    public function messages(): array
    {
        return [
            'nameroom.required' => 'Nama ruangan wajib diisi.',
            'nameroom.unique'   => 'Nama ruangan sudah terdaftar di sistem.',
            'capacity.required' => 'Kapasitas ruangan wajib diisi.',
            'capacity.min'      => 'Kapasitas minimal adalah 1 orang.',
            'detail.required'   => 'Detail atau deskripsi ruangan wajib diisi.',
            'images.required'   => 'Mohon unggah setidaknya satu foto ruangan.',
            'images.*.image'    => 'File harus berupa gambar.',
            'images.*.max'      => 'Ukuran gambar maksimal adalah 2MB.',
        ];
    }
}
