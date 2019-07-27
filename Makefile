preview:
	ENV=dev php -S localhost:8000 -t web

install:
	set -e
	composer install
	npm install
	./console db --force
	gulp
	make cache

assets:
	gulp

cache:
	set -e
	rm -rf var/cache/*
	# Will trigger the compilation of the container
	./console list

init:
	docker-compose run --rm cliphp make vendors
	docker-compose run --rm cliphp ./node_modules/gulp/bin/gulp.js

vendors: vendor node_modules

vendor: composer.lock
	composer install

node_modules:
	yarn install

deploy: cache
	set -e
	composer install --no-dev --classmap-authoritative
	export EXTERNALS_APP_VERSION=$$(date +%s) && serverless deploy
	make deploy-static-site

deploy-static-site:
	aws s3 sync web s3://externals-assets-prod --delete
