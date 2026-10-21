<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class MinecraftCommandRequest extends FormRequest
{

    private const BLOCKED_COMMANDS = [
        'stop',
        'restart',
        'reload',
        'save-off',
        'execute',
        'function',
        'debug',
        'jfr',
        'perf',
        'whitelist'
    ];

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
            'command' => ['required', 'string', 'min:1', 'max:256', 'regex:/^[^\x00-\x1F\x7F]+$/']
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                $command = $this->input('command');

                if (!is_string($command)) {
                    return;
                }

                $command = ltrim($command);

                // Remove the initial '/'
                $command = ltrim($command, '/');

                // Extracts only the command name (ex: op nickname => op)
                $name = strtolower(
                    preg_split('/\s+/', $command, 2)[0] ?? ''
                );

                // Remove explicit namespace like minecraft:stop
                $name = strtolower(
                    substr($name, strrpos($name, ':') !== false
                        ? strrpos($name, ':') + 1
                        : 0)
                );

                if (in_array($name, self::BLOCKED_COMMANDS, true)) {
                    $validator->errors()->add(
                        'command',
                        'This command is not allowed.'
                    );
                }
            },
        ];
    }
}
