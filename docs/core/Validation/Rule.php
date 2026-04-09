<?php

declare(strict_types=1);

namespace Zenith\Validation;

use Closure;

/**
 * Validation Rule object (Laravel 13+ style).
 * 
 * Allows for reusable, composable validation rules.
 */
class Rule
{
    /**
     * Create a required rule.
     *
     * @return static
     */
    public static function required(): static
    {
        return new static('required');
    }

    /**
     * Create a nullable rule.
     *
     * @return static
     */
    public static function nullable(): static
    {
        return new static('nullable');
    }

    /**
     * Create a string rule.
     *
     * @return static
     */
    public static function string(): static
    {
        return new static('string');
    }

    /**
     * Create an integer rule.
     *
     * @return static
     */
    public static function integer(): static
    {
        return new static('integer');
    }

    /**
     * Create a numeric rule.
     *
     * @return static
     */
    public static function numeric(): static
    {
        return new static('numeric');
    }

    /**
     * Create an array rule.
     *
     * @return static
     */
    public static function array(): static
    {
        return new static('array');
    }

    /**
     * Create a boolean rule.
     *
     * @return static
     */
    public static function boolean(): static
    {
        return new static('boolean');
    }

    /**
     * Create an email rule.
     *
     * @return static
     */
    public static function email(): static
    {
        return new static('email');
    }

    /**
     * Create a URL rule.
     *
     * @return static
     */
    public static function url(): static
    {
        return new static('url');
    }

    /**
     * Create an IP rule.
     *
     * @return static
     */
    public static function ip(): static
    {
        return new static('ip');
    }

    /**
     * Create a JSON rule.
     *
     * @return static
     */
    public static function json(): static
    {
        return new static('json');
    }

    /**
     * Create a date rule.
     *
     * @return static
     */
    public static function date(): static
    {
        return new static('date');
    }

    /**
     * Create a before date rule.
     *
     * @param string $date
     * @return static
     */
    public static function before(string $date): static
    {
        return new static("before:{$date}");
    }

    /**
     * Create an after date rule.
     *
     * @param string $date
     * @return static
     */
    public static function after(string $date): static
    {
        return new static("after:{$date}");
    }

    /**
     * Create a min rule.
     *
     * @param int $value
     * @return static
     */
    public static function min(int $value): static
    {
        return new static("min:{$value}");
    }

    /**
     * Create a max rule.
     *
     * @param int $value
     * @return static
     */
    public static function max(int $value): static
    {
        return new static("max:{$value}");
    }

    /**
     * Create a between rule.
     *
     * @param int $min
     * @param int $max
     * @return static
     */
    public static function between(int $min, int $max): static
    {
        return new static("between:{$min},{$max}");
    }

    /**
     * Create a size rule.
     *
     * @param int $value
     * @return static
     */
    public static function size(int $value): static
    {
        return new static("size:{$value}");
    }

    /**
     * Create an in rule (value must be in array).
     *
     * @param array $values
     * @return static
     */
    public static function in(array $values): static
    {
        $valuesStr = implode(',', $values);
        return new static("in:{$valuesStr}");
    }

    /**
     * Create a not_in rule.
     *
     * @param array $values
     * @return static
     */
    public static function notIn(array $values): static
    {
        $valuesStr = implode(',', $values);
        return new static("not_in:{$valuesStr}");
    }

    /**
     * Create a unique rule (database).
     *
     * @param string $table
     * @param string $column
     * @param mixed $ignore
     * @param string $ignoreColumn
     * @return static
     */
    public static function unique(string $table, string $column = 'NULL', mixed $ignore = null, string $ignoreColumn = 'id'): static
    {
        $rule = "unique:{$table},{$column}";
        if ($ignore !== null) {
            $rule .= ",{$ignore},{$ignoreColumn}";
        }
        return new static($rule);
    }

    /**
     * Create an exists rule (database).
     *
     * @param string $table
     * @param string $column
     * @return static
     */
    public static function exists(string $table, string $column = 'NULL'): static
    {
        return new static("exists:{$table},{$column}");
    }

    /**
     * Create a confirmed rule.
     *
     * @return static
     */
    public static function confirmed(): static
    {
        return new static('confirmed');
    }

    /**
     * Create a same rule.
     *
     * @param string $field
     * @return static
     */
    public static function same(string $field): static
    {
        return new static("same:{$field}");
    }

    /**
     * Create a different rule.
     *
     * @param string $field
     * @return static
     */
    public static function different(string $field): static
    {
        return new static("different:{$field}");
    }

    /**
     * Create an accepted rule.
     *
     * @return static
     */
    public static function accepted(): static
    {
        return new static('accepted');
    }

    /**
     * Create an accepted_if rule.
     *
     * @param string $field
     * @param mixed $value
     * @return static
     */
    public static function acceptedIf(string $field, mixed $value): static
    {
        return new static("accepted_if:{$field},{$value}");
    }

