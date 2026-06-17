<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use SebastianBergmann\Type\TrueType;

class MinecraftServerRequest extends FormRequest
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
            'server_name' => ['required', 'string', 'max:255'],
            'motd' => ['nullable', 'string', 'max:255'],
            'difficulty' => ['required', 'integer', 'min:0', 'max:3'],  
            'force_gamemode' => ['boolean', 'required'],
            'allow_flight' => ['boolean', 'required']    
        ];
    }
}
