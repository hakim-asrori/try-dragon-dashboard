<?php

namespace App\Library\PG;

use Illuminate\Support\Facades\{Http, Log};

class HttpExec
{
    public static function hit(?string $url = "", array $request_body = [], array $request_header = [])
    {
        Log::info('Call URL: ' . $url);
        Log::info('Request Header: ' . json_encode($request_header));
        Log::info('Request Body: ' . json_encode($request_body));

        try {
            $response = Http::withHeaders($request_header)
                ->withOptions([
                    'verify' => false,
                    'timeout' => 30,
                ])
                ->post($url, $request_body);

            Log::info('Request Result: ' . $response->body());

            return $response->json();
        } catch (\Exception $e) {
            Log::error('Call URL: ' . $url);
            Log::error('Request Body: ' . json_encode($request_body));
            Log::error('Request Error: ' . $e->getMessage());

            return [
                'status' => false,
                'error_message' => $e->getMessage(),
                'request_body' => $request_body,
            ];
        }
    }
}
