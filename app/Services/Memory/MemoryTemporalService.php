<?php

namespace App\Services\Memory;

use Carbon\CarbonImmutable;
use Throwable;

class MemoryTemporalService
{
    // ─────────────────────────────────────────────────────────────
    //  Constants — Weekday mappings
    // ─────────────────────────────────────────────────────────────

    private const WEEKDAY_BY_DAY = [
        'monday' => 'MO',
        'tuesday' => 'TU',
        'wednesday' => 'WE',
        'thursday' => 'TH',
        'friday' => 'FR',
        'saturday' => 'SA',
        'sunday' => 'SU',
    ];

    private const WEEKDAY_TO_ISO = [
        'monday' => 1, 'tuesday' => 2, 'wednesday' => 3, 'thursday' => 4,
        'friday' => 5, 'saturday' => 6, 'sunday' => 7,
    ];

    private const WEEKDAY_ALIASES = [
        'mon' => 'monday', 'tue' => 'tuesday', 'tues' => 'tuesday',
        'wed' => 'wednesday', 'thu' => 'thursday', 'thur' => 'thursday',
        'thurs' => 'thursday', 'fri' => 'friday', 'sat' => 'saturday',
        'sun' => 'sunday',
    ];

    // ─────────────────────────────────────────────────────────────
    //  Constants — Month mappings
    // ─────────────────────────────────────────────────────────────

    private const MONTH_NAMES = [
        'january' => 1, 'february' => 2, 'march' => 3, 'april' => 4,
        'may' => 5, 'june' => 6, 'july' => 7, 'august' => 8,
        'september' => 9, 'october' => 10, 'november' => 11, 'december' => 12,
    ];

    private const MONTH_ALIASES = [
        'jan' => 'january', 'feb' => 'february', 'mar' => 'march',
        'apr' => 'april', 'jun' => 'june', 'jul' => 'july',
        'aug' => 'august', 'sep' => 'september', 'sept' => 'september',
        'oct' => 'october', 'nov' => 'november', 'dec' => 'december',
    ];

    // ─────────────────────────────────────────────────────────────
    //  Constants — Number / ordinal word mappings
    // ─────────────────────────────────────────────────────────────

    private const WORD_TO_NUMBER = [
        'a' => 1, 'an' => 1, 'one' => 1, 'two' => 2, 'three' => 3,
        'four' => 4, 'five' => 5, 'six' => 6, 'seven' => 7,
        'eight' => 8, 'nine' => 9, 'ten' => 10, 'eleven' => 11,
        'twelve' => 12, 'thirteen' => 13, 'fourteen' => 14, 'fifteen' => 15,
        'sixteen' => 16, 'seventeen' => 17, 'eighteen' => 18, 'nineteen' => 19,
        'twenty' => 20, 'thirty' => 30, 'forty' => 40, 'fifty' => 50,
    ];

    private const ORDINAL_WORDS = [
        'first' => 1, 'second' => 2, 'third' => 3, 'fourth' => 4,
        'fifth' => 5, 'sixth' => 6, 'seventh' => 7, 'eighth' => 8,
        'ninth' => 9, 'tenth' => 10, 'last' => -1,
    ];

    // ─────────────────────────────────────────────────────────────
    //  Constants — Regex building blocks (reusable fragments)
    // ─────────────────────────────────────────────────────────────

    /** Weekday names including common abbreviations */
    private const WEEKDAY_RE = 'monday|tuesday|wednesday|thursday|friday|saturday|sunday|mon|tues?|wed|thur?s?|fri|sat|sun';

    /** Month names including common abbreviations */
    private const MONTH_RE = 'january|february|march|april|may|june|july|august|september|october|november|december|jan|feb|mar|apr|jun|jul|aug|sept?|oct|nov|dec';

    /** Number words (cardinals) and digits */
    private const NUMBER_WORD_RE = 'a|an|one|two|three|four|five|six|seven|eight|nine|ten|eleven|twelve|thirteen|fourteen|fifteen|twenty|thirty|\d+';

    /** Ordinal words and digits */
    private const ORDINAL_WORD_RE = 'first|second|third|fourth|fifth|sixth|seventh|eighth|ninth|tenth|last|\d+';

    /** Time unit words (singular/plural/abbreviations) */
    private const TIME_UNIT_RE = 'minutes?|mins?|hours?|hrs?|days?|nights?|weeks?|wks?|months?|years?|yrs?';

    // ═════════════════════════════════════════════════════════════
    //  PUBLIC — Main entry point
    // ═════════════════════════════════════════════════════════════

    public function extract(string $text, ?CarbonImmutable $reference = null): array
    {
        $reference ??= CarbonImmutable::now(config('app.timezone', 'UTC'));

        $normalized = $this->normalize($text);

        if ($normalized === '') {
            return $this->emptyPayload($reference);
        }

        $scheduleLike = $this->containsScheduleIntent($normalized);

        // ── Detection chain (most specific → most general) ───────

        if ($recurring = $this->detectRecurring($normalized, $reference, $scheduleLike)) {
            return $this->finalize($normalized, $recurring, $reference);
        }

        if ($offset = $this->detectRelativeOffset($normalized, $reference, $scheduleLike)) {
            return $this->finalize($normalized, $offset, $reference);
        }

        if ($ordinal = $this->detectOrdinalWeekDay($normalized, $reference, $scheduleLike)) {
            return $this->finalize($normalized, $ordinal, $reference);
        }

        if ($informal = $this->detectInformalRelative($normalized, $reference, $scheduleLike)) {
            return $this->finalize($normalized, $informal, $reference);
        }

        if ($range = $this->detectRelativeRange($normalized, $reference, $scheduleLike)) {
            return $this->finalize($normalized, $range, $reference);
        }

        if ($weekday = $this->detectRelativeWeekday($normalized, $reference, $scheduleLike)) {
            return $this->finalize($normalized, $weekday, $reference);
        }

        if ($absolute = $this->detectAbsoluteDate($normalized, $reference, $scheduleLike)) {
            return $this->finalize($normalized, $absolute, $reference);
        }

        if ($timeRange = $this->detectTimeRange($normalized, $reference, $scheduleLike)) {
            return $this->finalize($normalized, $timeRange, $reference);
        }

        if ($timeOnly = $this->detectTimeOnly($normalized, $reference, $scheduleLike)) {
            return $this->finalize($normalized, $timeOnly, $reference);
        }

        if ($season = $this->detectSeasonHoliday($normalized, $reference, $scheduleLike)) {
            return $this->finalize($normalized, $season, $reference);
        }

        return $this->emptyPayload($reference, $scheduleLike);
    }

    // ═════════════════════════════════════════════════════════════
    //  PUBLIC — Query-level temporal classification
    // ═════════════════════════════════════════════════════════════

    /**
     * Classify a query string to determine its temporal intent.
     *
     * Used by MemoryContextAssemblyService to adapt scoring weights
     * when the query is schedule-like, event-like, recurring, etc.
     *
     * @return array{is_schedule_like: bool, is_event_like: bool, is_recurring: bool, is_time_range: bool, is_exact_date: bool, is_future_oriented: bool, temporal_extract: array, intent_strength: float}
     */
    public function classifyQuery(string $text, ?CarbonImmutable $reference = null): array
    {
        $reference ??= CarbonImmutable::now(config('app.timezone', 'UTC'));

        $normalized = $this->normalize($text);
        $temporal   = $this->extract($text, $reference);

        $isScheduleLike = $this->containsScheduleIntent($normalized);

        // ── Recurring intent: query asks about repeating patterns ──
        $isRecurring = (bool) preg_match(
            '/\b(?:every|daily|weekly|monthly|yearly|annually|recurring|repeating|routine|each\s+(?:day|week|month|year))\b/ui',
            $normalized
        );

        // ── Future-oriented: query looks ahead ──────────────────────
        $isFutureOriented = (bool) preg_match(
            '/\b(?:upcoming|coming\s+up|next|future|later|ahead|what(?:\'s|\s+is|\s+are)\s+(?:coming|planned|scheduled|happening)|what\s+do\s+i\s+have|plans?\s+for|scheduled\s+(?:for|in|this|next))\b/ui',
            $normalized
        );

        // ── Event-like: query targets specific events ───────────────
        $eventKeywords = [
            'meeting', 'meetings', 'appointment', 'appointments',
            'event', 'events', 'deadline', 'deadlines',
            'exam', 'exams', 'interview', 'interviews',
            'flight', 'trip', 'doctor', 'dentist',
            'call', 'standup', 'demo', 'review',
            'birthday', 'anniversary', 'wedding', 'party',
            'class', 'lecture', 'workshop', 'seminar',
            'concert', 'dinner', 'lunch', 'brunch',
        ];
        $isEventLike = false;
        foreach ($eventKeywords as $kw) {
            if (preg_match('/\b' . preg_quote($kw, '/') . '\b/u', $normalized)) {
                $isEventLike = true;
                break;
            }
        }

        // ── Derive exact-date vs time-range from temporal extract ───
        $kind        = $temporal['kind'] ?? null;
        $hasTemporal = $temporal['has_temporal'] ?? false;

        $isExactDate = $hasTemporal && in_array($kind, ['event', 'time_reference', 'schedule'], true)
            && !empty($temporal['start_at'])
            && (
                empty($temporal['end_at'])
                || $this->isSameDay($temporal['start_at'], $temporal['end_at'])
            );

        $isTimeRange = $hasTemporal && (
            $kind === 'range'
            || (
                !empty($temporal['start_at'])
                && !empty($temporal['end_at'])
                && !$this->isSameDay($temporal['start_at'], $temporal['end_at'])
            )
        );

        // ── Compute intent strength (0.0–1.0) ──────────────────────
        $strength = 0.0;
        if ($isScheduleLike)   { $strength += 0.35; }
        if ($hasTemporal)      { $strength += 0.25; }
        if ($isFutureOriented) { $strength += 0.15; }
        if ($isEventLike)      { $strength += 0.15; }
        if ($isRecurring)      { $strength += 0.10; }
        $strength = round(min(1.0, $strength), 4);

        return [
            'is_schedule_like'   => $isScheduleLike,
            'is_event_like'      => $isEventLike,
            'is_recurring'       => $isRecurring,
            'is_time_range'      => $isTimeRange,
            'is_exact_date'      => $isExactDate,
            'is_future_oriented' => $isFutureOriented,
            'temporal_extract'   => $temporal,
            'intent_strength'    => $strength,
        ];
    }

