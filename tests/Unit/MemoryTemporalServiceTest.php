<?php

use App\Services\Memory\MemoryTemporalService;
use Carbon\CarbonImmutable;

// Fixed reference date for deterministic tests: Tuesday, June 10, 2026 at 12:00 UTC
$ref = CarbonImmutable::create(2026, 6, 10, 12, 0, 0, 'UTC');

// ═════════════════════════════════════════════════════════════
//  Empty / No Temporal
// ═════════════════════════════════════════════════════════════

test('empty string returns no temporal', function () use ($ref) {
    $service = new MemoryTemporalService();
    $result = $service->extract('', $ref);

    expect($result['has_temporal'])->toBeFalse();
    expect($result['confidence'])->toBe(0.0);
});

test('non-temporal text returns no temporal', function () use ($ref) {
    $service = new MemoryTemporalService();
    $result = $service->extract('I like pizza and dark mode', $ref);

    expect($result['has_temporal'])->toBeFalse();
});

// ═════════════════════════════════════════════════════════════
//  Recurring
// ═════════════════════════════════════════════════════════════

test('detects daily recurrence', function () use ($ref) {
    $service = new MemoryTemporalService();
    $result = $service->extract('standup every day at 9am', $ref);

    expect($result['has_temporal'])->toBeTrue();
    expect($result['kind'])->toBe('recurring');
    expect($result['recurrence_rule'])->toContain('FREQ=DAILY');
});

test('detects weekly recurrence', function () use ($ref) {
    $service = new MemoryTemporalService();
    $result = $service->extract('team sync weekly', $ref);

    expect($result['has_temporal'])->toBeTrue();
    expect($result['kind'])->toBe('recurring');
    expect($result['recurrence_rule'])->toContain('FREQ=WEEKLY');
});

test('detects monthly recurrence', function () use ($ref) {
    $service = new MemoryTemporalService();
    $result = $service->extract('pay rent monthly', $ref);

    expect($result['has_temporal'])->toBeTrue();
    expect($result['recurrence_rule'])->toContain('FREQ=MONTHLY');
});

test('detects quarterly recurrence', function () use ($ref) {
    $service = new MemoryTemporalService();
    $result = $service->extract('quarterly review meeting', $ref);

    expect($result['has_temporal'])->toBeTrue();
    expect($result['recurrence_rule'])->toContain('FREQ=QUARTERLY');
});

test('detects yearly recurrence', function () use ($ref) {
    $service = new MemoryTemporalService();
    $result = $service->extract('annually renew subscription', $ref);

    expect($result['has_temporal'])->toBeTrue();
    expect($result['recurrence_rule'])->toContain('FREQ=YEARLY');
});

test('detects every specific weekday', function () use ($ref) {
    $service = new MemoryTemporalService();
    $result = $service->extract('standup every monday', $ref);

    expect($result['has_temporal'])->toBeTrue();
    expect($result['kind'])->toBe('recurring');
    expect($result['recurrence_rule'])->toContain('BYDAY=MO');
});

test('detects every weekday', function () use ($ref) {
    $service = new MemoryTemporalService();
    $result = $service->extract('daily standup every weekday', $ref);

    expect($result['has_temporal'])->toBeTrue();
    expect($result['recurrence_rule'])->toContain('BYDAY=MO,TU,WE,TH,FR');
});

test('detects biweekly/fortnightly', function () use ($ref) {
    $service = new MemoryTemporalService();
    $result = $service->extract('biweekly sprint planning', $ref);

    expect($result['has_temporal'])->toBeTrue();
    expect($result['recurrence_rule'])->toContain('FREQ=WEEKLY');
    expect($result['recurrence_rule'])->toContain('INTERVAL=2');
});

test('detects every N weeks', function () use ($ref) {
    $service = new MemoryTemporalService();
    $result = $service->extract('report every 3 weeks', $ref);

    expect($result['has_temporal'])->toBeTrue();
    expect($result['recurrence_rule'])->toContain('FREQ=WEEKLY');
    expect($result['recurrence_rule'])->toContain('INTERVAL=3');
});

