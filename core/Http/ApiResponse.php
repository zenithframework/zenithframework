<?php

declare(strict_types=1);

namespace Zen\Http;

use Zen\Database\Model;

class Resource
{
    public function __construct(protected array|Model $resource)
    {
    }

    public function toArray(): array
    {
        if ($this->resource instanceof Model) {
            return $this->resource->toArray();
        }

        return $this->resource;
    }

    public function toJson(int $options = 0): string
    {
        return json_encode($this->toArray(), $options | JSON_THROW_ON_ERROR);
    }

    public static function collection(iterable $resources): ResourceCollection
    {
        return new ResourceCollection($resources, static::class);
    }
}

class ResourceCollection
{
    protected array $items = [];
    protected string $resourceClass;

    public function __construct(iterable $resources, string $resourceClass)
    {
        $this->resourceClass = $resourceClass;

        foreach ($resources as $resource) {
            $this->items[] = new $resourceClass($resource);
        }
    }

    public function toArray(): array
    {
        return array_map(fn($item) => $item->toArray(), $this->items);
    }

    public function toJson(int $options = 0): string
    {
        return json_encode($this->toArray(), $options | JSON_THROW_ON_ERROR);
    }

    public function count(): int
    {
        return count($this->items);
    }

    public function getIterator(): \ArrayIterator
    {
        return new \ArrayIterator($this->items);
    }
}

class ApiResponse
{
    public static function success(mixed $data, ?string $message = null, int $status = 200): Response
    {
        $response = [
            'success' => true,
            'data' => $data,
        ];

        if ($message !== null) {
            $response['message'] = $message;
        }

        return json($response, $status);
    }

    public static function error(mixed $message, int $status = 400, ?array $errors = null): Response
    {
        $response = [
            'success' => false,
            'message' => $message,
        ];

        if ($errors !== null) {
            $response['errors'] = $errors;
        }

        return json($response, $status);
    }

    public static function created(mixed $data, ?string $message = 'Created successfully'): Response
    {
        return self::success($data, $message, 201);
    }

    public static function noContent(): Response
    {
        return response('', 204);
    }

    public static function notFound(?string $message = 'Resource not found'): Response
    {
        return self::error($message, 404);
    }

    public static function unauthorized(?string $message = 'Unauthorized'): Response
    {
        return self::error($message, 401);
    }

    public static function forbidden(?string $message = 'Forbidden'): Response
    {
        return self::error($message, 403);
    }

    public static function validationError(array $errors): Response
    {
        return self::error('Validation failed', 422, $errors);
    }

    public static function paginate(array $items, int $total, int $perPage, int $currentPage): Response
    {
        return json([
            'success' => true,
            'data' => $items,
            'meta' => [
                'total' => $total,
                'per_page' => $perPage,
                'current_page' => $currentPage,
                'last_page' => (int) ceil($total / $perPage),
                'from' => ($currentPage - 1) * $perPage + 1,
                'to' => min($currentPage * $perPage, $total),
            ],
        ]);
    }
}