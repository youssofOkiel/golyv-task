<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AvailableSeatsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'start_station_id' => ['required', 'integer', 'exists:stations,id'],
            'end_station_id' => ['required', 'integer', 'exists:stations,id', 'different:start_station_id'],
        ];
    }
}
