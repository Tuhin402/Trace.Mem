<?php

namespace App\Services\Memory;

use Carbon\CarbonImmutable;
use Throwable;

class MemoryTemporalService
{
    private const WEEKDAY_BY_DAY = [
        'monday' => 'MO',
        'tuesday' => 'TU',
        'wednesday' => 'WE',
        'thursday' => 'TH',
        'friday' => 'FR',
        'saturday' => 'SA',
        'sunday' => 'SU',
    ];

    public function extract(string $text, ?CarbonImmutable $reference = null): array
    {
        $reference ??= CarbonImmutable::now(config('app.timezone', 'UTC'));

        $normalized = $this->normalize($text);

        if ($normalized === '') {
            return $this->emptyPayload($reference);
        }

        $scheduleLike = $this->containsScheduleIntent($normalized);

        if ($recurring = $this->detectRecurring($normalized, $reference, $scheduleLike)) {
            return $recurring;
        }

        if ($range = $this->detectRelativeRange($normalized, $reference, $scheduleLike)) {
            return $range;
        }

        if ($weekday = $this->detectRelativeWeekday($normalized, $reference, $scheduleLike)) {
            return $weekday;
        }

        if ($absolute = $this->detectAbsoluteDate($normalized, $reference, $scheduleLike)) {
            return $absolute;
        }

        if ($timeOnly = $this->detectTimeOnly($normalized, $reference, $scheduleLike)) {
            return $timeOnly;
        }

        return $this->emptyPayload($reference, $scheduleLike);
    }

    private function emptyPayload(CarbonImmutable $reference, bool $scheduleLike = false): array
    {
        return [
            'has_temporal' => false,
            'kind' => $scheduleLike ? 'schedule' : 'time_reference',
            'schedule_like' => $scheduleLike,
            'label' => null,
            'source_phrase' => null,
            'start_at' => null,
            'end_at' => null,
            'timezone' => $reference->getTimezone()->getName(),
            'recurrence_rule' => null,
            'confidence' => 0.0,
        ];
    }

    private function detectRecurring(string $text, CarbonImmutable $reference, bool $scheduleLike): ?array
    {
        $patterns = [
            '/\b(?:every\s+day|daily|each\s+day|day-to-day|everyday)\b/ui' => ['FREQ' => 'DAILY'],
            '/\b(?:every\s+week|weekly|each\s+week|week-to-week|7-days)\b/ui' => ['FREQ' => 'WEEKLY'],
            '/\b(?:every\s+month|monthly|each\s+month|month-to-month)\b/ui' => ['FREQ' => 'MONTHLY'],
            '/\b(?:quarterly|every\s+quarter|each\s+quarter|every\s+3\s*months|tri-monthly|q[1-4]\b)/ui' => ['FREQ' => 'QUARTERLY'],
            '/\b(?:half[- ]yearly|semi[- ]annually|bi[- ]annually|every\s+6\s*months|mid[- ]year|twice\s+a\s+year|semesterly|each\s+semester)\b/ui' => ['FREQ' => 'HALF-YEARLY'],
            '/\b(?:every\s+year|yearly|annually|each\s+year|year-on-year|per\s+annum)\b/ui' => ['FREQ' => 'YEARLY'],
        ];

        foreach ($patterns as $pattern => $rule) {
            if (preg_match($pattern, $text) !== 1) {
                continue;
            }

            return [
                'has_temporal' => true,
                'kind' => 'recurring',
                'schedule_like' => true,
                'label' => $this->deriveLabel($text, 'recurring'),
                'source_phrase' => $this->firstMatch($pattern, $text),
                'start_at' => null,
                'end_at' => null,
                'timezone' => $reference->getTimezone()->getName(),
                'recurrence_rule' => $this->buildRRule($rule),
                'confidence' => 0.9,
            ];
        }

        if (preg_match('/\bevery\s+(monday|tuesday|wednesday|thursday|friday|saturday|sunday)\b/u', $text, $m)) {
            $day = strtolower($m[1]);
            $rule = ['FREQ' => 'WEEKLY', 'BYDAY' => self::WEEKDAY_BY_DAY[$day]];

            return [
                'has_temporal' => true,
                'kind' => 'recurring',
                'schedule_like' => true,
                'label' => $this->deriveLabel($text, 'recurring'),
                'source_phrase' => $m[0],
                'start_at' => null,
                'end_at' => null,
                'timezone' => $reference->getTimezone()->getName(),
                'recurrence_rule' => $this->buildRRule($rule),
                'confidence' => 0.92,
            ];
        }

        if (preg_match('/\bevery\s+(weekday|weekdays|weekend|weekends)\b/u', $text, $m)) {
            $rule = match (strtolower($m[1])) {
                'weekday', 'weekdays' => ['FREQ' => 'WEEKLY', 'BYDAY' => 'MO,TU,WE,TH,FR'],
                default => ['FREQ' => 'WEEKLY', 'BYDAY' => 'SA,SU'],
            };

            return [
                'has_temporal' => true,
                'kind' => 'recurring',
                'schedule_like' => true,
                'label' => $this->deriveLabel($text, 'recurring'),
                'source_phrase' => $m[0],
                'start_at' => null,
                'end_at' => null,
                'timezone' => $reference->getTimezone()->getName(),
                'recurrence_rule' => $this->buildRRule($rule),
                'confidence' => 0.88,
            ];
        }

        return null;
    }