test('detects every other monday', function () use ($ref) {
    $service = new MemoryTemporalService();
    $result = $service->extract('meeting every other monday', $ref);

    expect($result['has_temporal'])->toBeTrue();
    expect($result['recurrence_rule'])->toContain('BYDAY=MO');
    expect($result['recurrence_rule'])->toContain('INTERVAL=2');
});

// ═════════════════════════════════════════════════════════════
//  Relative Offset
// ═════════════════════════════════════════════════════════════

test('detects "in 3 days"', function () use ($ref) {
    $service = new MemoryTemporalService();
    $result = $service->extract('deadline in 3 days', $ref);

    expect($result['has_temporal'])->toBeTrue();
    $start = CarbonImmutable::parse($result['start_at']);
    expect($start->toDateString())->toBe('2026-06-13');
});

test('detects "after 2 weeks"', function () use ($ref) {
    $service = new MemoryTemporalService();
    $result = $service->extract('follow up after 2 weeks', $ref);

    expect($result['has_temporal'])->toBeTrue();
    $start = CarbonImmutable::parse($result['start_at']);
    expect($start->toDateString())->toBe('2026-06-24');
});

test('detects "a month from now"', function () use ($ref) {
    $service = new MemoryTemporalService();
    $result = $service->extract('renew subscription a month from now', $ref);

    expect($result['has_temporal'])->toBeTrue();
    $start = CarbonImmutable::parse($result['start_at']);
    expect($start->month)->toBe(7);
});

test('detects "5 days from today"', function () use ($ref) {
    $service = new MemoryTemporalService();
    $result = $service->extract('submit assignment 5 days from today', $ref);

    expect($result['has_temporal'])->toBeTrue();
    $start = CarbonImmutable::parse($result['start_at']);
    expect($start->toDateString())->toBe('2026-06-15');
});

// ═════════════════════════════════════════════════════════════
//  Ordinal Week Day
// ═════════════════════════════════════════════════════════════

test('detects "2nd week 2nd day of this month"', function () use ($ref) {
    $service = new MemoryTemporalService();
    $result = $service->extract("meeting on 2nd week's 2nd day of this month", $ref);

    expect($result['has_temporal'])->toBeTrue();
    $start = CarbonImmutable::parse($result['start_at']);
    // June 2026: 1st week = days 1-7, 2nd week = days 8-14, 2nd day = day 9
    expect($start->toDateString())->toBe('2026-06-09');
});

test('detects "first week monday of july"', function () use ($ref) {
    $service = new MemoryTemporalService();
    $result = $service->extract('exam first week monday of july', $ref);

    expect($result['has_temporal'])->toBeTrue();
    $start = CarbonImmutable::parse($result['start_at']);
    // July 2026: July 1 is Wednesday, first Monday = July 6
    expect($start->toDateString())->toBe('2026-07-06');
});

test('detects "3rd week of next month"', function () use ($ref) {
    $service = new MemoryTemporalService();
    $result = $service->extract('meeting 3rd week of next month', $ref);

    expect($result['has_temporal'])->toBeTrue();
    $start = CarbonImmutable::parse($result['start_at']);
    // Next month = July 2026, 3rd week = days 15-21, default day 1 (Monday)
    // 3rd week's Monday: find 3rd Monday in July
    // July 1 = Wed, 1st Mon = Jul 6, 2nd Mon = Jul 13, 3rd Mon = Jul 20
    expect($start->toDateString())->toBe('2026-07-20');
});

test('detects "last week friday of december"', function () use ($ref) {
    $service = new MemoryTemporalService();
    $result = $service->extract('party last week friday of december', $ref);

    expect($result['has_temporal'])->toBeTrue();
    $start = CarbonImmutable::parse($result['start_at']);
    // December 2026: Dec 31 = Thursday, last Friday = Dec 25
    expect($start->toDateString())->toBe('2026-12-25');
});