    /**
     * Helper: check whether two ISO date strings fall on the same calendar day.
     */
    private function isSameDay(?string $a, ?string $b): bool
    {
        if (blank($a) || blank($b)) {
            return false;
        }

        try {
            return CarbonImmutable::parse($a)->isSameDay(CarbonImmutable::parse($b));
        } catch (Throwable) {
            return false;
        }
    }

    // ═════════════════════════════════════════════════════════════
    //  PRIVATE — Empty payload helper
    // ═════════════════════════════════════════════════════════════

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

    // ═════════════════════════════════════════════════════════════
    //  PRIVATE — Normalization
    // ═════════════════════════════════════════════════════════════

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

        $text = trim(mb_strtolower($text, 'UTF-8'));

        // ── Expand unambiguous text-speak abbreviations ──────────
        $textSpeak = [
            '/\btmrw\b/u'     => 'tomorrow',
            '/\btmr\b/u'      => 'tomorrow',
            '/\b2morrow\b/u'  => 'tomorrow',
            '/\b2mrw\b/u'     => 'tomorrow',
            '/\btdy\b/u'      => 'today',
            '/\b2day\b/u'     => 'today',
            '/\byday\b/u'     => 'yesterday',
            '/\b2nite\b/u'    => 'tonight',
            '/\btonite\b/u'   => 'tonight',
            '/\bnxt\b/u'      => 'next',
            '/\bprev\b/u'     => 'previous',
            '/\btom\b/u'      => 'tomorrow',
        ];

        foreach ($textSpeak as $pattern => $replacement) {
            $text = preg_replace($pattern, $replacement, $text) ?? $text;
        }

        // ── Strip ordinal suffixes: 1st → 1, 2nd → 2 ────────────
        $text = preg_replace('/\b(\d+)(?:st|nd|rd|th)\b/u', '$1', $text) ?? $text;

        // ── Clean up extra spaces from replacements ──────────────
        $text = preg_replace('/\s+/u', ' ', $text) ?? $text;