    private function detectRelativeRange(string $text, CarbonImmutable $reference, bool $scheduleLike): ?array
    {
        $map = [
            'this week' => [$reference->startOfWeek(), $reference->endOfWeek()],
            'next week' => [
                $reference->addWeek()->startOfWeek(),
                $reference->addWeek()->endOfWeek(),
            ],
            'last week' => [
                $reference->subWeek()->startOfWeek(),
                $reference->subWeek()->endOfWeek(),
            ],
            'this month' => [$reference->startOfMonth(), $reference->endOfMonth()],
            'next month' => [
                $reference->addMonthNoOverflow()->startOfMonth(),
                $reference->addMonthNoOverflow()->endOfMonth(),
            ],
            'last month' => [
                $reference->subMonthNoOverflow()->startOfMonth(),
                $reference->subMonthNoOverflow()->endOfMonth(),
            ],
            'this year' => [$reference->startOfYear(), $reference->endOfYear()],
            'next year' => [
                $reference->addYear()->startOfYear(),
                $reference->addYear()->endOfYear(),
            ],
            'last year' => [
                $reference->subYear()->startOfYear(),
                $reference->subYear()->endOfYear(),
            ],
            'today' => [$reference->startOfDay(), $reference->endOfDay()],
            'tomorrow' => [
                $reference->addDay()->startOfDay(),
                $reference->addDay()->endOfDay(),
            ],
            'yesterday' => [
                $reference->subDay()->startOfDay(),
                $reference->subDay()->endOfDay(),
            ],
        ];

        foreach ($map as $phrase => [$start, $end]) {
            if (str_contains($text, $phrase)) {
                return [
                    'has_temporal' => true,
                    'kind' => str_contains($phrase, 'week') || str_contains($phrase, 'month') || str_contains($phrase, 'year')
                        ? 'range'
                        : 'event',
                    'schedule_like' => $scheduleLike,
                    'label' => $this->deriveLabel($text, 'range'),
                    'source_phrase' => $phrase,
                    'start_at' => $start->toIso8601String(),
                    'end_at' => $end->toIso8601String(),
                    'timezone' => $reference->getTimezone()->getName(),
                    'recurrence_rule' => null,
                    'confidence' => 0.88,
                ];
            }
        }

        return null;
    }

    private function detectRelativeWeekday(string $text, CarbonImmutable $reference, bool $scheduleLike): ?array
    {
        if (! preg_match('/\b(last|this|next)\s+(monday|tuesday|wednesday|thursday|friday|saturday|sunday)\b/u', $text, $m)) {
            return null;
        }

        try {
            $phrase = $m[1] . ' ' . $m[2];
            $date = CarbonImmutable::parse($phrase, $reference->getTimezone());

            return [
                'has_temporal' => true,
                'kind' => $scheduleLike ? 'schedule' : 'event',
                'schedule_like' => $scheduleLike,
                'label' => $this->deriveLabel($text, 'weekday'),
                'source_phrase' => $m[0],
                'start_at' => $date->startOfDay()->toIso8601String(),
                'end_at' => $date->endOfDay()->toIso8601String(),
                'timezone' => $reference->getTimezone()->getName(),
                'recurrence_rule' => null,
                'confidence' => 0.94,
            ];
        } catch (Throwable) {
            return null;
        }
    }