// ═════════════════════════════════════════════════════════════
//  Relative Range
// ═════════════════════════════════════════════════════════════

test('detects "today"', function () use ($ref) {
    $service = new MemoryTemporalService();
    $result = $service->extract('meeting today', $ref);

    expect($result['has_temporal'])->toBeTrue();
    $start = CarbonImmutable::parse($result['start_at']);
    expect($start->toDateString())->toBe('2026-06-10');
});

test('detects "tomorrow"', function () use ($ref) {
    $service = new MemoryTemporalService();
    $result = $service->extract('class tomorrow', $ref);

    expect($result['has_temporal'])->toBeTrue();
    $start = CarbonImmutable::parse($result['start_at']);
    expect($start->toDateString())->toBe('2026-06-11');
});

test('detects "this week"', function () use ($ref) {
    $service = new MemoryTemporalService();
    $result = $service->extract('finish report this week', $ref);

    expect($result['has_temporal'])->toBeTrue();
    expect($result['kind'])->toBe('range');
});

test('detects "tonight"', function () use ($ref) {
    $service = new MemoryTemporalService();
    $result = $service->extract('dinner tonight', $ref);

    expect($result['has_temporal'])->toBeTrue();
    $start = CarbonImmutable::parse($result['start_at']);
    expect($start->hour)->toBe(18);
});

test('detects "this morning"', function () use ($ref) {
    $service = new MemoryTemporalService();
    $result = $service->extract('meeting this morning', $ref);

    expect($result['has_temporal'])->toBeTrue();
    $start = CarbonImmutable::parse($result['start_at']);
    expect($start->hour)->toBe(6);
});

test('detects "this weekend"', function () use ($ref) {
    $service = new MemoryTemporalService();
    $result = $service->extract('trip this weekend', $ref);

    expect($result['has_temporal'])->toBeTrue();
    expect($result['kind'])->toBe('range');
    $start = CarbonImmutable::parse($result['start_at']);
    // June 10 is Tuesday → this weekend is Saturday June 13
    expect($start->dayOfWeek)->toBe(CarbonImmutable::SATURDAY);
});

test('detects "this quarter"', function () use ($ref) {
    $service = new MemoryTemporalService();
    $result = $service->extract('goals for this quarter', $ref);

    expect($result['has_temporal'])->toBeTrue();
    expect($result['kind'])->toBe('range');
});

// ═════════════════════════════════════════════════════════════
//  Informal Relative
// ═════════════════════════════════════════════════════════════

test('detects "day after tomorrow"', function () use ($ref) {
    $service = new MemoryTemporalService();
    $result = $service->extract('flight day after tomorrow', $ref);

    expect($result['has_temporal'])->toBeTrue();
    $start = CarbonImmutable::parse($result['start_at']);
    expect($start->toDateString())->toBe('2026-06-12');
});

test('detects "day before yesterday"', function () use ($ref) {
    $service = new MemoryTemporalService();
    $result = $service->extract('submitted day before yesterday', $ref);

    expect($result['has_temporal'])->toBeTrue();
    $start = CarbonImmutable::parse($result['start_at']);
    expect($start->toDateString())->toBe('2026-06-08');
});

test('detects "3 days ago"', function () use ($ref) {
    $service = new MemoryTemporalService();
    $result = $service->extract('started project 3 days ago', $ref);

    expect($result['has_temporal'])->toBeTrue();
    $start = CarbonImmutable::parse($result['start_at']);
    expect($start->toDateString())->toBe('2026-06-07');
});

test('detects "2 months ago"', function () use ($ref) {
    $service = new MemoryTemporalService();
    $result = $service->extract('joined the team 2 months ago', $ref);

    expect($result['has_temporal'])->toBeTrue();
    $start = CarbonImmutable::parse($result['start_at']);
    expect($start->month)->toBe(4);
});

test('detects "end of this month"', function () use ($ref) {
    $service = new MemoryTemporalService();
    $result = $service->extract('deadline end of this month', $ref);

    expect($result['has_temporal'])->toBeTrue();
    $end = CarbonImmutable::parse($result['end_at']);
    expect($end->toDateString())->toBe('2026-06-30');
});

