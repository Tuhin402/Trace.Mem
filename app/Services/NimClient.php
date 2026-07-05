<?php

namespace App\Services;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

/**
 * NimClient — centralised HTTP transport for all NVIDIA NIM API calls.
 *
 * WHY THIS EXISTS
 * ───────────────
 * PHP's Guzzle (used by Laravel's Http facade) automatically adds the
 * "Expect: 100-continue" header to every POST request whose body is
 * larger than 1 024 bytes. NVIDIA NIM's API gateway (Cloudflare) does
 * not issue the 100 Continue intermediate response before reading the
 * request body. Guzzle therefore stalls until its timeout expires —
 * producing "cURL error 28: Operation timed out after Xms, 0 bytes
 * received" on every /chat/completions call, regardless of model or
 * payload content.
 *
 * GET /models is unaffected because GETs carry no body, so Guzzle
 * never attaches the Expect header. That is why authentication tests
 * pass while inference calls silently hang.
 *
 * FIXES APPLIED
 * ─────────────
 * 1. 'expect' => false            — suppresses Expect: 100-continue (PRIMARY)
 * 2. 'decode_content' => false    — prevents gzip decompression stalls on
 *                                   chunked inference streams
 * 3. CURLOPT_HTTP_VERSION_1_1     — pins to HTTP/1.1 to avoid HTTP/2 frame
 *                                   handling edge cases in some libcurl builds
 * 4. connectTimeout(10)           — separates connect timeout from read timeout
 *                                   so a TCP hang is detected in ≤ 10 s
 *
 * All callers pass only a $timeout (the read/transfer timeout) and a $payload.
 * Transport concerns never leak into business logic.
 */
class NimClient
{
    /**
     * POST to /chat/completions with all transport fixes applied.
     *
     * @param  array  $payload   Full request body (model, messages, …)
     * @param  int    $timeout   Read/transfer timeout in seconds
     * @return Response
     */
    public function completions(array $payload, int $timeout = 30): Response
    {
        return Http::withToken(config('services.nvidia_nim_openai.api_key'))
            ->acceptJson()
            ->withHeaders([
                'Content-Type' => 'application/json',
            ])
            ->withOptions([
                // ── PRIMARY FIX ──────────────────────────────────────────────
                // Prevent Guzzle from sending "Expect: 100-continue".
                // NVIDIA's API gateway never responds to the 100 handshake,
                // causing Guzzle to stall for the full timeout with 0 bytes read.
                'expect' => false,

                // ── SECONDARY FIX ────────────────────────────────────────────
                // Disable automatic gzip decompression.
                // NIM inference streams use chunked transfer encoding;
                // combining that with on-the-fly decompression can stall
                // the read loop when chunks arrive slowly.
                'decode_content' => false,

                // ── BELT-AND-SUSPENDERS ──────────────────────────────────────
                // Explicitly pin to HTTP/1.1.
                // Some libcurl builds exhibit HTTP/2 multiplexing edge cases
                // (zombie streams, SETTINGS frame stalls) on long inference
                // connections. HTTP/1.1 is predictable and widely tested.
                'curl' => [
                    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                ],
            ])
            ->connectTimeout(10)  // TCP connect limit (independent of read timeout)
            ->timeout($timeout)   // Total read/transfer limit
            ->post(
                config('services.nvidia_nim_openai.base_url') . '/chat/completions',
                $payload
            );
    }
}
