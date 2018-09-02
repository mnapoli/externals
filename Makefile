CURRENT_UID=$(shell id -u)

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
	rm -r var/cache/*
	# Will trigger the compilation of the container
	./console list

docker-compose.override.yml:
	cp docker-compose.override.yml-dist docker-compose.override.yml

docker-up: var/log/.docker-build data docker-compose.override.yml
	CURRENT_UID=$(CURRENT_UID) docker-compose up

var/log/.docker-build: docker-compose.yml docker-compose.override.yml $(shell find docker -type f)
	CURRENT_UID=$(CURRENT_UID) docker-compose build
	touch var/log/.docker-build

data:
	mkdir data
	mkdir data/composer

init:
	CURRENT_UID=$(CURRENT_UID) docker-compose run --rm cliphp make vendors
	CURRENT_UID=$(CURRENT_UID) docker-compose run --rm cliphp ./node_modules/gulp/bin/gulp.js

composer.phar:
	$(eval EXPECTED_SIGNATURE = "$(shell wget -q -O - https://composer.github.io/installer.sig)")
	$(eval ACTUAL_SIGNATURE = "$(shell php -r "copy('https://getcomposer.org/installer', 'composer-setup.php'); echo hash_file('SHA384', 'composer-setup.php');")")
	@if [ "$(EXPECTED_SIGNATURE)" != "$(ACTUAL_SIGNATURE)" ]; then echo "Invalid signature"; exit 1; fi
	php composer-setup.php
	rm composer-setup.php

vendors: vendor node_modules

vendor: composer.phar composer.lock
	php composer.phar install

node_modules:
	yarn install
