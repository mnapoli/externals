preview:
	ENV=dev XDEBUG_MODE=debug php -S localhost:8000 -t web vendor/bin/bref-dev-server

install:
	set -e
	composer install
	npm install
	./console db --force
	make assets
	make cache

assets:
	npx tailwindcss-cli@latest build ./assets/styles.css -o ./web/assets/css/main.min.css
	npx parcel build assets/main.js --out-dir=web/assets/js
	rm web/assets/js/main.js.map

assets-prod:
	NODE_ENV=production npx tailwindcss-cli@latest build ./assets/styles.css -o ./web/assets/css/main.min.css
	npx parcel build assets/main.js --out-dir=web/assets/js
	rm web/assets/js/main.js.map

cache:
	set -e
	rm -rf var/cache/*
	# Will trigger the compilation of the container
	./console list

vendors: vendor node_modules

vendor: composer.lock
	composer install

node_modules: package.json package-lock.json
	npm install

.PHONY: assets
