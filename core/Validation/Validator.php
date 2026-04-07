<?php

declare(strict_types=1);

namespace Zen\Validation;

class Validator
{
    protected array $data;
    protected array $rules;
    protected array $messages = [];
    protected array $errors = [];

    public function __construct(array $data, array $rules, array $messages = [])
    {
        $this->data = $data;
        $this->rules = $rules;
        $this->messages = $messages;
    }

    public static function make(array $data, array $rules, array $messages = []): static
    {
        return new static($data, $rules, $messages);
    }

    public function validate(): array
    {
        $this->errors = [];

        foreach ($this->rules as $field => $ruleString) {
            $rules = explode('|', $ruleString);

            foreach ($rules as $rule) {
                $this->validateField($field, $rule);
            }
        }

        return $this->errors;
    }

    public function fails(): bool
    {
        return !empty($this->validate());
    }

    public function errors(): array
    {
        return $this->errors;
    }

    public function validated(): array
    {
        $validated = [];
        
        foreach ($this->rules as $field => $ruleString) {
            if (isset($this->data[$field])) {
                $validated[$field] = $this->data[$field];
            }
        }
        
        return $validated;
    }

    protected function validateField(string $field, string $rule): void
    {
        [$ruleName, $parameter] = array_pad(explode(':', $rule, 2), 2, null);

        $value = $this->data[$field] ?? null;
        $method = 'validate' . ucfirst($ruleName);

        if (!method_exists($this, $method)) {
            return;
        }

        if (!$this->{$method}($field, $value, $parameter)) {
            $this->addError($field, $ruleName, $parameter);
        }
    }

    protected function addError(string $field, string $rule, ?string $parameter): void
    {
        $key = "{$field}.{$rule}";

        if (isset($this->messages[$key])) {
            $message = $this->messages[$key];
        } else {
            $message = $this->getDefaultMessage($field, $rule, $parameter);
        }

        $message = str_replace([':field', ':value', ':param'], [$field, $this->data[$field] ?? '', $parameter ?? ''], $message);

        if (!isset($this->errors[$field])) {
            $this->errors[$field] = [];
        }

        $this->errors[$field][] = $message;
    }

    protected function getDefaultMessage(string $field, string $rule, ?string $parameter): string
    {
        $messages = [
            'required' => "The {$field} is required.",
            'email' => "The {$field} must be a valid email address.",
            'min' => "The {$field} must be at least {$parameter} characters.",
            'max' => "The {$field} must not exceed {$parameter} characters.",
            'numeric' => "The {$field} must be a number.",
            'integer' => "The {$field} must be an integer.",
            'string' => "The {$field} must be a string.",
            'array' => "The {$field} must be an array.",
            'boolean' => "The {$field} must be true or false.",
            'url' => "The {$field} must be a valid URL.",
            'ip' => "The {$field} must be a valid IP address.",
            'alpha' => "The {$field} may only contain letters.",
            'alphaNum' => "The {$field} may only contain letters and numbers.",
            'date' => "The {$field} must be a valid date.",
            'confirmed' => "The {$field} confirmation does not match.",
            'unique' => "The {$field} has already been taken.",
            'exists' => "The selected {$field} is invalid.",
            'in' => "The selected {$field} is invalid.",
            'notIn' => "The selected {$field} is invalid.",
            'minValue' => "The {$field} must be at least {$parameter}.",
            'maxValue' => "The {$field} must not be greater than {$parameter}.",
            'between' => "The {$field} must be between {$parameter} and the second parameter.",
            'match' => "The {$field} must match the {$parameter} field.",
        ];

        return $messages[$rule] ?? "The {$field} is invalid.";
    }

    protected function validateRequired(string $field, mixed $value): bool
    {
        if (is_array($value)) {
            return !empty($value);
        }

        return $value !== null && $value !== '';
    }

    protected function validateEmail(string $field, mixed $value): bool
    {
        return filter_var($value, FILTER_VALIDATE_EMAIL) !== false;
    }

    protected function validateMin(string $field, mixed $value, ?string $parameter = null): bool
    {
        if ($parameter === null) {
            return true;
        }

        if (is_numeric($value)) {
            return $value >= (float) $parameter;
        }

        if (is_string($value)) {
            return mb_strlen($value) >= (int) $parameter;
        }

        if (is_array($value)) {
            return count($value) >= (int) $parameter;
        }

        return false;
    }

