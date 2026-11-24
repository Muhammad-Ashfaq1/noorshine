# Backup and rollback procedure

Complete this before running any command with `--apply`.

## Hostinger backup

1. In hPanel, open **Websites → Manage → Files → Backups** for `noorshine.pk`.
2. Create or confirm a fresh full website backup that includes both website files and the WordPress database.
3. Record the backup timestamp and verify that both file and database restore actions are available.
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
3. Restore the database backup from immediately before the deployment.
4. Restore the matching files backup.
5. Clear Hostinger, WordPress and object caches.
6. Recheck homepage, wp-admin, REST API and permalink behavior before removing maintenance mode.

The automation tools require `--backup-confirmed` together with `--apply`; this is a deliberate safety gate, not proof that a backup exists.

