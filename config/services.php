<?php

return [
    'openai' => [
        'key' => env('OPENAI_API_KEY'),
        'transcription_model' => env('OPENAI_TRANSCRIPTION_MODEL', 'gpt-4o-transcribe'),
        'llm_model' => env('OPENAI_LLM_MODEL', 'gpt-5.1'),
        'base_uri' => env('OPENAI_BASE_URI'),
        'timeout' => env('OPENAI_TIMEOUT', 60),
        'max_retries' => env('OPENAI_MAX_RETRIES', 3),
        'retry_delay_ms' => env('OPENAI_RETRY_DELAY_MS', 200),
    ],
];
