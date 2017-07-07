preview:
	ENV=dev php -S localhost:8000 -t web

install:
	composer install
	./console db --force
