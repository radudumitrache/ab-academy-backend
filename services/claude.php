<?php

namespace Services;

use Anthropic\Client;
use RuntimeException;

final class ClaudeClientFactory
{
    public static function make(): Client
    {
        $apiKey = (string) config('services.anthropic.key', '');

        if ($apiKey === '') {
            throw new RuntimeException('Anthropic API key is missing. Set ANTHROPIC_API_KEY in your environment.');
        }

        return new Client(
            apiKey: $apiKey,
        );
    }
}