test('detects "beginning of next month"', function () use ($ref) {
    $service = new MemoryTemporalService();
    $result = $service->extract('start project beginning of next month', $ref);

    expect($result['has_temporal'])->toBeTrue();
    $start = CarbonImmutable::parse($result['start_at']);
    expect($start->toDateString())->toBe('2026-07-01');
});

test('detects "mid-july"', function () use ($ref) {
    $service = new MemoryTemporalService();
    $result = $service->extract('vacation mid-july', $ref);

    expect($result['has_temporal'])->toBeTrue();
    // Mid-July = approximately July 12-18
    $start = CarbonImmutable::parse($result['start_at']);
    expect($start->month)->toBe(7);
});

test('detects "coming friday"', function () use ($ref) {
    $service = new MemoryTemporalService();
    $result = $service->extract('party this coming friday', $ref);

    expect($result['has_temporal'])->toBeTrue();
    $start = CarbonImmutable::parse($result['start_at']);
    expect($start->dayOfWeek)->toBe(CarbonImmutable::FRIDAY);
});

// ═════════════════════════════════════════════════════════════
//  Relative Weekday
// ═════════════════════════════════════════════════════════════

test('detects "this monday"', function () use ($ref) {
    $service = new MemoryTemporalService();
    $result = $service->extract('meeting this monday', $ref);

    expect($result['has_temporal'])->toBeTrue();
    $start = CarbonImmutable::parse($result['start_at']);
    expect($start->dayOfWeek)->toBe(CarbonImmutable::MONDAY);
});

test('detects "next friday"', function () use ($ref) {
    $service = new MemoryTemporalService();
    $result = $service->extract('party next friday', $ref);

    expect($result['has_temporal'])->toBeTrue();
    $start = CarbonImmutable::parse($result['start_at']);
    expect($start->dayOfWeek)->toBe(CarbonImmutable::FRIDAY);
});

test('detects "on tuesday" with schedule context', function () use ($ref) {
    $service = new MemoryTemporalService();
    $result = $service->extract('exam on tuesday', $ref);

    expect($result['has_temporal'])->toBeTrue();
    $start = CarbonImmutable::parse($result['start_at']);
    expect($start->dayOfWeekIso)->toBe(2); // Tuesday
});

test('detects bare weekday with schedule context', function () use ($ref) {
    $service = new MemoryTemporalService();
    $result = $service->extract('meeting wednesday', $ref);

    expect($result['has_temporal'])->toBeTrue();
    $start = CarbonImmutable::parse($result['start_at']);
    expect($start->dayOfWeekIso)->toBe(3); // Wednesday
});

// ═════════════════════════════════════════════════════════════
//  Absolute Date
// ═════════════════════════════════════════════════════════════

test('detects "15 june 2026"', function () use ($ref) {
    $service = new MemoryTemporalService();
    $result = $service->extract('exam on 15 june 2026', $ref);

    expect($result['has_temporal'])->toBeTrue();
    $start = CarbonImmutable::parse($result['start_at']);
    expect($start->toDateString())->toBe('2026-06-15');
});

test('detects "june 15"', function () use ($ref) {
    $service = new MemoryTemporalService();
    $result = $service->extract('deadline june 15', $ref);

    expect($result['has_temporal'])->toBeTrue();
    $start = CarbonImmutable::parse($result['start_at']);
    expect($start->day)->toBe(15);
    expect($start->month)->toBe(6);
});

test('detects ISO date "2026-06-15"', function () use ($ref) {
    $service = new MemoryTemporalService();
    $result = $service->extract('meeting on 2026-06-15', $ref);

    expect($result['has_temporal'])->toBeTrue();
    $start = CarbonImmutable::parse($result['start_at']);
    expect($start->toDateString())->toBe('2026-06-15');
    expect($result['confidence'])->toBeGreaterThanOrEqual(0.95);
});

