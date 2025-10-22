<?php

return [
    'openai' => [
        'key' => env('OPENAI_API_KEY'),
        'transcription_model' => env('OPENAI_TRANSCRIPTION_MODEL', 'gpt-4o-transcribe'),
        'transcription_response_format' => env('OPENAI_TRANSCRIPTION_RESPONSE_FORMAT', 'json'),
        'llm_model' => env('OPENAI_LLM_MODEL', 'gpt-5.1'),
        'base_uri' => env('OPENAI_BASE_URI'),
        'timeout' => env('OPENAI_TIMEOUT', 60),
        'connect_timeout' => env('OPENAI_CONNECT_TIMEOUT', 10),
        'max_retries' => env('OPENAI_MAX_RETRIES', 3),
        'retry_delay_ms' => env('OPENAI_RETRY_DELAY_MS', 200),
        'chat_temperature' => env('OPENAI_CHAT_TEMPERATURE', 0.2),
        'chat_temperature_descriptions' => env('OPENAI_CHAT_TEMPERATURE_DESCRIPTIONS', 0.4),
        'chat_max_tokens' => env('OPENAI_CHAT_MAX_TOKENS', 1600),
        'chat_max_tokens_descriptions' => env('OPENAI_CHAT_MAX_TOKENS_DESCRIPTIONS', 1200),
        'reasoning_effort' => env('OPENAI_REASONING_EFFORT', 'minimal'),
    ],
];
