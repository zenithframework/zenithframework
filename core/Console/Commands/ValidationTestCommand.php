<?php

declare(strict_types=1);

namespace Zenith\Console\Commands;

use Zenith\Container;
use Zenith\Validation\Validator;

class ValidationTestCommand extends Command
{
    protected string $name = 'validation:test';

    protected string $description = 'Test validation rules';

    public function handle(Container $container, array $arguments): void
    {
        $this->info('Zen Validation Rule Tester');
        $this->line(str_repeat('-', 60));

        $testCases = $this->getDefaultTestCases();
        $passed = 0;
        $failed = 0;

        foreach ($testCases as $name => $testCase) {
            $validator = Validator::make($testCase['data'], $testCase['rules']);
            $errors = $validator->validate();
            $shouldFail = $testCase['should_fail'] ?? false;

            if ($shouldFail && !empty($errors)) {
                $this->info("  [PASS] {$name}");
                $passed++;
            } elseif (!$shouldFail && empty($errors)) {
                $this->info("  [PASS] {$name}");
                $passed++;
            } else {
                $this->error("  [FAIL] {$name}");

                if (!empty($errors)) {
                    foreach ($errors as $field => $messages) {
                        foreach ($messages as $message) {
                            $this->line("    - {$field}: {$message}");
                        }
                    }
                }

                $failed++;
            }
        }

        $this->line(str_repeat('-', 60));
        $this->info("Results: {$passed} passed, {$failed} failed");
    }

    protected function getDefaultTestCases(): array
    {
        return [
            'required field present' => [
                'data' => ['name' => 'John Doe'],
                'rules' => ['name' => 'required'],
                'should_fail' => false,
            ],
            'required field missing' => [
                'data' => ['name' => ''],
                'rules' => ['name' => 'required'],
                'should_fail' => true,
            ],
            'valid email' => [
                'data' => ['email' => 'test@example.com'],
                'rules' => ['email' => 'required|email'],
                'should_fail' => false,
            ],
            'invalid email' => [
                'data' => ['email' => 'not-an-email'],
                'rules' => ['email' => 'required|email'],
                'should_fail' => true,
            ],
            'min length valid' => [
                'data' => ['password' => 'secretpass'],
                'rules' => ['password' => 'required|min:6'],
                'should_fail' => false,
            ],
            'min length invalid' => [
                'data' => ['password' => 'abc'],
                'rules' => ['password' => 'required|min:6'],
                'should_fail' => true,
            ],
            'max length valid' => [
                'data' => ['title' => 'Short Title'],
                'rules' => ['title' => 'required|max:100'],
                'should_fail' => false,
            ],
            'max length invalid' => [
                'data' => ['title' => str_repeat('a', 101)],
                'rules' => ['title' => 'required|max:100'],
                'should_fail' => true,
            ],
            'numeric valid' => [
                'data' => ['age' => '25'],
                'rules' => ['age' => 'required|numeric'],
                'should_fail' => false,
            ],
            'numeric invalid' => [
                'data' => ['age' => 'not-a-number'],
                'rules' => ['age' => 'required|numeric'],
                'should_fail' => true,
            ],
            'integer valid' => [
                'data' => ['count' => '42'],
                'rules' => ['count' => 'required|integer'],
                'should_fail' => false,
            ],
            'integer invalid' => [
                'data' => ['count' => '3.14'],
                'rules' => ['count' => 'required|integer'],
                'should_fail' => true,
            ],
            'boolean valid' => [
                'data' => ['active' => '1'],
                'rules' => ['active' => 'required|boolean'],
                'should_fail' => false,
            ],
            'url valid' => [
                'data' => ['website' => 'https://example.com'],
                'rules' => ['website' => 'required|url'],
                'should_fail' => false,
            ],
            'url invalid' => [
                'data' => ['website' => 'not-a-url'],
                'rules' => ['website' => 'required|url'],
                'should_fail' => true,
            ],
            'ip valid' => [
                'data' => ['ip' => '192.168.1.1'],
                'rules' => ['ip' => 'required|ip'],
                'should_fail' => false,
            ],
            'ip invalid' => [
                'data' => ['ip' => '999.999.999.999'],
                'rules' => ['ip' => 'required|ip'],
                'should_fail' => true,
            ],
            'alpha valid' => [
                'data' => ['name' => 'John'],
                'rules' => ['name' => 'required|alpha'],
                'should_fail' => false,
            ],
            'alpha invalid' => [
                'data' => ['name' => 'John123'],
                'rules' => ['name' => 'required|alpha'],
                'should_fail' => true,
            ],
            'alphaNum valid' => [
                'data' => ['username' => 'john123'],
                'rules' => ['username' => 'required|alphaNum'],
                'should_fail' => false,
            ],
            'alphaNum invalid' => [
                'data' => ['username' => 'john@123'],
                'rules' => ['username' => 'required|alphaNum'],
                'should_fail' => true,
            ],
            'confirmed valid' => [
                'data' => ['password' => 'secret', 'password_confirmation' => 'secret'],
                'rules' => ['password' => 'required|confirmed'],
                'should_fail' => false,
            ],
            'confirmed invalid' => [
                'data' => ['password' => 'secret', 'password_confirmation' => 'different'],
                'rules' => ['password' => 'required|confirmed'],
                'should_fail' => true,
            ],
            'date valid' => [
                'data' => ['birthday' => '1990-01-01'],
                'rules' => ['birthday' => 'required|date'],
                'should_fail' => false,
            ],
            'date invalid' => [
                'data' => ['birthday' => 'not-a-date'],
                'rules' => ['birthday' => 'required|date'],
                'should_fail' => true,
            ],
            'in rule valid' => [
                'data' => ['status' => 'active'],
                'rules' => ['status' => 'required|in:active,inactive,pending'],
                'should_fail' => false,
            ],
            'in rule invalid' => [
                'data' => ['status' => 'unknown'],
                'rules' => ['status' => 'required|in:active,inactive,pending'],
                'should_fail' => true,
            ],
            'between valid' => [
                'data' => ['score' => '50'],
                'rules' => ['score' => 'required|between:0,100'],
                'should_fail' => false,
            ],
            'between invalid' => [
                'data' => ['score' => '150'],
                'rules' => ['score' => 'required|between:0,100'],
                'should_fail' => true,
            ],
            'match rule valid' => [
                'data' => ['start_date' => '2024-01-01', 'end_date' => '2024-01-01'],
                'rules' => ['end_date' => 'match:start_date'],
                'should_fail' => false,
            ],
            'match rule invalid' => [
                'data' => ['start_date' => '2024-01-01', 'end_date' => '2024-12-31'],
                'rules' => ['end_date' => 'match:start_date'],
                'should_fail' => true,
            ],
            'multiple rules valid' => [
                'data' => ['email' => 'test@example.com', 'password' => 'secretpass123'],
                'rules' => ['email' => 'required|email', 'password' => 'required|min:8'],
                'should_fail' => false,
            ],
        ];
    }
}
