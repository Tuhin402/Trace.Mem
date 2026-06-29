<?php

namespace App\Services\Email;

/**
 * EmailTheme — single source of truth for all Trace.Mem email brand tokens.
 *
 * Every Blade template reads colors, typography, and spacing from this class.
 * When the Trace.Mem brand evolves, update this file and every email updates
 * automatically — no hunting through individual templates.
 *
 * Usage in Blade:
 *   {{ $theme::background() }}   or   {{ \App\Services\Email\EmailTheme::background() }}
 *
 * The EmailServiceProvider shares $theme as a view variable to all emails.* views.
 */
final class EmailTheme
{
    // ── Background / Surface ──────────────────────────────────────────────────

    /** Page / email background — deepest dark */
    public static function background(): string   { return '#0A0A0B'; }

    /** Card / container surface */
    public static function surface(): string      { return '#111113'; }

    /** Elevated card surface — used for nested info blocks */
    public static function surfaceElevated(): string { return '#18181B'; }

    /** Default border color */
    public static function border(): string       { return '#27272A'; }

    /** Subtle border / divider */
    public static function borderSubtle(): string { return '#1F1F23'; }

    // ── Brand Colors ──────────────────────────────────────────────────────────

    /** Primary brand — violet-400 */
    public static function primary(): string      { return '#A78BFA'; }

    /** Primary dark — violet-600 (used inside CTA buttons) */
    public static function primaryDark(): string  { return '#7C3AED'; }

    /** Primary hover — violet-500 */
    public static function primaryHover(): string { return '#8B5CF6'; }

    // ── Semantic Colors ───────────────────────────────────────────────────────

    /** Success green — emerald-400 */
    public static function success(): string      { return '#34D399'; }

    /** Success background tint */
    public static function successBg(): string    { return '#052E16'; }

    /** Warning amber — amber-400 */
    public static function warning(): string      { return '#FBBF24'; }

    /** Warning background tint */
    public static function warningBg(): string    { return '#1C1A08'; }

    /** Danger red — red-400 */
    public static function danger(): string       { return '#F87171'; }

    /** Danger background tint */
    public static function dangerBg(): string     { return '#200A0A'; }

    /** Info blue — blue-400 */
    public static function info(): string         { return '#60A5FA'; }

    /** Info background tint */
    public static function infoBg(): string       { return '#0A1628'; }

    // ── Typography ────────────────────────────────────────────────────────────

    /** Primary text — near-white */
    public static function text(): string         { return '#FAFAFA'; }

    /** Muted text — zinc-400 */
    public static function textMuted(): string    { return '#A1A1AA'; }

    /** Subtle text — zinc-500 */
    public static function textSubtle(): string   { return '#71717A'; }

    /** System font stack — no web font dependency */
    public static function fontFamily(): string
    {
        return "-apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif";
    }

    public static function fontSizeBase(): string  { return '15px'; }
    public static function fontSizeLarge(): string { return '18px'; }
    public static function fontSizeSmall(): string { return '13px'; }
    public static function fontSizeTiny(): string  { return '12px'; }
    public static function lineHeight(): string    { return '1.65'; }

    // ── Spacing & Layout ──────────────────────────────────────────────────────

    /** Maximum email width */
    public static function containerWidth(): string { return '600px'; }

    /** Outer section padding */
    public static function paddingOuter(): string   { return '40px 24px'; }

    /** Card / content block padding */
    public static function paddingCard(): string    { return '32px'; }

    /** Compact info-block padding */
    public static function paddingBlock(): string   { return '16px 20px'; }

    /** CTA button padding */
    public static function paddingButton(): string  { return '14px 28px'; }

    /** Standard section gap */
    public static function gap(): string            { return '24px'; }

    /** Border radius for cards */
    public static function radius(): string         { return '8px'; }

    /** Border radius for buttons */
    public static function radiusButton(): string   { return '6px'; }
}
