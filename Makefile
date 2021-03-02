preview:
	ENV=dev XDEBUG_MODE=debug vendor/bin/bref-dev-server --assets=web

install:
	set -e
	composer install
	npm install
	./console db --force
	make assets
	make cache

assets:
	npx tailwindcss-cli@latest build ./assets/styles.css -o ./web/assets/css/main.min.css
	npx esbuild assets/main.js --bundle --outfile=web/assets/js/main.js

assets-prod:
	NODE_ENV=production npx tailwindcss-cli@latest build ./assets/styles.css -o ./web/assets/css/main.min.css
	npx esbuild assets/main.js --bundle --outfile=web/assets/js/main.js

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
