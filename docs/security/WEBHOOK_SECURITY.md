# Webhook Security

TraceMem relies on webhooks to handle asynchronous billing and transactional email events. To prevent malicious actors from spoofing these events, all webhooks are strictly verified.

## Razorpay (Billing)

When Razorpay sends an event (e.g., `subscription.charged`):
1. TraceMem reads the `X-Razorpay-Signature` header.
2. It computes `HMAC-SHA256(rawBody, secret)`.
3. It uses `hash_equals()` (a timing-safe comparison function) to verify the signature.
4. Invalid signatures immediately return a `400 Bad Request`.

## Resend (Email via Svix)

When Resend sends a delivery or bounce event via Svix:
1. TraceMem reads the `svix-signature`, `svix-id`, and `svix-timestamp` headers.
2. It computes `HMAC-SHA256(svix-id.svix-timestamp.rawBody, secret)`.
3. It uses `hash_equals()` to verify the signature.
4. Invalid signatures immediately return a `400 Bad Request`.
