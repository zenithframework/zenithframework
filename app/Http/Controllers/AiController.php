<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Zenith\Http\Request;
use Zenith\Http\Response;
use Zenith\AI\AI;

class AiController
{
    public function chat(Request $request): Response
    {
        $prompt = $request->input('prompt', '');
        
        if (empty($prompt)) {
            return json(['error' => 'Prompt is required'], 400);
        }

        $apiKey = config('ai.openai_key') ?? getenv('AI_API_KEY');
        
        if (empty($apiKey)) {
            return json([
                'response' => 'AI is not configured. Set AI_API_KEY in .env or ai.openai_key in config.',
                'note' => 'This is a demo response. Configure your AI provider for real responses.'
            ]);
        }

        try {
            $ai = AI::chat()
                ->provider('openai')
                ->model('gpt-3.5-turbo')
                ->apiKey($apiKey);
            
            $response = $ai->withUserMessage($prompt)->send();
            
            return json(['response' => $response]);
        } catch (\Throwable $e) {
            return json(['error' => $e->getMessage()], 500);
        }
    }

    public function complete(Request $request): Response
    {
        $prompt = $request->input('prompt', '');
        
        if (empty($prompt)) {
            return json(['error' => 'Prompt is required'], 400);
        }

        $apiKey = config('ai.openai_key') ?? getenv('AI_API_KEY');
        
        if (empty($apiKey)) {
            return json([
                'completion' => 'AI is not configured. Set AI_API_KEY in .env for real completions.',
                'note' => 'Demo mode - configure your AI provider for actual completions.'
            ]);
        }

        try {
            $ai = AI::chat()
                ->provider('openai')
                ->model('gpt-3.5-turbo')
                ->apiKey($apiKey);
            
            $response = $ai->send();
            
            return json(['completion' => $response]);
        } catch (\Throwable $e) {
            return json(['error' => $e->getMessage()], 500);
        }
    }
}
