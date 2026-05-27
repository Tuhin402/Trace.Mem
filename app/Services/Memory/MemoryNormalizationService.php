<?php

namespace App\Services\Memory;

use Illuminate\Support\Str;

class MemoryNormalizationService
{
    public function normalize(string $content): string
    {
        $content = preg_replace("/[\x00-\x1F\x7F]/u", ' ', $content); 

        if (class_exists(\Normalizer::class)) {
            $content = \Normalizer::normalize($content, \Normalizer::FORM_C);
        }

        return Str::of($content)
            ->replace(["\r\n", "\r"], "\n")
            ->trim()
            ->squish()
            ->lower()
            ->toString();
    }

    public function hash(string $tenantId, string $userId, string $type, string $normalizedContent): string
    {
        return hash('sha256', $tenantId.'|'.$userId.'|'.$type.'|'.$normalizedContent);
    }
}