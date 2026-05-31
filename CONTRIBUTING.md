# Contributing

Thanks for improving Zertify. Keep contributions focused and avoid committing private data or third-party exam material.

## Development checks

Run these before opening a pull request:

```bash
php artisan test
npm run build
```

For PHP formatting checks, run:

```bash
composer test:lint
```

For runtime dependency advisories, run:

```bash
composer audit --locked --no-dev
npm audit --audit-level=high
```

## Content policy for public fixtures

Public examples must be synthetic or explicitly licensed for redistribution. Do not commit scanned pages, extracted textbook tasks, official exam PDFs, generated previews of third-party pages, production user data, API keys, or service account files.
