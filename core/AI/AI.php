<?php

declare(strict_types=1);

namespace Zenith\AI;

class AI
{
    protected string $provider = 'openai';
    protected string $apiKey = '';
    protected string $model = 'gpt-3.5-turbo';
    protected float $temperature = 0.7;
    protected int $maxTokens = 2048;
    protected array $messages = [];
    protected ?string $baseUrl = null;

    public static function chat(): static
    {
        return new static();
    }

    public function provider(string $provider): static
    {
        $this->provider = $provider;
        return $this;
    }

    public function model(string $model): static
    {
        $this->model = $model;
        return $this;
    }

    public function temperature(float $temperature): static
    {
        $this->temperature = $temperature;
        return $this;
    }

    public function maxTokens(int $maxTokens): static
    {
        $this->maxTokens = $maxTokens;
        return $this;
    }

    public function apiKey(string $apiKey): static
    {
        $this->apiKey = $apiKey;
        return $this;
    }

    public function baseUrl(string $baseUrl): static
    {
        $this->baseUrl = $baseUrl;
        return $this;
    }

    public function withSystemMessage(string $content): static
    {
        $this->messages[] = ['role' => 'system', 'content' => $content];
        return $this;
    }

    public function withUserMessage(string $content): static
    {
        $this->messages[] = ['role' => 'user', 'content' => $content];
        return $this;
    }

    public function withAssistantMessage(string $content): static
    {
        $this->messages[] = ['role' => 'assistant', 'content' => $content];
        return $this;
    }

    public function send(): string
    {
        $this->apiKey = $this->apiKey ?: env('AI_API_KEY', '');

        if (empty($this->apiKey)) {
            throw new \RuntimeException('AI API key is required. Set AI_API_KEY in .env');
        }

        return $this->makeRequest();
    }

    public function json(): array
    {
        $response = $this->send();
        return json_decode($response, true) ?? [];
    }

    public function stream(callable $callback): void
    {
        $this->apiKey = $this->apiKey ?: env('AI_API_KEY', '');

        if (empty($this->apiKey)) {
            throw new \RuntimeException('AI API key is required. Set AI_API_KEY in .env');
        }

        $this->makeStreamRequest($callback);
    }

    protected function makeRequest(): string
    {
        $url = $this->getEndpoint();
        $headers = $this->getHeaders();
        $body = $this->getBody();

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            throw new \RuntimeException("AI request failed with status {$httpCode}: {$response}");
        }

        return $this->parseResponse($response);
    }

    protected function makeStreamRequest(callable $callback): void
    {
        $url = $this->getEndpoint() . '/stream';
        $headers = $this->getHeaders();
        $headers[] = 'Accept: text/event-stream';
        $body = $this->getBody();
        $body['stream'] = true;

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, false);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_WRITEFUNCTION, function ($ch, $data) use ($callback) {
            $callback($data);
            return strlen($data);
        });

        curl_exec($ch);
        curl_close($ch);
    }

    protected function getEndpoint(): string
    {
        if ($this->baseUrl !== null) {
            return $this->baseUrl;
        }

        return match ($this->provider) {
            'openai' => 'https://api.openai.com/v1/chat/completions',
            'anthropic' => 'https://api.anthropic.com/v1/messages',
            'ollama' => 'http://localhost:11434/api/chat',
            default => throw new \RuntimeException("Unknown AI provider: {$this->provider}"),
        };
    }

    protected function getHeaders(): array
    {
        $headers = [
            'Content-Type: application/json',
        ];

        return match ($this->provider) {
            'openai' => array_merge($headers, [
                'Authorization: Bearer ' . $this->apiKey,
            ]),
            'anthropic' => array_merge($headers, [
                'x-api-key: ' . $this->apiKey,
                'anthropic-version: 2023-06-01',
            ]),
            'ollama' => $headers,
            default => $headers,
        };
    }

    protected function getBody(): array
    {
        return match ($this->provider) {
            'openai', 'ollama' => [
                'model' => $this->model,
                'messages' => $this->messages,
                'temperature' => $this->temperature,
                'max_tokens' => $this->maxTokens,
            ],
            'anthropic' => [
                'model' => $this->model,
                'messages' => $this->messages,
                'temperature' => $this->temperature,
                'max_tokens' => $this->maxTokens,
            ],
            default => [],
        };
    }

    protected function parseResponse(string $response): string
    {
        $data = json_decode($response, true);

        return match ($this->provider) {
            'openai' => $data['choices'][0]['message']['content'] ?? '',
            'anthropic' => $data['content'][0]['text'] ?? '',
            'ollama' => $data['message']['content'] ?? '',
            default => '',
        };
    }
}
