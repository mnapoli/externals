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

docker-compose.override.yml:
	cp docker-compose.override.yml-dist docker-compose.override.yml

docker-up: var/log/.docker-build data docker-compose.override.yml
	docker-compose up

var/log/.docker-build: docker-compose.yml docker-compose.override.yml $(shell find docker -type f)
	docker-compose build
	touch var/log/.docker-build

data:
	mkdir data

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
	sam package \
		--region eu-west-1 \
		--template-file template.yaml \
		--output-template-file .stack.yaml \
		--s3-bucket externals-app
	sam deploy \
		--region eu-west-1 \
		--template-file .stack.yaml \
		--stack-name externals \
 		--capabilities CAPABILITY_IAM