    protected function validateMax(string $field, mixed $value, ?string $parameter = null): bool
    {
        if ($parameter === null) {
            return true;
        }

        if (is_numeric($value)) {
            return $value <= (float) $parameter;
        }

        if (is_string($value)) {
            return mb_strlen($value) <= (int) $parameter;
        }

        if (is_array($value)) {
            return count($value) <= (int) $parameter;
        }

        return false;
    }

    protected function validateNumeric(string $field, mixed $value): bool
    {
        return is_numeric($value);
    }

    protected function validateInteger(string $field, mixed $value): bool
    {
        return filter_var($value, FILTER_VALIDATE_INT) !== false;
    }

    protected function validateString(string $field, mixed $value): bool
    {
        return is_string($value);
    }

    protected function validateArray(string $field, mixed $value): bool
    {
        return is_array($value);
    }

    protected function validateBoolean(string $field, mixed $value): bool
    {
        return in_array($value, [true, false, 0, 1, '0', '1', 'true', 'false'], true);
    }

    protected function validateUrl(string $field, mixed $value): bool
    {
        return filter_var($value, FILTER_VALIDATE_URL) !== false;
    }

    protected function validateIp(string $field, mixed $value): bool
    {
        return filter_var($value, FILTER_VALIDATE_IP) !== false;
    }

    protected function validateAlpha(string $field, mixed $value): bool
    {
        return preg_match('/^[a-zA-Z]+$/', $value) === 1;
    }

    protected function validateAlphaNum(string $field, mixed $value): bool
    {
        return preg_match('/^[a-zA-Z0-9]+$/', $value) === 1;
    }

    protected function validateDate(string $field, mixed $value): bool
    {
        return strtotime($value) !== false;
    }

    protected function validateConfirmed(string $field, mixed $value): bool
    {
        $confirmedField = $field . '_confirmation';
        return isset($this->data[$confirmedField]) && $value === $this->data[$confirmedField];
    }

    protected function validateUnique(string $field, mixed $value, ?string $parameter = null): bool
    {
        if ($value === null || $value === '') {
            return true;
        }

        $parts = explode(',', $parameter ?? '');
        $table = $parts[0] ?? $field . 's';
        $column = $parts[1] ?? $field;

        $qb = new \Zen\Database\QueryBuilder();
        $qb->table($table);
        $qb->where($column, $value);

        return !$qb->exists();
    }

    protected function validateExists(string $field, mixed $value, ?string $parameter = null): bool
    {
        if ($value === null || $value === '') {
            return true;
        }

        $parts = explode(',', $parameter ?? '');
        $table = $parts[0] ?? $field . 's';
        $column = $parts[1] ?? $field;

        $qb = new \Zen\Database\QueryBuilder();
        $qb->table($table);
        $qb->where($column, $value);

        return $qb->exists();
    }

    protected function validateIn(string $field, mixed $value, ?string $parameter = null): bool
    {
        if ($value === null || $value === '') {
            return true;
        }

        $values = explode(',', $parameter ?? '');
        return in_array($value, $values, true);
    }

    protected function validateNotIn(string $field, mixed $value, ?string $parameter = null): bool
    {
        return !$this->validateIn($field, $value, $parameter);
    }

    protected function validateMinValue(string $field, mixed $value, ?string $parameter = null): bool
    {
        return is_numeric($value) && $value >= (float) ($parameter ?? 0);
    }

    protected function validateMaxValue(string $field, mixed $value, ?string $parameter = null): bool
    {
        return is_numeric($value) && $value <= (float) ($parameter ?? PHP_INT_MAX);
    }

    protected function validateBetween(string $field, mixed $value, ?string $parameter = null): bool
    {
        if (!is_numeric($value)) {
            return false;
        }

        $parts = explode(',', $parameter ?? '');
        $min = (float) ($parts[0] ?? 0);
        $max = (float) ($parts[1] ?? PHP_INT_MAX);

        return $value >= $min && $value <= $max;
    }

    protected function validateMatch(string $field, mixed $value, ?string $parameter = null): bool
    {
        return isset($this->data[$parameter]) && $value === $this->data[$parameter];
    }
}
