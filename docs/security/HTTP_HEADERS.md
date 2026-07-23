# HTTP Security Headers

TraceMem's public-facing application and APIs enforce strict HTTP security headers by default via the `SecurityHeaders` middleware.

| Header | Value | Purpose |
|--------|-------|---------|
| `Content-Security-Policy` | Whitelists Razorpay JS/iframe; `unsafe-inline` for Inertia | Prevents Cross-Site Scripting (XSS). |
| `Strict-Transport-Security` | `max-age=31536000; includeSubDomains` | Enforces HTTPS on all connections. |
| `X-Frame-Options` | `SAMEORIGIN` | Protects against Clickjacking. |
| `X-Content-Type-Options` | `nosniff` | Prevents MIME-sniffing attacks. |
| `Referrer-Policy` | `strict-origin-when-cross-origin` | Protects referral data leaks. |
| `Permissions-Policy` | camera, mic, geolocation, payment, USB (disabled) | Restricts browser features. |
| `Cross-Origin-Embedder-Policy`| `unsafe-none` | Preserves the Razorpay iframe functionality while securing other assets. |