test('detects slash date DD/MM/YYYY', function () use ($ref) {
    $service = new MemoryTemporalService();
    $result = $service->extract('appointment on 15/06/2026', $ref);

    expect($result['has_temporal'])->toBeTrue();
    $start = CarbonImmutable::parse($result['start_at']);
    expect($start->toDateString())->toBe('2026-06-15');
});

test('uses DD/MM for ambiguous dates', function () use ($ref) {
    $service = new MemoryTemporalService();
    $result = $service->extract('meeting 01/02/2026', $ref);

    expect($result['has_temporal'])->toBeTrue();
    $start = CarbonImmutable::parse($result['start_at']);
    // DD/MM → day=1, month=2 → February 1
    expect($start->month)->toBe(2);
    expect($start->day)->toBe(1);
});

test('detects month-only "in june"', function () use ($ref) {
    $service = new MemoryTemporalService();
    $result = $service->extract('vacation in june', $ref);

    expect($result['has_temporal'])->toBeTrue();
    expect($result['kind'])->toBe('range');
    $start = CarbonImmutable::parse($result['start_at']);
    expect($start->month)->toBe(6);
});

test('detects month+year "march 2027"', function () use ($ref) {
    $service = new MemoryTemporalService();
    $result = $service->extract('launch planned march 2027', $ref);

    expect($result['has_temporal'])->toBeTrue();
    $start = CarbonImmutable::parse($result['start_at']);
    expect($start->month)->toBe(3);
    expect($start->year)->toBe(2027);
});

test('detects year-only "in 2027"', function () use ($ref) {
    $service = new MemoryTemporalService();
    $result = $service->extract('goal to finish in 2027', $ref);

    expect($result['has_temporal'])->toBeTrue();
    expect($result['kind'])->toBe('range');
    $start = CarbonImmutable::parse($result['start_at']);
    expect($start->year)->toBe(2027);
});

// ═════════════════════════════════════════════════════════════
//  Time Range
// ═════════════════════════════════════════════════════════════

test('detects "3pm to 5pm"', function () use ($ref) {
    $service = new MemoryTemporalService();
    $result = $service->extract('meeting 3pm to 5pm', $ref);

    expect($result['has_temporal'])->toBeTrue();
    $start = CarbonImmutable::parse($result['start_at']);
    $end = CarbonImmutable::parse($result['end_at']);
    expect($start->hour)->toBe(15);
    expect($end->hour)->toBe(17);
});

test('detects "between 9am and 11am"', function () use ($ref) {
    $service = new MemoryTemporalService();
    $result = $service->extract('class between 9am and 11am', $ref);

    expect($result['has_temporal'])->toBeTrue();
    $start = CarbonImmutable::parse($result['start_at']);
    $end = CarbonImmutable::parse($result['end_at']);
    expect($start->hour)->toBe(9);
    expect($end->hour)->toBe(11);
});

// ═════════════════════════════════════════════════════════════
//  Time Only
// ═════════════════════════════════════════════════════════════

test('detects "3pm"', function () use ($ref) {
    $service = new MemoryTemporalService();
    $result = $service->extract('reminder at 3pm', $ref);

    expect($result['has_temporal'])->toBeTrue();
    $start = CarbonImmutable::parse($result['start_at']);
    expect($start->hour)->toBe(15);
});

test('detects 24-hour time "14:30"', function () use ($ref) {
    $service = new MemoryTemporalService();
    $result = $service->extract('meeting at 14:30', $ref);

    expect($result['has_temporal'])->toBeTrue();
    $start = CarbonImmutable::parse($result['start_at']);
    expect($start->hour)->toBe(14);
    expect($start->minute)->toBe(30);
});

test('detects "at noon"', function () use ($ref) {
    $service = new MemoryTemporalService();
    $result = $service->extract('lunch at noon', $ref);

    expect($result['has_temporal'])->toBeTrue();
    $start = CarbonImmutable::parse($result['start_at']);
    expect($start->hour)->toBe(12);
});

