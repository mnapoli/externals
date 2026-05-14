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
