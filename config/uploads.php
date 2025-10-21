<?php

return [
    'max_chunk_bytes' => env('UPLOAD_MAX_CHUNK_BYTES', 15 * 1024 * 1024),
    'allowed_mimes' => array_map('trim', explode(',', env('AUDIO_ALLOWED_MIMES', 'audio/ogg,audio/webm'))),
];
