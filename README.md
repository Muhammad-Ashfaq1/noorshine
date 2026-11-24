# NoorShine WordPress Commerce

Production-oriented source for the NoorShine WooCommerce launch:

- `wp-content/themes/noorshine/` — responsive custom WooCommerce theme.
- `wp-content/plugins/noorshine-product-designer/` — 2D SVG product designer MVP.
- `data/` — category structure and 40 original draft product concepts.
- `scripts/` — secure, idempotent WordPress/WooCommerce REST automation.
- `docs/` — audit, research, deployment, backup, testing, and approval notes.

No live write has been performed. Products are always imported as drafts and prices remain unset until owner approval.

## Quick start

1. Copy `.env.example` to `.env` and add a revocable WordPress Application Password and WooCommerce REST keys. Never use the primary WordPress password.
2. Revoke and replace any exposed credentials before running the authenticated read-only audit with `npm run audit`. Use `npm run audit -- --public` to check discovery anonymously.
3. Create a Hostinger backup and follow `docs/DEPLOYMENT.md`.
4. Use `npm run categories:sync -- --dry-run` and `npm run products:import -- --dry-run` before any live write. Add `--offline` to plan from the local catalog without contacting the website.

See `docs/OWNER-APPROVALS.md` for the decisions still required.

