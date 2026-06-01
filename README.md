# [externals.io](http://externals.io/)

## Local Development

Requirements:

- PHP 8.5
- NPM

Copy the `.env.example` file to `.env` and configure it.

Install the application:

```bash
composer install
npm install
php artisan key:generate
php artisan migrate
```

Run the preview:

```bash
php artisan serve
npm run dev

# Browse to http://localhost:8000
```

To build assets for production, run `npm run build`. Assets are automatically compiled when the website is deployed.

[![](http://i.imgur.com/BrCb8gu.png)](http://externals.io/)

[![](http://i.imgur.com/gD7Let2.png)](http://externals.io/)

## Production and Staging

The application is hosted on AWS with [Bref](https://bref.sh/) (serverless PHP on AWS Lambda). Configuration lives in `serverless.yml`.

Both environments share the same RDS MySQL instance (different databases). Secrets are stored in AWS SSM Parameter Store under `/externals/*`.

### Deployments

Deployments are triggered automatically by GitHub Actions on push:

- `master` → production (`.github/workflows/deploy.yml`)
- `staging` or `v4` → staging (`.github/workflows/deploy-staging.yml`)

Each staging deploy also runs `php artisan migrate --force`. Production migrations are not run automatically.

### Scheduled jobs

In production only, `externals:sync` runs every 15 minutes to pull new messages from the PHP NNTP server (see `serverless.yml`).

### Refreshing the staging database from prod

Staging data drifts from prod over time. To reset staging to a copy of prod, run manually:

```bash
bref command --env=staging "staging:refresh-from-prod"
bref command --env=staging "migrate --force"
```

This drops every table in `externals-staging` and recopies it from `externals-prod` on the same RDS instance. It takes several minutes, which is why it is not part of the deploy pipeline.
