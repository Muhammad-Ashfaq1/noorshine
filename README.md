# NoorShine WordPress Commerce

Production-oriented source for the NoorShine WooCommerce launch:

- `wp-content/themes/noorshine/` — responsive custom WooCommerce theme.
- `wp-content/plugins/noorshine-product-designer/` — 2D SVG product designer MVP.
- `data/` — category structure, original draft concepts, and photographed catalog descriptions and prices.
- `assets/storefront/` — optimized owner photos and homepage banner used by the production catalog.
- `scripts/` — secure, idempotent WordPress/WooCommerce REST automation.
- `docs/` — audit, research, deployment, backup, testing, and approval notes.

The photographed catalog uses WooCommerce drafts with proposed prices. Items opted into catalog previews appear publicly with ordering closed.

## Quick start

1. Copy `.env.example` to `.env` and add a revocable WordPress Application Password and WooCommerce REST keys. Never use the primary WordPress password.
2. Revoke and replace any exposed credentials before running the authenticated read-only audit with `npm run audit`. Use `npm run audit -- --public` to check discovery anonymously.
3. Create a Hostinger backup and follow `docs/DEPLOYMENT.md`.
4. Use `npm run categories:sync -- --dry-run` and `npm run products:import -- --dry-run` before any live write. Add `--offline` to plan from the local catalog without contacting the website.

See `docs/OWNER-APPROVALS.md` for the decisions still required.

## Photographed catalog

Run `node scripts/owner-photo-catalog.mjs check` to review the live import state. The `apply` mode uses the WebP files in `assets/storefront/`. Add `--enable-catalog-preview` to make the photographed rows eligible for public, non-purchasable previews.

To regenerate crops from archived originals on macOS, point `NOORSHINE_OWNER_SOURCE_DIR` to the recovered `captured-images` directory and run `node scripts/owner-photo-catalog.mjs prepare /path/to/banner.png`. Review the files in `dist/owner-catalog/` before replacing their optimized counterparts.