        return trim($text);
    }

    // ═════════════════════════════════════════════════════════════
    //  PRIVATE — Detection chain methods
    // ═════════════════════════════════════════════════════════════

    // ─────────────────────────────────────────────────────────────
    //  1. detectRecurring
    // ─────────────────────────────────────────────────────────────

    private function detectRecurring(string $text, CarbonImmutable $reference, bool $scheduleLike): ?array
    {
        // ── Static frequency patterns ────────────────────────────
        $patterns = [
            '/\b(?:every\s+day|daily|each\s+day|day-to-day|everyday)\b/ui'
                => ['FREQ' => 'DAILY'],
            '/\b(?:every\s+week|weekly|each\s+week|week-to-week|7-days)\b/ui'
                => ['FREQ' => 'WEEKLY'],
            '/\b(?:every\s+month|monthly|each\s+month|month-to-month)\b/ui'
                => ['FREQ' => 'MONTHLY'],
            '/\b(?:quarterly|every\s+quarter|each\s+quarter|every\s+3\s*months|tri-monthly|q[1-4]\b)/ui'
                => ['FREQ' => 'QUARTERLY'],
            '/\b(?:half[- ]yearly|semi[- ]annually|bi[- ]annually|every\s+6\s*months|mid[- ]year|twice\s+a\s+year|semesterly|each\s+semester)\b/ui'
                => ['FREQ' => 'HALF-YEARLY'],
            '/\b(?:every\s+year|yearly|annually|each\s+year|year-on-year|per\s+annum)\b/ui'
                => ['FREQ' => 'YEARLY'],

            // New: bi-patterns
            '/\b(?:every\s+other\s+day|alternate\s+days?|on\s+alternate\s+days?)\b/ui'
                => ['FREQ' => 'DAILY', 'INTERVAL' => '2'],
            '/\b(?:every\s+other\s+week|biweekly|bi-weekly|bi\s+weekly|fortnightly|every\s+fortnight)\b/ui'
                => ['FREQ' => 'WEEKLY', 'INTERVAL' => '2'],
            '/\b(?:every\s+other\s+month|bimonthly|bi-monthly|bi\s+monthly)\b/ui'
                => ['FREQ' => 'MONTHLY', 'INTERVAL' => '2'],
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

        // ── Every N units: "every 2 weeks", "every 3 months" ─────
        $numberRe = self::NUMBER_WORD_RE;
        $unitRe = self::TIME_UNIT_RE;

        if (preg_match('/\bevery\s+(' . $numberRe . ')\s+(' . $unitRe . ')\b/u', $text, $m)) {
            $interval = $this->wordToNumber($m[1]);
            $unit = $this->normalizeTimeUnit($m[2]);

            $freq = match ($unit) {
                'minute' => 'MINUTELY',
                'hour'   => 'HOURLY',
                'day'    => 'DAILY',
                'week'   => 'WEEKLY',
                'month'  => 'MONTHLY',
                'year'   => 'YEARLY',
                default  => null,
            };

            if ($freq && $interval > 1) {
                return [
                    'has_temporal' => true,
                    'kind' => 'recurring',
                    'schedule_like' => true,
                    'label' => $this->deriveLabel($text, 'recurring'),
                    'source_phrase' => $m[0],
                    'start_at' => null,
                    'end_at' => null,
                    'timezone' => $reference->getTimezone()->getName(),
                    'recurrence_rule' => $this->buildRRule(['FREQ' => $freq, 'INTERVAL' => (string) $interval]),
                    'confidence' => 0.88,
                ];
            }
        }

        // ── Every specific weekday: "every monday" ───────────────
        $weekdayRe = self::WEEKDAY_RE;

        if (preg_match('/\bevery\s+other\s+(' . $weekdayRe . ')\b/u', $text, $m)) {
            $day = $this->resolveWeekday($m[1]);
            $rule = ['FREQ' => 'WEEKLY', 'INTERVAL' => '2', 'BYDAY' => self::WEEKDAY_BY_DAY[$day]];

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
                'confidence' => 0.91,
            ];
        }

        if (preg_match('/\bevery\s+(' . $weekdayRe . ')\b/u', $text, $m)) {
            $day = $this->resolveWeekday($m[1]);
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

        // ── Every weekday / weekend ──────────────────────────────
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

    // ─────────────────────────────────────────────────────────────
    //  2. detectRelativeOffset — "in 3 days", "after 2 weeks"
    // ─────────────────────────────────────────────────────────────

    private function detectRelativeOffset(string $text, CarbonImmutable $reference, bool $scheduleLike): ?array
    {
        $numberRe = self::NUMBER_WORD_RE;
        $unitRe = self::TIME_UNIT_RE;

        $patterns = [
            // "in 3 days", "after 2 weeks", "within a month"
            '/\b(?:in|after|within)\s+(' . $numberRe . ')\s+(' . $unitRe . ')\b/u',
            // "3 days from now", "a week from today"
            '/\b(' . $numberRe . ')\s+(' . $unitRe . ')\s+from\s+(?:now|today|here)\b/u',
        ];

        foreach ($patterns as $pattern) {
            if (! preg_match($pattern, $text, $m)) {
                continue;
            }

            $amount = $this->wordToNumber($m[1]);
            $unit = $this->normalizeTimeUnit($m[2]);

            $date = match ($unit) {
                'minute' => $reference->addMinutes($amount),
                'hour'   => $reference->addHours($amount),
                'day'    => $reference->addDays($amount),
                'week'   => $reference->addWeeks($amount),
                'month'  => $reference->addMonthsNoOverflow($amount),
                'year'   => $reference->addYears($amount),
                default  => null,
            };

            if (! $date) {
                continue;
            }

            $isSmallUnit = in_array($unit, ['minute', 'hour']);

            return [
                'has_temporal' => true,
                'kind' => $scheduleLike ? 'event' : ($isSmallUnit ? 'event' : 'time_reference'),
                'schedule_like' => $scheduleLike,
                'label' => $this->deriveLabel($text, 'offset'),
                'source_phrase' => $m[0],
                'start_at' => $isSmallUnit
                    ? $date->toIso8601String()
                    : $date->startOfDay()->toIso8601String(),
                'end_at' => $isSmallUnit
                    ? $date->addHour()->toIso8601String()
                    : $date->endOfDay()->toIso8601String(),
                'timezone' => $reference->getTimezone()->getName(),
                'recurrence_rule' => null,
                'confidence' => 0.88,
            ];
        }

        return null;
    }

    // ─────────────────────────────────────────────────────────────
    //  3. detectOrdinalWeekDay — "2nd week's 2nd day of this month"
    // ─────────────────────────────────────────────────────────────

    private function detectOrdinalWeekDay(string $text, CarbonImmutable $reference, bool $scheduleLike): ?array
    {
        $ordRe = self::ORDINAL_WORD_RE;
        $weekdayRe = self::WEEKDAY_RE;
        $monthRe = self::MONTH_RE;

        // Pattern: [ordinal] week['s] [ordinal day | weekday] [of [month]]
        $pattern = '/\b(' . $ordRe . ')\s*week[\']?s?\s*(?:(' . $ordRe . ')\s*day\b|(' . $weekdayRe . '))?\s*(?:of\s+)?(?:(this|that|current|next|last|coming|previous)\s+month|(' . $monthRe . ')(?:\s+(\d{4}))?)?/u';

        if (! preg_match($pattern, $text, $m)) {
            return null;
        }

        // ── Parse week ordinal ───────────────────────────────────
        $weekOrd = $this->parseOrdinal($m[1]);

        if ($weekOrd === 0 || ($weekOrd > 6 && $weekOrd !== -1)) {
            return null;
        }

        // ── Parse target day ─────────────────────────────────────
        $dayIsWeekdayName = false;
        $targetDayIso = 1; // default Monday

        if (! empty($m[3])) {
            // Weekday name provided
            $dayIsWeekdayName = true;
            $resolved = $this->resolveWeekday($m[3]);
            $targetDayIso = self::WEEKDAY_TO_ISO[$resolved] ?? 1;
        } elseif (! empty($m[2])) {
            // Numeric day ordinal (1 = first day, 7 = seventh day)
            $targetDayOrd = $this->parseOrdinal($m[2]);
            $targetDayIso = max(1, min(7, $targetDayOrd));
        }

        // ── Resolve target month ─────────────────────────────────
        $monthRef = ! empty($m[4]) ? strtolower($m[4]) : null;
        $monthName = ! empty($m[5]) ? $m[5] : null;
        $explicitYear = ! empty($m[6]) ? (int) $m[6] : null;

        if ($monthName) {
            $monthNum = $this->monthToNumber($monthName);
            if (! $monthNum) {
                return null;
            }
            $targetYear = $explicitYear ?? $reference->year;

            try {
                $monthStart = CarbonImmutable::create($targetYear, $monthNum, 1, 0, 0, 0, $reference->getTimezone());
            } catch (Throwable) {
                return null;
            }
        } elseif (in_array($monthRef, ['next', 'coming'], true)) {
            $monthStart = $reference->addMonthNoOverflow()->startOfMonth();
        } elseif (in_array($monthRef, ['last', 'previous'], true)) {
            $monthStart = $reference->subMonthNoOverflow()->startOfMonth();
        } else {
            $monthStart = $reference->startOfMonth();
        }

        // ── Calculate date ───────────────────────────────────────
        try {
            if ($dayIsWeekdayName) {
                $date = $this->findNthWeekdayInMonth($monthStart, $targetDayIso, $weekOrd);
            } else {
                // Simple arithmetic: (weekOrd - 1) * 7 + dayNumber
                if ($weekOrd === -1) {
                    // "last week" with numeric day → last 7 days of month
                    $lastDay = $monthStart->endOfMonth()->day;
                    $dayOfMonth = max(1, $lastDay - 7 + $targetDayIso);
                } else {
                    $dayOfMonth = ($weekOrd - 1) * 7 + $targetDayIso;
                }
                $dayOfMonth = max(1, min($dayOfMonth, $monthStart->daysInMonth));
                $date = $monthStart->setDay($dayOfMonth);
            }

            if (! $date) {
                return null;
            }

            // Year inference for month names without year
            if ($monthName && ! $explicitYear) {
                $date = $this->inferYear($date, $reference, $scheduleLike);
            }
        } catch (Throwable) {
            return null;
        }

        return [
            'has_temporal' => true,
            'kind' => $scheduleLike ? 'event' : 'time_reference',
            'schedule_like' => $scheduleLike,
            'label' => $this->deriveLabel($text, 'ordinal'),
            'source_phrase' => trim($m[0]),
            'start_at' => $date->startOfDay()->toIso8601String(),
            'end_at' => $date->endOfDay()->toIso8601String(),
            'timezone' => $reference->getTimezone()->getName(),
            'recurrence_rule' => null,
            'confidence' => 0.90,
        ];
    }

    // ─────────────────────────────────────────────────────────────
    //  4. detectRelativeRange — "today", "this week", "tonight"
    // ─────────────────────────────────────────────────────────────

    private function detectRelativeRange(string $text, CarbonImmutable $reference, bool $scheduleLike): ?array
    {
        // Pre-compute weekend dates
        $dow = $reference->dayOfWeek; // 0=Sun … 6=Sat

        if ($dow === CarbonImmutable::SATURDAY) {
            $thisWeekendStart = $reference->startOfDay();
            $thisWeekendEnd = $reference->addDay()->endOfDay();
        } elseif ($dow === CarbonImmutable::SUNDAY) {
            $thisWeekendStart = $reference->subDay()->startOfDay();
            $thisWeekendEnd = $reference->endOfDay();
        } else {
            $daysToSat = CarbonImmutable::SATURDAY - $dow;
            $thisWeekendStart = $reference->addDays($daysToSat)->startOfDay();
            $thisWeekendEnd = $thisWeekendStart->addDay()->endOfDay();
        }

        $map = [
            // ── Existing relative ranges (preserved) ─────────────
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

            // ── New: time-of-day ranges ──────────────────────────
            'tonight' => [
                $reference->setTime(18, 0, 0),
                $reference->setTime(23, 59, 59),
            ],
            'this morning' => [
                $reference->setTime(6, 0, 0),
                $reference->setTime(12, 0, 0),
            ],
            'this afternoon' => [
                $reference->setTime(12, 0, 0),
                $reference->setTime(17, 0, 0),
            ],
            'this evening' => [
                $reference->setTime(17, 0, 0),
                $reference->setTime(21, 0, 0),
            ],

            // ── New: weekend ranges ──────────────────────────────
            'this weekend' => [$thisWeekendStart, $thisWeekendEnd],
            'next weekend' => [
                $thisWeekendStart->addWeek(),
                $thisWeekendEnd->addWeek(),
            ],
            'last weekend' => [
                $thisWeekendStart->subWeek(),
                $thisWeekendEnd->subWeek(),
            ],

            // ── New: quarter ranges ──────────────────────────────
            'this quarter' => [$reference->startOfQuarter(), $reference->endOfQuarter()],
            'next quarter' => [
                $reference->addQuarter()->startOfQuarter(),
                $reference->addQuarter()->endOfQuarter(),
            ],
            'last quarter' => [
                $reference->subQuarter()->startOfQuarter(),
                $reference->subQuarter()->endOfQuarter(),
            ],
        ];

        foreach ($map as $phrase => [$start, $end]) {
            if (str_contains($text, $phrase)) {
                $isRange = str_contains($phrase, 'week')
                    || str_contains($phrase, 'month')
                    || str_contains($phrase, 'year')
                    || str_contains($phrase, 'quarter')
                    || str_contains($phrase, 'weekend');

                return [
                    'has_temporal' => true,
                    'kind' => $isRange ? 'range' : 'event',
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

    // ─────────────────────────────────────────────────────────────
    //  5. detectInformalRelative — "day after tomorrow", "3 days ago"
    // ─────────────────────────────────────────────────────────────

    private function detectInformalRelative(string $text, CarbonImmutable $reference, bool $scheduleLike): ?array
    {
        // ── Fixed informal phrases ───────────────────────────────
        $fixedPhrases = [
            'day after tomorrow' => [$reference->addDays(2), 0.90],
            'day before yesterday' => [$reference->subDays(2), 0.90],
            'night before last' => [$reference->subDays(2), 0.85],
        ];

        foreach ($fixedPhrases as $phrase => [$date, $conf]) {
            if (str_contains($text, $phrase)) {
                return [
                    'has_temporal' => true,
                    'kind' => $scheduleLike ? 'event' : 'time_reference',
                    'schedule_like' => $scheduleLike,
                    'label' => $this->deriveLabel($text, 'informal'),
                    'source_phrase' => $phrase,
                    'start_at' => $date->startOfDay()->toIso8601String(),
                    'end_at' => $date->endOfDay()->toIso8601String(),
                    'timezone' => $reference->getTimezone()->getName(),
                    'recurrence_rule' => null,
                    'confidence' => $conf,
                ];
            }
        }

        // ── "coming monday", "upcoming sunday", "this coming friday" ─
        $weekdayRe = self::WEEKDAY_RE;

        if (preg_match('/\b(?:this\s+)?(?:coming|upcoming)\s+(' . $weekdayRe . ')\b/u', $text, $m)) {
            $day = $this->resolveWeekday($m[1]);
            $targetIso = self::WEEKDAY_TO_ISO[$day] ?? null;

            if ($targetIso) {
                $date = $this->findNextWeekday($reference, $targetIso);

                return [
                    'has_temporal' => true,
                    'kind' => $scheduleLike ? 'event' : 'time_reference',
                    'schedule_like' => $scheduleLike,
                    'label' => $this->deriveLabel($text, 'informal'),
                    'source_phrase' => $m[0],
                    'start_at' => $date->startOfDay()->toIso8601String(),
                    'end_at' => $date->endOfDay()->toIso8601String(),
                    'timezone' => $reference->getTimezone()->getName(),
                    'recurrence_rule' => null,
                    'confidence' => 0.88,
                ];
            }
        }

        // ── "previous/past [weekday]" ────────────────────────────
        if (preg_match('/\b(?:previous|past)\s+(' . $weekdayRe . ')\b/u', $text, $m)) {
            $day = $this->resolveWeekday($m[1]);

            try {
                $date = CarbonImmutable::parse('last ' . $day, $reference->getTimezone());

                return [
                    'has_temporal' => true,
                    'kind' => $scheduleLike ? 'event' : 'time_reference',
                    'schedule_like' => $scheduleLike,
                    'label' => $this->deriveLabel($text, 'informal'),
                    'source_phrase' => $m[0],
                    'start_at' => $date->startOfDay()->toIso8601String(),
                    'end_at' => $date->endOfDay()->toIso8601String(),
                    'timezone' => $reference->getTimezone()->getName(),
                    'recurrence_rule' => null,
                    'confidence' => 0.85,
                ];
            } catch (Throwable) {
                // continue to next pattern
            }
        }

        // ── "N days/weeks/months ago" ────────────────────────────
        $numberRe = self::NUMBER_WORD_RE;
        $unitRe = self::TIME_UNIT_RE;

        if (preg_match('/\b(' . $numberRe . ')\s+(' . $unitRe . ')\s+ago\b/u', $text, $m)) {
            $amount = $this->wordToNumber($m[1]);
            $unit = $this->normalizeTimeUnit($m[2]);

            $date = match ($unit) {
                'minute' => $reference->subMinutes($amount),
                'hour'   => $reference->subHours($amount),
                'day'    => $reference->subDays($amount),
                'week'   => $reference->subWeeks($amount),
                'month'  => $reference->subMonthsNoOverflow($amount),
                'year'   => $reference->subYears($amount),
                default  => null,
            };

            if ($date) {
                return [
                    'has_temporal' => true,
                    'kind' => 'time_reference',
                    'schedule_like' => false,
                    'label' => $this->deriveLabel($text, 'informal'),
                    'source_phrase' => $m[0],
                    'start_at' => $date->startOfDay()->toIso8601String(),
                    'end_at' => $date->endOfDay()->toIso8601String(),
                    'timezone' => $reference->getTimezone()->getName(),
                    'recurrence_rule' => null,
                    'confidence' => 0.85,
                ];
            }
        }

        // ── "end of this/next/last week/month/year" ──────────────
        if (preg_match('/\b(?:end\s+of|towards\s+(?:the\s+)?end\s+of)\s+(this|next|last)\s+(week|month|year)\b/u', $text, $m)) {
            $ref = $m[1];
            $unit = $m[2];

            $date = match ("{$ref} {$unit}") {
                'this week'  => $reference->endOfWeek(),
                'next week'  => $reference->addWeek()->endOfWeek(),
                'last week'  => $reference->subWeek()->endOfWeek(),
                'this month' => $reference->endOfMonth(),
                'next month' => $reference->addMonthNoOverflow()->endOfMonth(),
                'last month' => $reference->subMonthNoOverflow()->endOfMonth(),
                'this year'  => $reference->endOfYear(),
                'next year'  => $reference->addYear()->endOfYear(),
                'last year'  => $reference->subYear()->endOfYear(),
                default      => null,
            };

            if ($date) {
                return [
                    'has_temporal' => true,
                    'kind' => 'time_reference',
                    'schedule_like' => $scheduleLike,
                    'label' => $this->deriveLabel($text, 'informal'),
                    'source_phrase' => $m[0],
                    'start_at' => $date->subDays(2)->startOfDay()->toIso8601String(),
                    'end_at' => $date->endOfDay()->toIso8601String(),
                    'timezone' => $reference->getTimezone()->getName(),
                    'recurrence_rule' => null,
                    'confidence' => 0.82,
                ];
            }
        }

        // ── "beginning/start of this/next week/month/year" ───────
        if (preg_match('/\b(?:beginning|start|starting)\s+of\s+(this|next|last)\s+(week|month|year)\b/u', $text, $m)) {
            $ref = $m[1];
            $unit = $m[2];

            $date = match ("{$ref} {$unit}") {
                'this week'  => $reference->startOfWeek(),
                'next week'  => $reference->addWeek()->startOfWeek(),
                'last week'  => $reference->subWeek()->startOfWeek(),
                'this month' => $reference->startOfMonth(),
                'next month' => $reference->addMonthNoOverflow()->startOfMonth(),
                'last month' => $reference->subMonthNoOverflow()->startOfMonth(),
                'this year'  => $reference->startOfYear(),
                'next year'  => $reference->addYear()->startOfYear(),
                'last year'  => $reference->subYear()->startOfYear(),
                default      => null,
            };

            if ($date) {
                return [
                    'has_temporal' => true,
                    'kind' => 'time_reference',
                    'schedule_like' => $scheduleLike,
                    'label' => $this->deriveLabel($text, 'informal'),
                    'source_phrase' => $m[0],
                    'start_at' => $date->startOfDay()->toIso8601String(),
                    'end_at' => $date->addDays(2)->endOfDay()->toIso8601String(),
                    'timezone' => $reference->getTimezone()->getName(),
                    'recurrence_rule' => null,
                    'confidence' => 0.82,
                ];
            }
        }

        // ── "mid-july", "mid june", "mid-month" ─────────────────
        $monthRe = self::MONTH_RE;

        if (preg_match('/\bmid[- ]?(' . $monthRe . ')\b/u', $text, $m)) {
            $monthNum = $this->monthToNumber($m[1]);

            if ($monthNum) {
                try {
                    $date = CarbonImmutable::create($reference->year, $monthNum, 15, 0, 0, 0, $reference->getTimezone());
                    $date = $this->inferYear($date, $reference, $scheduleLike);

                    return [
                        'has_temporal' => true,
                        'kind' => 'time_reference',
                        'schedule_like' => $scheduleLike,
                        'label' => $this->deriveLabel($text, 'informal'),
                        'source_phrase' => $m[0],
                        'start_at' => $date->subDays(3)->startOfDay()->toIso8601String(),
                        'end_at' => $date->addDays(3)->endOfDay()->toIso8601String(),
                        'timezone' => $reference->getTimezone()->getName(),
                        'recurrence_rule' => null,
                        'confidence' => 0.72,
                    ];
                } catch (Throwable) {
                    // continue
                }
            }
        }

        if (preg_match('/\bmid[- ]?month\b/u', $text)) {
            $date = $reference->setDay(15);

            return [
                'has_temporal' => true,
                'kind' => 'time_reference',
                'schedule_like' => $scheduleLike,
                'label' => $this->deriveLabel($text, 'informal'),
                'source_phrase' => 'mid-month',
                'start_at' => $date->subDays(3)->startOfDay()->toIso8601String(),
                'end_at' => $date->addDays(3)->endOfDay()->toIso8601String(),
                'timezone' => $reference->getTimezone()->getName(),
                'recurrence_rule' => null,
                'confidence' => 0.70,
            ];
        }

        return null;
    }

    // ─────────────────────────────────────────────────────────────
    //  6. detectRelativeWeekday — "this monday", "on friday"
    // ─────────────────────────────────────────────────────────────

    private function detectRelativeWeekday(string $text, CarbonImmutable $reference, bool $scheduleLike): ?array
    {
        $weekdayRe = self::WEEKDAY_RE;

        // ── Check 1: Explicit prefix (last/this/next + weekday) ──
        if (preg_match('/\b(last|this|next)\s+(' . $weekdayRe . ')\b/u', $text, $m)) {
            $prefix = $m[1];
            $day = $this->resolveWeekday($m[2]);

            try {
                $phrase = $prefix . ' ' . $day;
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
                // fall through
            }
        }

        // ── Check 2: Preposition + weekday ("on monday", "by fri")
        if (preg_match('/\b(?:on|by|due|before|after|until)\s+(' . $weekdayRe . ')\b/u', $text, $m)) {
            $day = $this->resolveWeekday($m[1]);
            $targetIso = self::WEEKDAY_TO_ISO[$day] ?? null;

            if ($targetIso) {
                $date = $this->findNextWeekday($reference, $targetIso);

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
                    'confidence' => 0.85,
                ];
            }
        }

        // ── Check 3: Bare weekday (only with schedule context) ───
        if ($scheduleLike && preg_match('/\b(' . $weekdayRe . ')\b/u', $text, $m)) {
            $day = $this->resolveWeekday($m[1]);
            $targetIso = self::WEEKDAY_TO_ISO[$day] ?? null;

            if ($targetIso) {
                $date = $this->findNextWeekday($reference, $targetIso);

                return [
                    'has_temporal' => true,
                    'kind' => 'schedule',
                    'schedule_like' => true,
                    'label' => $this->deriveLabel($text, 'weekday'),
                    'source_phrase' => $m[0],
                    'start_at' => $date->startOfDay()->toIso8601String(),
                    'end_at' => $date->endOfDay()->toIso8601String(),
                    'timezone' => $reference->getTimezone()->getName(),
                    'recurrence_rule' => null,
                    'confidence' => 0.70,
                ];
            }
        }

        return null;
    }

    // ─────────────────────────────────────────────────────────────
    //  7. detectAbsoluteDate — "15 June 2026", "in june", "2026-06-15"
    // ─────────────────────────────────────────────────────────────

    private function detectAbsoluteDate(string $text, CarbonImmutable $reference, bool $scheduleLike): ?array
    {
        $monthRe = self::MONTH_RE;

        // ── Pattern A: Day + Month [+ Year] — "15 june 2026", "15 of june"
        if (preg_match('/\b(\d{1,2})\s+(?:of\s+)?(' . $monthRe . ')(?:\s+(\d{4}))?\b/u', $text, $m)) {
            $result = $this->resolveAbsoluteFromDayMonth(
                (int) $m[1], $m[2], $m[3] ?? null, $m[0], $reference, $scheduleLike
            );
            if ($result) {
                return $result;
            }
        }

        // ── Pattern B: Month + Day [+ Year] — "june 15 2026", "june 15"
        if (preg_match('/\b(' . $monthRe . ')\s+(\d{1,2})(?:\s+(\d{4}))?\b/u', $text, $m)) {
            $result = $this->resolveAbsoluteFromDayMonth(
                (int) $m[2], $m[1], $m[3] ?? null, $m[0], $reference, $scheduleLike
            );
            if ($result) {
                return $result;
            }
        }

        // ── Pattern C: ISO date — "2026-06-15" ───────────────────
        if (preg_match('/\b(\d{4})-(\d{2})-(\d{2})\b/u', $text, $m)) {
            try {
                $date = CarbonImmutable::createStrict((int) $m[1], (int) $m[2], (int) $m[3], 0, 0, 0, $reference->getTimezone());

                return [
                    'has_temporal' => true,
                    'kind' => $scheduleLike ? 'event' : 'time_reference',
                    'schedule_like' => $scheduleLike,
                    'label' => $this->deriveLabel($text, 'absolute'),
                    'source_phrase' => $m[0],
                    'start_at' => $date->startOfDay()->toIso8601String(),
                    'end_at' => $date->endOfDay()->toIso8601String(),
                    'timezone' => $reference->getTimezone()->getName(),
                    'recurrence_rule' => null,
                    'confidence' => 0.95,
                ];
            } catch (Throwable) {
                // continue
            }
        }

        // ── Pattern D: Slash/dash separated — "15/06/2026", "06-15"
        if (preg_match('/\b(\d{1,2})[\/\-](\d{1,2})(?:[\/\-](\d{2,4}))?\b/u', $text, $m)) {
            $first = (int) $m[1];
            $second = (int) $m[2];
            $yearRaw = $m[3] ?? null;

            $year = $yearRaw ? (int) $yearRaw : $reference->year;
            if ($year < 100) {
                $year += ($year < 50) ? 2000 : 1900;
            }

            // DD/MM vs MM/DD heuristic (international DD/MM default)
            if ($first > 12) {
                $day = $first;
                $month = $second;
            } elseif ($second > 12) {
                $month = $first;
                $day = $second;
            } else {
                // Ambiguous → DD/MM (international standard)
                $day = $first;
                $month = $second;
            }

            if ($month >= 1 && $month <= 12 && $day >= 1 && $day <= 31) {
                try {
                    $date = CarbonImmutable::createStrict($year, $month, $day, 0, 0, 0, $reference->getTimezone());

                    if (! $yearRaw) {
                        $date = $this->inferYear($date, $reference, $scheduleLike);
                    }

                    return [
                        'has_temporal' => true,
                        'kind' => $scheduleLike ? 'event' : 'time_reference',
                        'schedule_like' => $scheduleLike,
                        'label' => $this->deriveLabel($text, 'absolute'),
                        'source_phrase' => $m[0],
                        'start_at' => $date->startOfDay()->toIso8601String(),
                        'end_at' => $date->endOfDay()->toIso8601String(),
                        'timezone' => $reference->getTimezone()->getName(),
                        'recurrence_rule' => null,
                        'confidence' => 0.88,
                    ];
                } catch (Throwable) {
                    // invalid date
                }
            }
        }

        // ── Pattern E: Month-only — "in june", "by march", "during august"
        if (preg_match('/\b(?:in|by|during|for|around)\s+(' . $monthRe . ')\b/u', $text, $m)) {
            $monthNum = $this->monthToNumber($m[1]);

            if ($monthNum) {
                try {
                    $start = CarbonImmutable::create($reference->year, $monthNum, 1, 0, 0, 0, $reference->getTimezone());
                    $end = $start->endOfMonth();
                    $start = $this->inferYear($start, $reference, $scheduleLike);
                    $end = $start->endOfMonth();

                    return [
                        'has_temporal' => true,
                        'kind' => 'range',
                        'schedule_like' => $scheduleLike,
                        'label' => $this->deriveLabel($text, 'absolute'),
                        'source_phrase' => $m[0],
                        'start_at' => $start->startOfDay()->toIso8601String(),
                        'end_at' => $end->endOfDay()->toIso8601String(),
                        'timezone' => $reference->getTimezone()->getName(),
                        'recurrence_rule' => null,
                        'confidence' => 0.78,
                    ];
                } catch (Throwable) {
                    // continue
                }
            }
        }

        // ── Pattern F: Month + Year — "june 2027", "march 2026"
        if (preg_match('/\b(' . $monthRe . ')\s+(\d{4})\b/u', $text, $m)) {
            $monthNum = $this->monthToNumber($m[1]);

            if ($monthNum) {
                try {
                    $start = CarbonImmutable::create((int) $m[2], $monthNum, 1, 0, 0, 0, $reference->getTimezone());

                    return [
                        'has_temporal' => true,
                        'kind' => 'range',
                        'schedule_like' => $scheduleLike,
                        'label' => $this->deriveLabel($text, 'absolute'),
                        'source_phrase' => $m[0],
                        'start_at' => $start->startOfDay()->toIso8601String(),
                        'end_at' => $start->endOfMonth()->endOfDay()->toIso8601String(),
                        'timezone' => $reference->getTimezone()->getName(),
                        'recurrence_rule' => null,
                        'confidence' => 0.85,
                    ];
                } catch (Throwable) {
                    // continue
                }
            }
        }

        // ── Pattern G: Year-only — "in 2027", "by 2028"
        if (preg_match('/\b(?:in|by|during|for|around)\s+(\d{4})\b/u', $text, $m)) {
            $year = (int) $m[1];
            if ($year >= 2000 && $year <= 2100) {
                try {
                    $start = CarbonImmutable::create($year, 1, 1, 0, 0, 0, $reference->getTimezone());

                    return [
                        'has_temporal' => true,
                        'kind' => 'range',
                        'schedule_like' => $scheduleLike,
                        'label' => $this->deriveLabel($text, 'absolute'),
                        'source_phrase' => $m[0],
                        'start_at' => $start->startOfDay()->toIso8601String(),
                        'end_at' => $start->endOfYear()->endOfDay()->toIso8601String(),
                        'timezone' => $reference->getTimezone()->getName(),
                        'recurrence_rule' => null,
                        'confidence' => 0.75,
                    ];
                } catch (Throwable) {
                    // continue
                }
            }
        }

        return null;
    }

    // ─────────────────────────────────────────────────────────────
    //  8. detectTimeRange — "3pm to 5pm", "between 9am and 11am"
    // ─────────────────────────────────────────────────────────────

    private function detectTimeRange(string $text, CarbonImmutable $reference, bool $scheduleLike): ?array
    {
        $timePart = '(\d{1,2})(?::(\d{2}))?\s*(am|pm)?';

        $patterns = [
            // "from 3pm to 5pm", "3pm - 5:30pm", "3pm till 5pm"
            '/\b(?:from\s+)?' . $timePart . '\s*(?:to|till|until|-|–|—)\s*' . $timePart . '\b/u',
            // "between 9am and 11am"
            '/\bbetween\s+' . $timePart . '\s+and\s+' . $timePart . '\b/u',
        ];

        foreach ($patterns as $pattern) {
            if (! preg_match($pattern, $text, $m)) {
                continue;
            }

            $start = $this->parseTimeComponents($m[1], $m[2] ?? null, $m[3] ?? null);
            $end = $this->parseTimeComponents($m[4], $m[5] ?? null, $m[6] ?? null);

            if (! $start || ! $end) {
                continue;
            }

            // Infer AM/PM when one side has it and the other doesn't
            if (empty($m[3]) && ! empty($m[6])) {
                $start = $this->inferAmPm($start, $end);
            } elseif (! empty($m[3]) && empty($m[6])) {
                $end = $this->inferAmPm($end, $start);
            }

            try {
                $startDt = $reference->setTime($start['hour'], $start['minute'], 0);
                $endDt = $reference->setTime($end['hour'], $end['minute'], 0);

                // If end is before start, assume end is next day
                if ($endDt->lessThanOrEqualTo($startDt)) {
                    $endDt = $endDt->addDay();
                }

                return [
                    'has_temporal' => true,
                    'kind' => $scheduleLike ? 'event' : 'time_reference',
                    'schedule_like' => $scheduleLike,
                    'label' => $this->deriveLabel($text, 'time_range'),
                    'source_phrase' => $m[0],
                    'start_at' => $startDt->toIso8601String(),
                    'end_at' => $endDt->toIso8601String(),
                    'timezone' => $reference->getTimezone()->getName(),
                    'recurrence_rule' => null,
                    'confidence' => 0.88,
                ];
            } catch (Throwable) {
                continue;
            }
        }

        return null;
    }

    // ─────────────────────────────────────────────────────────────
    //  9. detectTimeOnly — "3pm", "at 14:30", "at noon"
    // ─────────────────────────────────────────────────────────────

    private function detectTimeOnly(string $text, CarbonImmutable $reference, bool $scheduleLike): ?array
    {
        // ── Explicit 12-hour time: "3pm", "3:30 am", "at 10pm" ──
        if (preg_match('/\b(?:at\s+)?(\d{1,2})(?::(\d{2}))?\s*(am|pm)\b/u', $text, $m)) {
            $components = $this->parseTimeComponents($m[1], $m[2] ?? null, $m[3]);

            if ($components) {
                try {
                    $dateTime = $reference->setTime($components['hour'], $components['minute'], 0);

                    return [
                        'has_temporal' => true,
                        'kind' => $scheduleLike ? 'event' : 'time_reference',
                        'schedule_like' => $scheduleLike,
                        'label' => $this->deriveLabel($text, 'time'),
                        'source_phrase' => trim($m[0]),
                        'start_at' => $dateTime->toIso8601String(),
                        'end_at' => $dateTime->addHour()->toIso8601String(),
                        'timezone' => $reference->getTimezone()->getName(),
                        'recurrence_rule' => null,
                        'confidence' => 0.82,
                    ];
                } catch (Throwable) {
                    // continue
                }
            }
        }

        // ── 24-hour time: "14:30", "at 09:00" ───────────────────
        if (preg_match('/\b(?:at\s+)?(\d{1,2}):(\d{2})\b/u', $text, $m)) {
            $hour = (int) $m[1];
            $minute = (int) $m[2];

            if ($hour >= 0 && $hour <= 23 && $minute >= 0 && $minute <= 59) {
                try {
                    $dateTime = $reference->setTime($hour, $minute, 0);

                    return [
                        'has_temporal' => true,
                        'kind' => $scheduleLike ? 'event' : 'time_reference',
                        'schedule_like' => $scheduleLike,
                        'label' => $this->deriveLabel($text, 'time'),
                        'source_phrase' => trim($m[0]),
                        'start_at' => $dateTime->toIso8601String(),
                        'end_at' => $dateTime->addHour()->toIso8601String(),
                        'timezone' => $reference->getTimezone()->getName(),
                        'recurrence_rule' => null,
                        'confidence' => 0.80,
                    ];
                } catch (Throwable) {
                    // continue
                }
            }
        }

        // ── Named times: "at noon", "at midnight" ────────────────
        $namedTimes = [
            'noon'     => [12, 0],
            'midday'   => [12, 0],
            'midnight' => [0, 0],
        ];

        foreach ($namedTimes as $name => [$hour, $minute]) {
            if (preg_match('/\b(?:at\s+|by\s+)?' . preg_quote($name, '/') . '\b/u', $text)) {
                try {
                    $dateTime = $reference->setTime($hour, $minute, 0);

                    return [
                        'has_temporal' => true,
                        'kind' => $scheduleLike ? 'event' : 'time_reference',
                        'schedule_like' => $scheduleLike,
                        'label' => $this->deriveLabel($text, 'time'),
                        'source_phrase' => $name,
                        'start_at' => $dateTime->toIso8601String(),
                        'end_at' => $dateTime->addHour()->toIso8601String(),
                        'timezone' => $reference->getTimezone()->getName(),
                        'recurrence_rule' => null,
                        'confidence' => 0.80,
                    ];
                } catch (Throwable) {
                    // continue
                }
            }
        }

        return null;
    }

    // ─────────────────────────────────────────────────────────────
    //  10. detectSeasonHoliday — "this summer", "christmas"
    // ─────────────────────────────────────────────────────────────

    private function detectSeasonHoliday(string $text, CarbonImmutable $reference, bool $scheduleLike): ?array
    {
        // ── Seasons (Northern Hemisphere / meteorological) ────────
        $seasons = [
            'spring' => [3, 1, 5, 31],
            'summer' => [6, 1, 8, 31],
            'fall'   => [9, 1, 11, 30],
            'autumn' => [9, 1, 11, 30],
            'winter' => [12, 1, 2, 28],
        ];

        if (preg_match('/\b(this|next|last)\s+(spring|summer|fall|autumn|winter)\b/u', $text, $m)) {
            $rel = $m[1];
            $season = $m[2];
            $range = $seasons[$season] ?? null;

            if ($range) {
                [$startMonth, $startDay, $endMonth, $endDay] = $range;

                $year = $reference->year;
                if ($rel === 'next') {
                    $year++;
                } elseif ($rel === 'last') {
                    $year--;
                }

                // Winter crosses year boundary (Dec → Feb)
                $endYear = ($startMonth > $endMonth) ? $year + 1 : $year;
                if ($season === 'winter' && $endMonth === 2) {
                    // Adjust for leap year
                    $endDay = CarbonImmutable::create($endYear, 2, 1)->daysInMonth;
                }

                try {
                    $start = CarbonImmutable::create($year, $startMonth, $startDay, 0, 0, 0, $reference->getTimezone());
                    $end = CarbonImmutable::create($endYear, $endMonth, $endDay, 23, 59, 59, $reference->getTimezone());

                    return [
                        'has_temporal' => true,
                        'kind' => 'range',
                        'schedule_like' => $scheduleLike,
                        'label' => $this->deriveLabel($text, 'season'),
                        'source_phrase' => $m[0],
                        'start_at' => $start->toIso8601String(),
                        'end_at' => $end->toIso8601String(),
                        'timezone' => $reference->getTimezone()->getName(),
                        'recurrence_rule' => null,
                        'confidence' => 0.65,
                    ];
                } catch (Throwable) {
                    // continue
                }
            }
        }

        // ── Fixed-date holidays ──────────────────────────────────
        $holidays = [
            'christmas eve'     => [12, 24],
            'christmas day'     => [12, 25],
            'christmas'         => [12, 25],
            "new year's eve"    => [12, 31],
            "new years eve"     => [12, 31],
            "new year's day"    => [1, 1],
            "new years day"     => [1, 1],
            'new year'          => [1, 1],
            "valentine's day"   => [2, 14],
            'valentines day'    => [2, 14],
            'valentine'         => [2, 14],
            'halloween'         => [10, 31],
            'republic day'      => [1, 26],
            'independence day'  => [8, 15],
        ];

        foreach ($holidays as $name => [$month, $day]) {
            if (str_contains($text, $name)) {
                try {
                    $date = CarbonImmutable::create($reference->year, $month, $day, 0, 0, 0, $reference->getTimezone());
                    $date = $this->inferYear($date, $reference, $scheduleLike);

                    return [
                        'has_temporal' => true,
                        'kind' => $scheduleLike ? 'event' : 'time_reference',
                        'schedule_like' => $scheduleLike,
                        'label' => ucfirst($name),
                        'source_phrase' => $name,
                        'start_at' => $date->startOfDay()->toIso8601String(),
                        'end_at' => $date->endOfDay()->toIso8601String(),
                        'timezone' => $reference->getTimezone()->getName(),
                        'recurrence_rule' => null,
                        'confidence' => 0.70,
                    ];
                } catch (Throwable) {
                    continue;
                }
            }
        }

        return null;
    }

    // ═════════════════════════════════════════════════════════════
    //  PRIVATE — Post-processing (finalize, enrichWithTime, enrichDuration)
    // ═════════════════════════════════════════════════════════════

    /**
     * Apply time enrichment and duration enrichment to a detected result.
     */
    private function finalize(string $text, array $result, CarbonImmutable $reference): array
    {
        $result = $this->enrichWithTime($text, $result, $reference);
        $result = $this->enrichDuration($text, $result, $reference);

        return $result;
    }

    /**
     * If the detection is day-level and the text contains a time expression,
     * narrow start_at/end_at to that specific time.
     */
    private function enrichWithTime(string $text, array $result, CarbonImmutable $reference): array
    {
        if (! ($result['has_temporal'] ?? false) || ! $result['start_at']) {
            return $result;
        }

        // Don't narrow ranges or recurring results
        if (in_array($result['kind'] ?? '', ['range', 'recurring'])) {
            return $result;
        }

        try {
            $startAt = CarbonImmutable::parse($result['start_at']);
        } catch (Throwable) {
            return $result;
        }

        // Only enrich day-level results (midnight)
        if ($startAt->hour !== 0 || $startAt->minute !== 0 || $startAt->second !== 0) {
            return $result;
        }

        $hour = null;
        $minute = 0;

        // ── Priority 1: Explicit 12-hour time ────────────────────
        if (preg_match('/\b(?:at\s+)?(\d{1,2})(?::(\d{2}))?\s*(am|pm)\b/u', $text, $m)) {
            $c = $this->parseTimeComponents($m[1], $m[2] ?? null, $m[3]);
            if ($c) {
                $hour = $c['hour'];
                $minute = $c['minute'];
            }
        }
        // ── Priority 2: Explicit 24-hour time ────────────────────
        elseif (preg_match('/\b(?:at\s+)?(\d{1,2}):(\d{2})\b/u', $text, $m)) {
            $h = (int) $m[1];
            if ($h >= 0 && $h <= 23) {
                $hour = $h;
                $minute = (int) $m[2];
            }
        }
        // ── Priority 3: Named times ──────────────────────────────
        elseif (preg_match('/\b(?:at\s+)?noon\b/u', $text)) {
            $hour = 12;
        } elseif (preg_match('/\b(?:at\s+)?midnight\b/u', $text)) {
            $hour = 0;
        }
        // ── Priority 4: Time-of-day qualifiers ───────────────────
        elseif (preg_match('/\b(?:in\s+the\s+)?morning\b/u', $text)) {
            $hour = 9;
        } elseif (preg_match('/\b(?:in\s+the\s+)?afternoon\b/u', $text)) {
            $hour = 14;
        } elseif (preg_match('/\b(?:in\s+the\s+)?evening\b/u', $text)) {
            $hour = 18;
        } elseif (preg_match('/\bat?\s+night\b/u', $text)) {
            $hour = 21;
        }

        if ($hour !== null) {
            $newStart = $startAt->setTime($hour, $minute, 0);
            $result['start_at'] = $newStart->toIso8601String();

            // Adjust end_at: if it was end-of-day, set to start + 1 hour
            try {
                $endAt = CarbonImmutable::parse($result['end_at']);
                if ($endAt->hour === 23 && $endAt->minute === 59) {
                    $result['end_at'] = $newStart->addHour()->toIso8601String();
                }
            } catch (Throwable) {
                $result['end_at'] = $newStart->addHour()->toIso8601String();
            }

            $result['confidence'] = min(1.0, round(($result['confidence'] ?? 0.5) + 0.03, 4));
        }

        return $result;
    }

    /**
     * If the text contains a duration expression ("for 2 hours"),
     * adjust end_at accordingly.
     */
    private function enrichDuration(string $text, array $result, CarbonImmutable $reference): array
    {
        if (! ($result['has_temporal'] ?? false) || ! $result['start_at']) {
            return $result;
        }

        if (($result['kind'] ?? '') === 'recurring') {
            return $result;
        }

        $numberRe = self::NUMBER_WORD_RE;
        $unitRe = 'minutes?|mins?|hours?|hrs?|days?|nights?';

        $patterns = [
            '/\bfor\s+(' . $numberRe . ')\s+(' . $unitRe . ')\b/u',
            '/\blasting\s+(' . $numberRe . ')\s+(' . $unitRe . ')\b/u',
            '/\bduration[:\s]+(' . $numberRe . ')\s+(' . $unitRe . ')\b/u',
            '/\btakes?\s+(?:about\s+)?(' . $numberRe . ')\s+(' . $unitRe . ')\b/u',
            '/\b(' . $numberRe . ')\s*[-–]\s*(' . $unitRe . ')\s+(?:long|call|meeting|session|class|lecture|talk)\b/u',
        ];

        foreach ($patterns as $pattern) {
            if (! preg_match($pattern, $text, $m)) {
                continue;
            }

            $amount = $this->wordToNumber($m[1]);
            $unit = $this->normalizeTimeUnit($m[2]);

            $durationMinutes = match ($unit) {
                'minute' => $amount,
                'hour'   => $amount * 60,
                'day'    => $amount * 24 * 60,
                default  => 0,
            };

            // Cap at 30 days
            $maxMinutes = 30 * 24 * 60;
            if ($durationMinutes <= 0 || $durationMinutes > $maxMinutes) {
                continue;
            }

            try {
                $startAt = CarbonImmutable::parse($result['start_at']);
                $result['end_at'] = $startAt->addMinutes($durationMinutes)->toIso8601String();
                $result['confidence'] = min(1.0, round(($result['confidence'] ?? 0.5) + 0.03, 4));
            } catch (Throwable) {
                // keep original end_at
            }

            break;
        }

        return $result;
    }

    // ═════════════════════════════════════════════════════════════
    //  PUBLIC — Schedule intent detection
    // ═════════════════════════════════════════════════════════════

    public function containsScheduleIntent(string $text): bool
    {
        $keywords = [
            // ── Original keywords (preserved) ────────────────────
            'meeting', 'meetings', 'seminar', 'seminars',
            'presentation', 'presentations',
            'routine', 'routines', 'schedule', 'schedules',
            'appointment', 'appointments', 'event', 'events',
            'workshop', 'workshops', 'call', 'calls',
            'deadline', 'deadlines', 'plan', 'plans',
            'trip', 'reminder', 'reminders',

            // ── Student ──────────────────────────────────────────
            'exam', 'exams', 'test', 'tests',
            'quiz', 'quizzes', 'homework',
            'assignment', 'assignments',
            'project', 'projects',
            'lecture', 'lectures',
            'class', 'classes',
            'tutorial', 'tutorials',
            'semester', 'lab', 'labs',
            'submission', 'submissions',
            'viva', 'placement', 'internship',
            'orientation', 'convocation',
            'fresher', 'farewell',
            'practical', 'practicals',

            // ── Office ───────────────────────────────────────────
            'standup', 'stand-up',
            'sync', 'syncs', 'sprint',
            'retro', 'retrospective',
            'demo', 'review', 'reviews',
            'one-on-one', '1:1',
            'offsite', 'townhall', 'town hall',
            'huddle', 'briefing', 'debrief',
            'kick-off', 'kickoff',
            'check-in', 'checkin',
            'all-hands', 'all hands',
            'onboarding', 'training',
            'interview', 'interviews',
            'scrum', 'standup',

            // ── Personal / Life ──────────────────────────────────
            'birthday', 'anniversary', 'wedding', 'funeral',
            'ceremony', 'party', 'celebration',
            'dinner', 'lunch', 'brunch', 'breakfast',
            'date night',
            'doctor', 'dentist', 'therapy',
            'gym', 'workout', 'yoga', 'meditation',
            'flight', 'travel', 'vacation', 'holiday',
            'festival', 'concert', 'movie', 'show',
            'game', 'match',
            'pickup', 'drop-off', 'dropoff',
            'errand', 'errands', 'chore', 'chores',
            'grocery', 'groceries', 'laundry', 'cleaning',

            // ── Generic temporal intent ──────────────────────────
            'due', 'overdue',
            'postponed', 'rescheduled',
            'moved to', 'shifted to', 'pushed to',
            'bumped to', 'delayed', 'extended',
            'renewed', 'expire', 'expires',
            'expiry', 'expiring', 'renewal',
        ];

        foreach ($keywords as $keyword) {
            if (preg_match('/\b' . preg_quote($keyword, '/') . '\b/u', $text)) {
                return true;
            }
        }

        return false;
    }

    // ═════════════════════════════════════════════════════════════
    //  PRIVATE — Helper methods
    // ═════════════════════════════════════════════════════════════

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

    /**
     * Derive a human-readable label from the text and detection kind.
     * Prefers context-specific labels (Meeting, Exam, Deadline…) over generic ones.
     */
    private function deriveLabel(string $text, string $kind): string
    {
        $contextLabels = [
            'meeting'       => 'Meeting',
            'standup'       => 'Standup',
            'stand-up'      => 'Stand-up',
            'exam'          => 'Exam',
            'test'          => 'Test',
            'quiz'          => 'Quiz',
            'assignment'    => 'Assignment',
            'homework'      => 'Homework',
            'deadline'      => 'Deadline',
            'interview'     => 'Interview',
            'presentation'  => 'Presentation',
            'class'         => 'Class',
            'lecture'       => 'Lecture',
            'workshop'      => 'Workshop',
            'seminar'       => 'Seminar',
            'appointment'   => 'Appointment',
            'birthday'      => 'Birthday',
            'anniversary'   => 'Anniversary',
            'flight'        => 'Flight',
            'trip'          => 'Trip',
            'vacation'      => 'Vacation',
            'holiday'       => 'Holiday',
            'wedding'       => 'Wedding',
            'party'         => 'Party',
            'dinner'        => 'Dinner',
            'lunch'         => 'Lunch',
            'breakfast'     => 'Breakfast',
            'brunch'        => 'Brunch',
            'call'          => 'Call',
            'sprint'        => 'Sprint',
            'review'        => 'Review',
            'demo'          => 'Demo',
            'training'      => 'Training',
            'gym'           => 'Gym',
            'workout'       => 'Workout',
            'doctor'        => 'Doctor',
            'dentist'       => 'Dentist',
            'therapy'       => 'Therapy',
            'concert'       => 'Concert',
            'movie'         => 'Movie',
            'reminder'      => 'Reminder',
            'submission'    => 'Submission',
            'placement'     => 'Placement',
            'viva'          => 'Viva',
            'lab'           => 'Lab',
            'ceremony'      => 'Ceremony',
            'festival'      => 'Festival',
            'game'          => 'Game',
            'match'         => 'Match',
        ];

        foreach ($contextLabels as $keyword => $label) {
            if (str_contains($text, $keyword)) {
                return $label;
            }
        }

        return match ($kind) {
            'recurring'   => 'Recurring schedule',
            'range'       => 'Time range',
            'weekday'     => 'Specific day',
            'absolute'    => 'Dated memory',
            'time'        => 'Timed memory',
            'offset'      => 'Upcoming event',
            'ordinal'     => 'Calendar reference',
            'informal'    => 'Relative date',
            'time_range'  => 'Time window',
            'season'      => 'Seasonal reference',
            'holiday'     => 'Holiday',
            default       => 'Temporal memory',
        };
    }

    /**
     * Convert a word (e.g., "three") or digit string to its integer value.
     */
    private function wordToNumber(string $word): int
    {
        $word = strtolower(trim($word));

        if (is_numeric($word)) {
            return max(1, (int) $word);
        }

        return self::WORD_TO_NUMBER[$word] ?? 1;
    }

    /**
     * Normalize time unit to singular canonical form.
     */
    private function normalizeTimeUnit(string $unit): string
    {
        $unit = strtolower(trim($unit));

        return match (true) {
            in_array($unit, ['minute', 'minutes', 'min', 'mins']) => 'minute',
            in_array($unit, ['hour', 'hours', 'hr', 'hrs'])      => 'hour',
            in_array($unit, ['day', 'days', 'night', 'nights'])    => 'day',
            in_array($unit, ['week', 'weeks', 'wk', 'wks'])       => 'week',
            in_array($unit, ['month', 'months'])                   => 'month',
            in_array($unit, ['year', 'years', 'yr', 'yrs'])       => 'year',
            default => $unit,
        };
    }

    /**
     * Resolve weekday alias to full name (e.g. "tue" → "tuesday").
     */
    private function resolveWeekday(string $day): string
    {
        $day = strtolower(trim($day));

        return self::WEEKDAY_ALIASES[$day] ?? $day;
    }

    /**
     * Resolve month alias to full name (e.g. "jan" → "january").
     */
    private function resolveMonth(string $month): string
    {
        $month = strtolower(trim($month));

        return self::MONTH_ALIASES[$month] ?? $month;
    }

    /**
     * Convert month name/alias to its 1-based number.
     */
    private function monthToNumber(string $month): ?int
    {
        $resolved = $this->resolveMonth($month);

        return self::MONTH_NAMES[$resolved] ?? null;
    }

    /**
     * Parse an ordinal word or number string to integer.
     * Returns -1 for "last", 0 if unparseable.
     */
    private function parseOrdinal(string $value): int
    {
        $value = strtolower(trim($value));

        if (is_numeric($value)) {
            return (int) $value;
        }

        return self::ORDINAL_WORDS[$value] ?? 0;
    }

    /**
     * Infer the year for a date without an explicit year.
     * If date is past and text indicates a future event (schedule_like), advance to next year.
     */
    private function inferYear(CarbonImmutable $date, CarbonImmutable $reference, bool $scheduleLike): CarbonImmutable
    {
        // Already in the future → keep as-is
        if ($date->greaterThanOrEqualTo($reference->startOfDay())) {
            return $date;
        }

        // Date is in the past — if schedule context and > 7 days ago, assume next year
        if ($scheduleLike && $date->lessThan($reference->subDays(7))) {
            return $date->addYear();
        }

        return $date;
    }

    /**
     * Parse hour/minute/ampm strings into a ['hour' => int, 'minute' => int] array.
     */
    private function parseTimeComponents(string $hourStr, ?string $minuteStr, ?string $ampm): ?array
    {
        $hour = (int) $hourStr;
        $minute = (int) ($minuteStr ?? '0');

        if ($ampm) {
            $ampm = strtolower($ampm);
            if ($ampm === 'pm' && $hour < 12) {
                $hour += 12;
            }
            if ($ampm === 'am' && $hour === 12) {
                $hour = 0;
            }
        }

        if ($hour < 0 || $hour > 23 || $minute < 0 || $minute > 59) {
            return null;
        }

        return ['hour' => $hour, 'minute' => $minute];
    }

    /**
     * When one time in a range has AM/PM and the other doesn't,
     * infer the missing AM/PM from context.
     */
    private function inferAmPm(array $unknown, array $known): array
    {
        // If the unknown hour is <= 12 and the known hour is in PM range,
        // assume unknown is also PM (common pattern: "3 to 5pm" → 3pm to 5pm)
        if ($unknown['hour'] <= 12 && $known['hour'] >= 12) {
            if ($unknown['hour'] < 12) {
                $unknown['hour'] += 12;
            }
        }

        return $unknown;
    }

    /**
     * Find the next occurrence of a given ISO weekday from reference.
     * If reference IS that weekday, returns the reference date itself.
     */
    private function findNextWeekday(CarbonImmutable $reference, int $targetIso): CarbonImmutable
    {
        $currentIso = $reference->dayOfWeekIso;

        if ($currentIso === $targetIso) {
            return $reference;
        }

        $daysAhead = ($targetIso - $currentIso + 7) % 7;

        return $reference->addDays($daysAhead);
    }

    /**
     * Find the Nth occurrence of a given ISO weekday in a month.
     * Returns null if the Nth occurrence doesn't exist.
     */
    private function findNthWeekdayInMonth(CarbonImmutable $monthStart, int $targetDayIso, int $weekOrd): ?CarbonImmutable
    {
        if ($weekOrd === -1) {
            // Last occurrence: start from end of month and go backwards
            $date = $monthStart->endOfMonth()->startOfDay();

            while ($date->dayOfWeekIso !== $targetDayIso) {
                $date = $date->subDay();
            }

            return $date;
        }

        // Find first occurrence of target weekday in the month
        $date = $monthStart->startOfMonth();

        while ($date->dayOfWeekIso !== $targetDayIso) {
            $date = $date->addDay();
        }

        // Advance to the Nth occurrence
        $date = $date->addWeeks($weekOrd - 1);

        // Validate: still in the same month?
        if ($date->month !== $monthStart->month) {
            return null;
        }

        return $date;
    }

    /**
     * Resolve day+month(+year) into a complete absolute date result.
     */
    private function resolveAbsoluteFromDayMonth(
        int $day, string $monthStr, ?string $yearStr,
        string $sourcePhrase, CarbonImmutable $reference, bool $scheduleLike
    ): ?array {
        $monthNum = $this->monthToNumber($monthStr);

        if (! $monthNum || $day < 1 || $day > 31) {
            return null;
        }

        $year = $yearStr ? (int) $yearStr : $reference->year;

        try {
            $date = CarbonImmutable::createStrict($year, $monthNum, $day, 0, 0, 0, $reference->getTimezone());

            if (! $yearStr) {
                $date = $this->inferYear($date, $reference, $scheduleLike);
            }

            return [
                'has_temporal' => true,
                'kind' => $scheduleLike ? 'event' : 'time_reference',
                'schedule_like' => $scheduleLike,
                'label' => $this->deriveLabel(strtolower($sourcePhrase), 'absolute'),
                'source_phrase' => $sourcePhrase,
                'start_at' => $date->startOfDay()->toIso8601String(),
                'end_at' => $date->endOfDay()->toIso8601String(),
                'timezone' => $reference->getTimezone()->getName(),
                'recurrence_rule' => null,
                'confidence' => $yearStr ? 0.95 : 0.90,
            ];
        } catch (Throwable) {
            return null;
        }
    }
}