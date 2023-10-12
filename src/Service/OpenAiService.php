<?php

namespace App\Service;

use Symfony\Component\HttpClient\HttpClient;

class OpenAiService
{

    public function generateResponse(array $message): array
    {
        // Get your API key from your environment
        $yourApiKey = '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';
        $httpClient = HttpClient::create();

        // Create a completion request
        $response = $httpClient->request('POST', 'https://api.openai.com/v1/chat/completions', [
            'headers' => [
                'Authorization' => 'Bearer ' . $yourApiKey,
                'Content-Type' => 'application/json',
            ],
            'json' => [
                'model' => "gpt-3.5-turbo",
                'messages' => $message,
            ],
        ]);

        $responseData = $response->toArray();
        // Get the generated response text from the completion
        return $responseData['choices'][0]['message'];
    }}

?>