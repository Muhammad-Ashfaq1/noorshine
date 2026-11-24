# Safe deployment runbook

Deploy the existing WordPress theme and Product Designer to Hostinger. Record the staging results and secure explicit owner approval before the production deployment or theme activation.

## 1. Preflight

1. Complete `BACKUP-ROLLBACK.md` and record the backup timestamp.
2. Confirm WordPress/PHP compatibility against the current WooCommerce release notes.
3. Revoke all exposed Application Passwords and WooCommerce keys. Generate new credentials and confirm rotation before authenticating. Use the WordPress login name of the Application Password owner.
4. Copy `.env.example` to `.env`, set the new values via a local editor, and restrict access with `chmod 600 .env`. Verify with `git check-ignore .env`. Keep production credentials separate from staging and revoke integration credentials when the work is complete.
5. Run `npm run audit -- --public` for anonymous WordPress discovery. After rotation confirmation, run `npm run audit` for authenticated WordPress and WooCommerce checks.

## 2. Verify WooCommerce

The 24 November 2026 public deployment audit detected an active WooCommerce REST v3 namespace. Reuse the existing installation and current Shop, Cart, Checkout and My Account pages. Use the following installation path only if a later audit confirms WooCommerce is inactive or missing.

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

Review the authenticated comparison before approving changes to existing settings. The script preserves an existing Pakistan province, configures PKR and the selected account and inventory flags, and keeps COD and bank transfer disabled. It halts for review if another gateway is enabled. The store address, shipping zones and rates, taxes, per-product stock, prices and payment credentials are configured separately from owner inputs.

## 4. Install theme and plugin

On staging, upload the ZIPs in `dist/` via **Appearance → Themes → Add New → Upload Theme** and **Plugins → Add New Plugin → Upload Plugin**. SFTP and hPanel File Manager can also deploy these directories:

- `wp-content/themes/noorshine`
- `wp-content/plugins/noorshine-product-designer`

Activate both on staging first. Create menus and assign the Primary and Footer locations. Leave the previous theme installed for rollback. If upgrading a previously installed NoorShine 1.0.0, replace the full theme through WordPress’s ZIP replacement so the retired `woocommerce.php` file is removed. A copy-over SFTP upload can leave that file active.

### SSH updates to the installed theme

In Hostinger, open **Websites → NoorShine → Manage → SSH Access**. Enable SSH, install the dedicated deployment public key, and copy the connection details and ED25519 host fingerprint from this screen. The script targets the existing NoorShine theme in the hosting account’s `~/domains/noorshine.pk/public_html`.

Set these repository variables under **GitHub → Settings → Secrets and variables → Actions**. Use the values from the same NoorShine hPanel:

- `NOORSHINE_SSH_USER` and `NOORSHINE_SSH_PORT` from the SSH command.
- `NOORSHINE_SSH_FINGERPRINT` from the ED25519 host key entry.
- `NOORSHINE_SSH_HOST` from the SSH command. Set this last to enable the workflow.

Add the matching private key as the repository secret `NOORSHINE_DEPLOY_KEY`. When GitHub CLI is authenticated, you can set it from the Mac without copying the key into a clipboard:

```sh
gh secret set NOORSHINE_DEPLOY_KEY < ~/.ssh/noorshine-deploy
```

In **Actions → Deploy NoorShine theme → Run workflow**, run `check` on `main` to review the transfer. Run `apply` to deploy the current theme. Later pushes to `main` that change the theme or its deployment files run automatically. For a local deploy, export the four connection variables and run `bash scripts/deploy-theme.sh check` followed by `bash scripts/deploy-theme.sh apply`. The local script reads `~/.ssh/noorshine-deploy` by default.

An apply run copies the installed theme to the private `~/.noorshine-theme-backups/` directory, syncs theme files over SSH, and compares the live stylesheet with the local source. The command preserves server-only files and scopes the transfer to the theme. Use the backup path printed by the run to restore overwritten theme files if needed.

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

The importer creates new products as hidden, out-of-stock drafts in groups of five. It preserves existing drafts or published records only when SKU and NoorShine external ID both match. A conflicting identity stops the run before product writes. Existing categories and pages are preserved. Category slug and parent mismatches require review before applying.

If a write times out or returns an unexpected response, inspect the reported SKU or slug in WordPress and rerun the authenticated dry run. The next run reconciles prior successful creations by ID and identity. Use `--offline` for a local-only plan for categories, products, pages or store settings. Offline plans contain no evidence of remote state.

## 6. WooCommerce pages and settings

- Confirm the existing WooCommerce page assignments. Use the WooCommerce page installer only for assignments found to be missing.
- After the Home page is approved and published, select **Settings → Reading → Your homepage displays → A static page → Home**. Set a featured image only when approved original art is available.
- Add the legal store address and email.
- Configure owner-approved shipping zones/rates and professional tax advice.
- Keep all payment methods disabled until sandbox/test credentials and checkout testing are complete.
- Exclude Cart, Checkout, My Account and WooCommerce session cookies from Hostinger/full-page caching.
- Review the authenticated plugin inventory for Yoast, Rank Math or another SEO provider. Keep one provider. If one is active, map the draft catalog SEO fields to that provider before publishing.
- Verify the active sitemap and canonical URLs on staging. On production, verify domain ownership in Google Search Console and submit the active sitemap after launch approval.

## 7. Launch gate

Do not publish until `TESTING.md` passes, legal/policy text is approved, imagery is authorized, Arabic is verified, prices and production facts are measured, gateways are tested, and an owner has explicitly approved launch.

