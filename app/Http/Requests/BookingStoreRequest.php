<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BookingStoreRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name'          => 'required|string|max:255',
            'organization'  => 'required|string|max:255',
            'email'         => 'required|email|max:255',
            'no_whatsapp'   => 'required|string|max:20',
            'room_id'       => 'required|exists:rooms,id',
            'start_time'    => 'required|date|after:now',
            'end_time'      => 'required|date|after:start_time',
            'note'          => 'nullable|string',
            'purpose'       => 'required|string|max:255',
            
        ];
    }

    public function messages(): array
    {
        return [
            'room_id.exists' => 'The selected room does not exist.',
            'start_time.after' => 'The start time must be a date in the future.',
            'end_time.after' => 'The end time must be after the start time.',
        ];
    }
}
