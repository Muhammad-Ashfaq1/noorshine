# Safe deployment runbook

All source is local-only at this point. The Sites workflow influenced the responsive presentation and verification approach, but this is a WordPress/Hostinger deployment—not OpenAI Sites hosting.

## 1. Preflight

1. Complete `BACKUP-ROLLBACK.md` and record the backup timestamp.
2. Confirm WordPress/PHP compatibility against the current WooCommerce release notes.
3. Create a dedicated WordPress integration administrator temporarily, then generate an Application Password for it.
4. Copy `.env.example` to `.env`; enter the Application Password. `.env` is ignored by Git.
5. Run `npm run audit`. Resolve every failed authenticated check.

## 2. Install WooCommerce

Preferred methods are the WordPress plugin screen, Hostinger’s authorized workflow, or WP-CLI over SSH. Example after backup:

```bash
wp plugin install woocommerce --activate
wp plugin status woocommerce
wp rewrite structure '/%postname%/' --hard
wp rewrite flush --hard
```

Do not use an unofficial WooCommerce package. Do not enable a gateway during onboarding.

In **WooCommerce → Settings → Advanced → REST API**, create a read/write key for the dedicated integration user and place it in `.env`. Run `npm run audit` again.

## 3. Configure known-safe settings

Review the dry run:

```bash
npm run store:configure
```

After backup confirmation:

```bash
npm run store:configure -- --apply --backup-confirmed
```

This configures only PKR/Pakistan, account behavior, and explicitly disables COD/bank transfer. It does not invent address, shipping, tax, stock, prices or payment credentials.

## 4. Install theme and plugin

Upload these directories through SFTP, hPanel File Manager, or a controlled deployment:

- `wp-content/themes/noorshine`
- `wp-content/plugins/noorshine-product-designer`

Activate the NoorShine theme and plugin only on staging first. Create menus and assign the Primary and Footer locations. Leave the existing theme installed for rollback.

## 5. Sync drafts

Run each dry run first:

```bash
npm run categories:sync
npm run products:import
npm run pages:sync
```

After reviewing output and reconfirming the backup:

```bash
npm run categories:sync -- --apply --backup-confirmed
npm run products:import -- --apply --backup-confirmed
npm run pages:sync -- --apply --backup-confirmed
```

The product importer is idempotent by SKU and always sets imported products to `draft` with hidden catalog visibility. Pages are also drafts.

## 6. WooCommerce pages and settings

- Use WooCommerce’s page installer for Shop, Cart, Checkout and My Account, then confirm those page assignments.
- Set a static homepage only after the Home draft and theme are approved.
- Add the legal store address and email.
- Configure owner-approved shipping zones/rates and professional tax advice.
- Keep all payment methods disabled until sandbox/test credentials and checkout testing are complete.
- Exclude Cart, Checkout, My Account and WooCommerce session cookies from Hostinger/full-page caching.

## 7. Launch gate

Do not publish until `TESTING.md` passes, legal/policy text is approved, imagery is authorized, Arabic is verified, prices and production facts are measured, gateways are tested, and an owner has explicitly approved launch.