test('detects "at midnight"', function () use ($ref) {
    $service = new MemoryTemporalService();
    $result = $service->extract('deadline at midnight', $ref);

    expect($result['has_temporal'])->toBeTrue();
    $start = CarbonImmutable::parse($result['start_at']);
    expect($start->hour)->toBe(0);
});

// ═════════════════════════════════════════════════════════════
//  Season / Holiday
// ═════════════════════════════════════════════════════════════

test('detects "this summer"', function () use ($ref) {
    $service = new MemoryTemporalService();
    $result = $service->extract('internship this summer', $ref);

    expect($result['has_temporal'])->toBeTrue();
    expect($result['kind'])->toBe('range');
    $start = CarbonImmutable::parse($result['start_at']);
    expect($start->month)->toBe(6);
});

test('detects "christmas"', function () use ($ref) {
    $service = new MemoryTemporalService();
    $result = $service->extract('family gathering on christmas', $ref);

    expect($result['has_temporal'])->toBeTrue();
    $start = CarbonImmutable::parse($result['start_at']);
    expect($start->month)->toBe(12);
    expect($start->day)->toBe(25);
});

// ═════════════════════════════════════════════════════════════
//  Time Enrichment (date + time combination)
// ═════════════════════════════════════════════════════════════

test('enriches day-level detection with time', function () use ($ref) {
    $service = new MemoryTemporalService();
    $result = $service->extract('meeting tomorrow at 3pm', $ref);

    expect($result['has_temporal'])->toBeTrue();
    $start = CarbonImmutable::parse($result['start_at']);
    expect($start->toDateString())->toBe('2026-06-11');
    expect($start->hour)->toBe(15);
});

test('enriches with morning qualifier', function () use ($ref) {
    $service = new MemoryTemporalService();
    $result = $service->extract('class tomorrow morning', $ref);

    expect($result['has_temporal'])->toBeTrue();
    $start = CarbonImmutable::parse($result['start_at']);
    expect($start->toDateString())->toBe('2026-06-11');
    expect($start->hour)->toBe(9);
});

// ═════════════════════════════════════════════════════════════
//  Duration Enrichment
// ═════════════════════════════════════════════════════════════

test('enriches with duration "for 2 hours"', function () use ($ref) {
    $service = new MemoryTemporalService();
    $result = $service->extract('meeting tomorrow at 3pm for 2 hours', $ref);

    expect($result['has_temporal'])->toBeTrue();
    $start = CarbonImmutable::parse($result['start_at']);
    $end = CarbonImmutable::parse($result['end_at']);
    expect($start->hour)->toBe(15);
    expect($end->hour)->toBe(17);
});

test('enriches with duration "for 30 minutes"', function () use ($ref) {
    $service = new MemoryTemporalService();
    $result = $service->extract('call today at 2pm for 30 minutes', $ref);

    expect($result['has_temporal'])->toBeTrue();
    $start = CarbonImmutable::parse($result['start_at']);
    $end = CarbonImmutable::parse($result['end_at']);
    expect($start->hour)->toBe(14);
    expect($end->hour)->toBe(14);
    expect($end->minute)->toBe(30);
});

// ═════════════════════════════════════════════════════════════
//  Year Inference
// ═════════════════════════════════════════════════════════════

test('infers next year for past schedule date', function () use ($ref) {
    $service = new MemoryTemporalService();
    // January 5 is well in the past for June 10 ref, and has schedule context
    $result = $service->extract('exam on 5 january', $ref);

    expect($result['has_temporal'])->toBeTrue();
    $start = CarbonImmutable::parse($result['start_at']);
    // January 5 is > 7 days past + schedule_like=true → advance to 2027
    expect($start->year)->toBe(2027);
});

test('keeps current year for near-past date', function () use ($ref) {
    $service = new MemoryTemporalService();
    // June 8 is only 2 days ago — should stay in 2026
    $result = $service->extract('submitted assignment 8 june', $ref);

    expect($result['has_temporal'])->toBeTrue();
    $start = CarbonImmutable::parse($result['start_at']);
    expect($start->year)->toBe(2026);
});