    private function detectAbsoluteDate(string $text, CarbonImmutable $reference, bool $scheduleLike): ?array
    {
        $patterns = [
            // 15 June 2026, 15th June, June 15, June 15 2026
            '/\b(?:(\d{1,2})(?:st|nd|rd|th)?\s+(january|february|march|april|may|june|july|august|september|october|november|december)(?:\s+(\d{4}))?|(january|february|march|april|may|june|july|august|september|october|november|december)\s+(\d{1,2})(?:st|nd|rd|th)?(?:\s+(\d{4}))?)\b/u',
            // 2026-06-15
            '/\b(\d{4}-\d{2}-\d{2})\b/u',
            // 15/06/2026 or 15-06-2026
            '/\b(\d{1,2}[\/\-]\d{1,2}[\/\-]\d{2,4})\b/u',
        ];

        foreach ($patterns as $pattern) {
            if (! preg_match($pattern, $text, $m)) {
                continue;
            }

            $candidate = $m[1] ?? $m[0];

            try {
                $date = CarbonImmutable::parse($candidate, $reference->getTimezone());

                return [
                    'has_temporal' => true,
                    'kind' => $scheduleLike ? 'event' : 'time_reference',
                    'schedule_like' => $scheduleLike,
                    'label' => $this->deriveLabel($text, 'absolute'),
                    'source_phrase' => $candidate,
                    'start_at' => $date->startOfDay()->toIso8601String(),
                    'end_at' => $date->endOfDay()->toIso8601String(),
                    'timezone' => $reference->getTimezone()->getName(),
                    'recurrence_rule' => null,
                    'confidence' => 0.93,
                ];
            } catch (Throwable) {
                // keep trying other patterns
            }
        }

        return null;
    }

    private function detectTimeOnly(string $text, CarbonImmutable $reference, bool $scheduleLike): ?array
    {
        if (! preg_match('/\b(\d{1,2})(?::(\d{2}))?\s*(am|pm)\b/u', $text, $m)) {
            return null;
        }

        try {
            $time = trim($m[0]);
            $dateTime = CarbonImmutable::parse($reference->format('Y-m-d') . ' ' . $time, $reference->getTimezone());

            return [
                'has_temporal' => true,
                'kind' => $scheduleLike ? 'event' : 'time_reference',
                'schedule_like' => $scheduleLike,
                'label' => $this->deriveLabel($text, 'time'),
                'source_phrase' => $time,
                'start_at' => $dateTime->toIso8601String(),
                'end_at' => $dateTime->addHour()->toIso8601String(),
                'timezone' => $reference->getTimezone()->getName(),
                'recurrence_rule' => null,
                'confidence' => 0.82,
            ];
        } catch (Throwable) {
            return null;
        }
    }

    private function containsScheduleIntent(string $text): bool
    {
        $keywords = [
            'meeting', 'meetings', 'seminar', 'seminars', 'presentation', 'presentations',
            'routine', 'routines', 'schedule', 'schedules', 'appointment', 'appointments',
            'event', 'events', 'workshop', 'workshops', 'call', 'calls',
            'deadline', 'deadlines', 'plan', 'plans', 'trip', 'trip', 'reminder', 'reminders',
        ];

        foreach ($keywords as $keyword) {
            if (str_contains($text, $keyword)) {
                return true;
            }
        }

        return false;
    }

    private function buildRRule(array $rule): string
    {
        $parts = [];

        foreach ($rule as $key => $value) {
            $parts[] = $key . '=' . $value;
        }

        return implode(';', $parts);
    }

    private function firstMatch(string $pattern, string $text): ?string
    {
        if (preg_match($pattern, $text, $m)) {
            return $m[0];
        }

        return null;
    }

    private function deriveLabel(string $text, string $kind): string
    {
        return match ($kind) {
            'recurring' => 'Recurring schedule',
            'range' => 'Time range',
            'weekday' => 'Specific day',
            'absolute' => 'Dated memory',
            'time' => 'Timed memory',
            default => 'Temporal memory',
        };
    }

    private function normalize(string $text): string
    {
        if (class_exists(\Normalizer::class)) {
            $normalized = \Normalizer::normalize($text, \Normalizer::FORM_C);
            if ($normalized !== false) {
                $text = $normalized;
            }
        }

        $text = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', ' ', $text) ?? $text;
        $text = preg_replace('/\s+/u', ' ', $text) ?? $text;

        return trim(mb_strtolower($text, 'UTF-8'));
    }
}