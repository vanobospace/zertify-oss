# Deploy Notes

## Maintenance Page

The maintenance template is stored in `resources/views/maintenance.blade.php`, so it is tracked in git and will stay available after redeploys.

Enable maintenance mode with the tracked template:

```bash
php artisan down --render=maintenance --secret=<SECRET>
```

Disable maintenance mode:

```bash
php artisan up
```

Use the `--secret` flag only for controlled access while the public site is closed. This workflow affects only the environment where the command is executed, so run it on production when you want the production site to go offline.

## Dokploy Setup

Store the required environment variables in Dokploy:

```bash
MAINTENANCE_SECRET=your-secret-value
NIXPACKS_START_CMD="php artisan down --render=maintenance --secret=$MAINTENANCE_SECRET"
```

If the Dokploy UI exposes an After Deploy command, use the tracked deploy helper after each redeploy:

```bash
bash scripts/maintenance-down.sh
```

The helper script lives at `scripts/maintenance-down.sh` and fails fast if `MAINTENANCE_SECRET` is missing.

If your Dokploy version does not expose a dedicated post-deploy field, the stable fallback is to wire maintenance into `NIXPACKS_START_CMD` so each redeploy brings the application back into maintenance mode automatically.
