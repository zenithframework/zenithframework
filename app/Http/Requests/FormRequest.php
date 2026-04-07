<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Zen\Http\Request;
use Zen\Validation\Validator;

abstract class FormRequest extends Request
{
    protected array $messages = [];

    public function validateRequest(): bool
    {
        $validator = Validator::make($this->all(), $this->rules(), $this->messages);
        
        if ($validator->fails()) {
            session()->put('_errors', $validator->errors());
            return false;
        }

        return true;
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [];
    }

    public function messages(): array
    {
        return [];
    }

    public function validated(): array
    {
        return $this->all();
    }

    public function errors(): array
    {
        return session()->get('_errors', []);
    }

    public function fails(): bool
    {
        return !$this->validateRequest();
    }
}