    /**
     * Create a declined rule.
     *
     * @return static
     */
    public static function declined(): static
    {
        return new static('declined');
    }

    /**
     * Create an active_url rule.
     *
     * @return static
     */
    public static function activeUrl(): static
    {
        return new static('active_url');
    }

    /**
     * Create a starts_with rule.
     *
     * @param array $values
     * @return static
     */
    public static function startsWith(array $values): static
    {
        $valuesStr = implode(',', $values);
        return new static("starts_with:{$valuesStr}");
    }

    /**
     * Create an ends_with rule.
     *
     * @param array $values
     * @return static
     */
    public static function endsWith(array $values): static
    {
        $valuesStr = implode(',', $values);
        return new static("ends_with:{$valuesStr}");
    }

    /**
     * Create a regex rule.
     *
     * @param string $pattern
     * @return static
     */
    public static function regex(string $pattern): static
    {
        return new static("regex:{$pattern}");
    }

    /**
     * Create a not_regex rule.
     *
     * @param string $pattern
     * @return static
     */
    public static function notRegex(string $pattern): static
    {
        return new static("not_regex:{$pattern}");
    }

    /**
     * Create a custom rule using a closure.
     *
     * @param Closure $callback
     * @return ClosureRule
     */
    public static function when(Closure $callback): ClosureRule
    {
        return new ClosureRule($callback);
    }

    /**
     * Create a rule that requires another field to be present.
     *
     * @param string $field
     * @return static
     */
    public static function requiredWith(string $field): static
    {
        return new static("required_with:{$field}");
    }

    /**
     * Create a required_without rule.
     *
     * @param string $field
     * @return static
     */
    public static function requiredWithout(string $field): static
    {
        return new static("required_without:{$field}");
    }

    /**
     * Create a required_if rule.
     *
     * @param string $field
     * @param mixed $value
     * @return static
     */
    public static function requiredIf(string $field, mixed $value): static
    {
        return new static("required_if:{$field},{$value}");
    }

    /**
     * Create a required_unless rule.
     *
     * @param string $field
     * @param mixed $value
     * @return static
     */
    public static function requiredUnless(string $field, mixed $value): static
    {
        return new static("required_unless:{$field},{$value}");
    }

    /**
     * Create a file rule.
     *
     * @return static
     */
    public static function file(): static
    {
        return new static('file');
    }

    /**
     * Create an image rule.
     *
     * @return static
     */
    public static function image(): static
    {
        return new static('image');
    }

    /**
     * Create a dimensions rule for images.
     *
     * @param array $dimensions
     * @return static
     */
    public static function dimensions(array $dimensions = []): static
    {
        $parts = [];
        foreach ($dimensions as $key => $value) {
            $parts[] = "{$key}={$value}";
        }
        $dimensionsStr = implode(',', $parts);
        return new static("dimensions:{$dimensionsStr}");
    }

    /**
     * Create a mimes rule for file validation.
     *
     * @param array $mimeTypes
     * @return static
     */
    public static function mimes(array $mimeTypes): static
    {
        $mimeStr = implode(',', $mimeTypes);
        return new static("mimes:{$mimeStr}");
    }

    /**
     * Create a mime_type rule.
     *
     * @param array $mimeTypes
     * @return static
     */
    public static function mimeType(array $mimeTypes): static
    {
        $mimeStr = implode(',', $mimeTypes);
        return new static("mime_type:{$mimeStr}");
    }

    /**
     * Create a max_digits rule.
     *
     * @param int $value
     * @return static
     */
    public static function maxDigits(int $value): static
    {
        return new static("max_digits:{$value}");
    }

    /**
     * Create a min_digits rule.
     *
     * @param int $value
     * @return static
     */
    public static function minDigits(int $value): static
    {
        return new static("min_digits:{$value}");
    }

    /**
     * Create a lowercase rule.
     *
     * @return static
     */
    public static function lowercase(): static
    {
        return new static('lowercase');
    }

    /**
     * Create an uppercase rule.
     *
     * @return static
     */
    public static function uppercase(): static
    {
        return new static('uppercase');
    }

    /**
     * Add the rule to a rules array.
     *
     * @param array $rules
     * @param string $field
     * @return array
     */
    public function apply(array $rules, string $field): array
    {
        $rules[$field] = $this->toString();
        return $rules;
    }

    /**
     * Get the string representation of the rule.
     *
     * @return string
     */
    public function toString(): string
    {
        return $this->rule;
    }

    /**
     * Convert to string.
     *
     * @return string
     */
    public function __toString(): string
    {
        return $this->rule;
    }

    public function __construct(protected string $rule)
    {
    }
}

/**
 * Closure-based validation rule.
 */
class ClosureRule
{
    protected Closure $callback;

    public function __construct(Closure $callback)
    {
        $this->callback = $callback;
    }

    /**
     * Get the callback.
     *
     * @return Closure
     */
    public function getCallback(): Closure
    {
        return $this->callback;
    }
}
