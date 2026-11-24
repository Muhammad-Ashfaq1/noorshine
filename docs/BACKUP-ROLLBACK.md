# Backup and rollback procedure

Complete this before running any command with `--apply`.

## Hostinger backup

1. In hPanel, open **Websites → Manage site** for `noorshine.pk`. In the dashboard sidebar, open **Files → Backups**.
2. Under **Create Backup**, choose **Select → Continue**. If manual backups are unavailable on your plan, verify the latest automatic backup covers the website files and its WordPress database.
3. Once the backup completes, record the **Last backup** timestamp and timezone. Verify that the matching website and database are available for restoration and record the currently active theme, plugins, homepage and WooCommerce page IDs.
4. Download an additional database export if practical. Store it outside the public web root.
5. Do not continue if hPanel reports a backup in progress, failed, or unavailable.

## Optional WP-CLI verification

From the WordPress document root:

```bash
wp core version
wp plugin list
wp theme list
wp db check
wp option get home
wp option get siteurl
```

Never print secrets. Do not archive `.env`, `wp-config.php`, uploads containing private customer data, or backup files into Git.

## Rollback sequence

1. Put the store into maintenance mode if customer traffic exists.
2. Deactivate `noorshine-product-designer` and switch back to the previously active theme.
3. If a rollback needs a full restore, preserve any orders or content created after the checkpoint before restoring. In **Files → Backups**, choose **Restore website** for the recorded checkpoint. If the single-site option is unavailable, restore the matching files and database from the same checkpoint following Hostinger’s procedure.
4. Clear Hostinger, WordPress and object caches.
5. Recheck homepage, wp-admin, REST API, assigned WooCommerce pages and permalink behavior before removing maintenance mode.

The automation tools require `--backup-confirmed` together with `--apply`; this is a deliberate safety gate, not proof that a backup exists.

[Create backups in Hostinger](https://www.hostinger.com/support/2298928-how-to-create-backups-at-hostinger/) · [Restore a WordPress website in Hostinger](https://www.hostinger.com/support/3989447-how-to-restore-a-wordpress-website-from-backups-in-hostinger/)

