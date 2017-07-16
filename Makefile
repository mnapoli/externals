preview:
	ENV=dev php -S localhost:8000 -t web

install:
	composer install
	npm install
	./console db --force
	gulp

assets:
	gulp

cache:
	rm var/cache/
	# Will trigger the compilation of the container
	./console list
