<?php

namespace App\Services;

class TextStitchingService
{
    public function stitchSegments(array $segments): array
    {
        $result = [];
        foreach ($segments as $segment) {
            $text = $segment['text'] ?? '';
            if ($text === '') {
                continue;
            }

            if (! empty($result)) {
                $text = $this->deduplicateOverlap(end($result)['text'], $text);
            }

            $segment['text'] = $text;
            $result[] = $segment;
        }

        return $result;
    }

    protected function deduplicateOverlap(string $previous, string $current): string
    {
        $previousTail = substr($previous, -150);
        $overlap = $this->longestOverlap($previousTail, substr($current, 0, 150));

        if ($overlap === '') {
            return $current;
        }

        return ltrim(substr($current, strlen($overlap)));
    }

    protected function longestOverlap(string $a, string $b): string
    {
        $max = min(strlen($a), strlen($b));
        for ($length = $max; $length >= 3; $length--) {
            $end = substr($a, -$length);
            $start = substr($b, 0, $length);
            if (strcasecmp($end, $start) === 0) {
                return $start;
            }
        }

        return '';
    }
}
