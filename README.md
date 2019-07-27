# Externals.io

## Setup

Requirements (TODO: Vagrant box):

- PHP 7.1 or above
- NPM
- Gulp (install with `npm install gulp-cli -g`)
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

- clone the repository
- run `docker-compose up`
- run `docker-compose run cli php /var/task/console db --force` to initialize the database
- when the containers are all up and running execute a `make init` in another window
