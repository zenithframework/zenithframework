<?php

declare(strict_types=1);

namespace Zen\Database;

class Factory
{
    protected string $table;
    protected int $count = 1;
    protected array $definitions = [];
    protected static int $id = 1;

    public function __construct(string $table)
    {
        $this->table = $table;
    }

    public function count(int $count): static
    {
        $this->count = $count;
        return $this;
    }

    public function define(string $column, callable $callback): static
    {
        $this->definitions[$column] = $callback;
        return $this;
    }

    public function create(): array
    {
        $results = [];

        for ($i = 0; $i < $this->count; $i++) {
            $data = $this->generateData();
            $results[] = $this->insert($data);
        }

        return $results;
    }

    protected function generateData(): array
    {
        $data = [];
        $index = self::$id;

        foreach ($this->definitions as $column => $callback) {
            $data[$column] = $callback($index);
        }

        self::$id++;

        return $data;
    }

    protected function insert(array $data): int
    {
        $qb = new QueryBuilder();
        $qb->table($this->table);
        $qb->insert($data);
        return (int) $qb->lastInsertId();
    }

    public static function fake(string $type): mixed
    {
        return match ($type) {
            'name' => self::randomElement([
                'John', 'Jane', 'Bob', 'Alice', 'Charlie', 'Diana', 'Eve', 'Frank',
                'Grace', 'Henry', 'Ivy', 'Jack', 'Kate', 'Leo', 'Mia', 'Noah', 'Olivia',
                'Paul', 'Quinn', 'Rose', 'Sam', 'Tina', 'Uma', 'Victor', 'Wendy', 'Xavier',
                'Yara', 'Zack'
            ]),
            'email' => strtolower(self::fake('name')) . '@example.com',
            'firstName' => self::fake('name'),
            'lastName' => self::randomElement(['Smith', 'Johnson', 'Williams', 'Brown', 'Jones', 'Garcia', 'Miller', 'Davis']),
            'phone' => '+1-' . rand(200, 999) . '-' . rand(100, 999) . '-' . rand(1000, 9999),
            'address' => rand(1, 9999) . ' ' . self::randomElement(['Main', 'Oak', 'Pine', 'Maple', 'Cedar']) . ' ' . self::randomElement(['St', 'Ave', 'Blvd', 'Rd']),
            'city' => self::randomElement(['New York', 'Los Angeles', 'Chicago', 'Houston', 'Phoenix', 'Philadelphia', 'San Antonio', 'San Diego']),
            'state' => self::randomElement(['NY', 'CA', 'IL', 'TX', 'AZ', 'PA', 'FL', 'OH']),
            'zip' => rand(10000, 99999),
            'country' => 'USA',
            'password' => password_hash('password', PASSWORD_DEFAULT),
            'username' => strtolower(self::fake('name')) . rand(1, 999),
            'text' => self::randomElement([
                'Lorem ipsum dolor sit amet.',
                'Consectetur adipiscing elit.',
                'Sed do eiusmod tempor.',
                'Ut enim ad minim veniam.',
                'Quis nostrud exercitation.',
                'Duis aute irure dolor.',
                'Excepteur sint occaecat.',
                'Cupidatat non proident.'
            ]),
            'sentence' => self::randomElement([
                'The quick brown fox jumps over the lazy dog.',
                'A journey of a thousand miles begins with a single step.',
                'To be or not to be, that is the question.',
                'All that glitters is not gold.'
            ]),
            'paragraph' => implode(' ', array_map(fn() => self::fake('sentence'), range(1, 3))),
            'url' => 'https://' . self::randomElement(['example.com', 'test.org', 'demo.net']) . '/' . self::randomElement(['about', 'contact', 'blog', 'news']),
            'ipv4' => rand(1, 255) . '.' . rand(0, 255) . '.' . rand(0, 255) . '.' . rand(0, 255),
            'uuid' => sprintf(
                '%08x-%04x-%04x-%04x-%012x',
                rand(), rand(0, 0xffff), rand(0, 0xffff), rand(0, 0xffff), rand()
            ),
            'boolean' => (bool) rand(0, 1),
            'integer' => rand(1, 1000),
            'float' => rand(1, 1000) + rand(0, 99) / 100,
            'date' => date('Y-m-d', rand(strtotime('1990-01-01'), strtotime('2025-12-31'))),
            'datetime' => date('Y-m-d H:i:s', rand(strtotime('1990-01-01'), strtotime('2025-12-31'))),
            'time' => date('H:i:s', rand(0, 86399)),
            'timestamp' => rand(strtotime('1990-01-01'), strtotime('2025-12-31')),
            'json' => json_encode(['key' => 'value', 'number' => rand(1, 100)]),
            'html' => '<p>' . self::fake('text') . '</p>',
            'imageUrl' => 'https://picsum.photos/' . rand(200, 800) . '/' . rand(200, 800),
            'color' => sprintf('#%06x', rand(0, 0xffffff)),
            'slug' => strtolower(preg_replace('/[^a-z0-9]+/', '-', self::fake('name'))),
            default => null,
        };
    }

    protected static function randomElement(array $array): mixed
    {
        return $array[array_rand($array)];
    }
}
