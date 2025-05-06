# Externals.io

## Setup

Requirements:

- PHP 8.0
- NPM
- MySQL database
- Algolia account (TODO: make it optional in dev environment)

Copy the `.env.dist` file to `.env` and configure it.

Install the application (will create the database and the tables):

```bash
$ make install
```

Run the preview:

```bash
$ make preview

# Browse to http://localhost:8000
```

To recompile the assets if you change them, run `make assets`. Assets are automatically compiled when the website is deployed.

[![](http://i.imgur.com/BrCb8gu.png)](http://externals.io/)

[![](http://i.imgur.com/gD7Let2.png)](http://externals.io/)

## Setup via docker

Note: this is obsolete and will likely not work.

- clone the repository
- run `docker-compose up`
- run `docker-compose run cli php /var/task/console db --force` to initialize the database
- when the containers are all up and running execute a `make init` in another window
