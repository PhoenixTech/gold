<?php

namespace App\Services;

use Exception;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SmsService
{
    public function send(?string $text, string $number, array $args = []): bool
    {
        $url = config('app.sms.url');
        if (empty($url)) {
            return false;
        }

        if (config('app.sms.driver') === 'Kavenegar') {
            $tokenUrl = str_replace('TOKEN', (string) config('app.sms.token'), $url).'?'.http_build_query($args);
            $response = Http::get($tokenUrl);
            $r = json_decode($response->body(), true);
            if (! isset($r['return']['status']) || $r['return']['status'] != 200) {
                Log::error($r ?? []);

                return false;
            }

            return true;
        }

        $formattedText = (string) $text;
        foreach ($args as $k => $arg) {
            $formattedText = str_replace('%'.$k, (string) $arg, $formattedText);
        }

        $fields = [
            'user' => $url,
            'password' => config('app.sms.password'),
            'to' => $number,
            'from' => config('app.sms.number'),
            'text' => $formattedText,
            'isflash' => 'false',
        ];

        $client = new Client;

        try {
            $client->post($url, [
                'form_params' => $fields,
                'headers' => [
                    'Content-Type' => 'application/x-www-form-urlencoded',
                    'Cache-Control' => 'no-cache',
                ],
            ]);
        } catch (Exception $e) {
            Log::error($e->getMessage());

            return false;
        }

        return true;
    }
}
