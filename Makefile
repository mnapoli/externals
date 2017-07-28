preview:
	ENV=dev ./console serve

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
