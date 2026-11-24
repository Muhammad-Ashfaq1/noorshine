# Test plan

## Automated/local

- `npm run catalog:validate`
- `npm run check` when PHP CLI is available
- `npm test` for HTTPS, write gates and duplicate prevention with mocked REST responses.
- `wp eval-file /absolute/path/to/tests/wordpress-smoke.php` on a fresh loopback WordPress install with `WP_ENVIRONMENT_TYPE` set to `local`. This creates an unpaid fixture order and tests variation validation, pricing and order metadata.
- Start the local WordPress server on port 8719 and an isolated headless Chrome with `--remote-debugging-port=8720`. After loading the WordPress fixture, run `node tests/browser-smoke.mjs` with Node 22 or newer. Screenshots are saved in `dist/qa/`.
- `npm run audit` with both WordPress and WooCommerce credentials
- `npm run inventory:validate` after draft import
- Re-run category and product dry runs to confirm they preserve matching records and flag conflicts.

## Storefront

- Homepage at 320, 375, 768, 1024 and 1440 px; keyboard navigation; visible focus; reduced motion.
- Shop filters/sorting and every category landing page.
- Product gallery, variations, price display only for approved data, add-to-cart and stock behavior.
- Cart totals, coupons if enabled, guest and account checkout in gateway test mode only.
- Customer registration, login, password reset and My Account order view.
- Transactional emails to controlled test accounts; verify SPF/DKIM/DMARC separately.

## Product Designer

- All admin options persist with capability and nonce checks.
- Preview changes for style/template/material/finish/size/text on mobile and desktop.
- Unchecked custom-text attestation is rejected.
- Unlisted/tampered option values are rejected server-side.
- Configuration is visible in cart and stored on the order line.
- Price adjustments occur only when owner-approved rules are enabled.
- Free text requires customer attestation and is stored as a private order customization subject to manual review. Only administrator-confirmed templates should supply sacred text for the preview.

## Security and reliability

- Test authenticated REST read/write with the dedicated user and confirm least privilege.
- Verify XSS payloads are sanitized/escaped and CSRF requests fail without valid nonces.
- Confirm customer cannot change designer settings or production status.
- Confirm rate limits/retries do not create duplicates.
- Run plugin/theme vulnerability and WordPress Site Health checks.
- Verify backups restore on staging.

## SEO and performance

- Unique titles/descriptions, canonical URLs, breadcrumbs, XML sitemap and noindex for drafts.
- Product structured data contains only real price, availability and reviews.
- Validate with Rich Results and schema tools after publication.
- Measure LCP/INP/CLS on real mobile hardware or throttled Lighthouse after real images are installed.
- Confirm WebP/AVIF sizing, lazy loading below the fold, cache exclusions and no mixed content.