test('keeps current year for future date', function () use ($ref) {
    $service = new MemoryTemporalService();
    $result = $service->extract('exam on 20 june', $ref);

    expect($result['has_temporal'])->toBeTrue();
    $start = CarbonImmutable::parse($result['start_at']);
    expect($start->year)->toBe(2026);
    expect($start->toDateString())->toBe('2026-06-20');
});

// ═════════════════════════════════════════════════════════════
//  Schedule Intent Detection
// ═════════════════════════════════════════════════════════════

test('detects student schedule intent', function () use ($ref) {
    $service = new MemoryTemporalService();
    $result = $service->extract('exam on monday', $ref);

    expect($result['has_temporal'])->toBeTrue();
    expect($result['schedule_like'])->toBeTrue();
});

test('detects office schedule intent', function () use ($ref) {
    $service = new MemoryTemporalService();
    $result = $service->extract('standup at 9am', $ref);

    expect($result['has_temporal'])->toBeTrue();
    expect($result['schedule_like'])->toBeTrue();
});

test('detects personal schedule intent', function () use ($ref) {
    $service = new MemoryTemporalService();
    $result = $service->extract('birthday party next saturday', $ref);

    expect($result['has_temporal'])->toBeTrue();
    expect($result['schedule_like'])->toBeTrue();
});

test('detects presentation schedule intent', function () use ($ref) {
    $service = new MemoryTemporalService();
    $result = $service->extract('presentation tomorrow at 10am', $ref);

    expect($result['has_temporal'])->toBeTrue();
    expect($result['schedule_like'])->toBeTrue();
});

// ═════════════════════════════════════════════════════════════
//  Text-speak normalization
// ═════════════════════════════════════════════════════════════

test('handles text-speak "tmrw"', function () use ($ref) {
    $service = new MemoryTemporalService();
    $result = $service->extract('meeting tmrw', $ref);

    expect($result['has_temporal'])->toBeTrue();
    $start = CarbonImmutable::parse($result['start_at']);
    expect($start->toDateString())->toBe('2026-06-11');
});

test('handles text-speak "2day"', function () use ($ref) {
    $service = new MemoryTemporalService();
    $result = $service->extract('call 2day', $ref);

    expect($result['has_temporal'])->toBeTrue();
    $start = CarbonImmutable::parse($result['start_at']);
    expect($start->toDateString())->toBe('2026-06-10');
});

// ═════════════════════════════════════════════════════════════
//  Context-aware Labels
// ═════════════════════════════════════════════════════════════

test('derives meeting label', function () use ($ref) {
    $service = new MemoryTemporalService();
    $result = $service->extract('team meeting tomorrow', $ref);

    expect($result['label'])->toBe('Meeting');
});

test('derives exam label', function () use ($ref) {
    $service = new MemoryTemporalService();
    $result = $service->extract('math exam next monday', $ref);

    expect($result['label'])->toBe('Exam');
});

test('derives birthday label', function () use ($ref) {
    $service = new MemoryTemporalService();
    $result = $service->extract('birthday party on 25 june', $ref);

    expect($result['label'])->toBe('Birthday');
});

// ═════════════════════════════════════════════════════════════
//  Return Shape Consistency
// ═════════════════════════════════════════════════════════════

test('return shape has all required keys', function () use ($ref) {
    $service = new MemoryTemporalService();
    $result = $service->extract('meeting tomorrow at 3pm for 2 hours', $ref);

    expect($result)->toHaveKeys([
        'has_temporal',
        'kind',
        'schedule_like',
        'label',
        'source_phrase',
        'start_at',
        'end_at',
        'timezone',
        'recurrence_rule',
        'confidence',
    ]);
});

test('empty result has same keys as temporal result', function () use ($ref) {
    $service = new MemoryTemporalService();
    $empty = $service->extract('I like pizza', $ref);
    $temporal = $service->extract('meeting tomorrow', $ref);

    expect(array_keys($empty))->toBe(array_keys($temporal));
});
