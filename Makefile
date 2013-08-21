SOURCES = php/index.php src/Bliss/Controllers/*.php src/Bliss/*.php src/Bliss_Plugin/Content/*.php setup.php manage.php

all: phpcs update

phpcs:
	./vendor/bin/phpcs -n --standard=PSR2 -s $(SOURCES)

phpunit: 
	rm -rf data	
	tar xzf tests/testdata.tar.gz
	./vendor/bin/phpunit --stderr --strict tests

lint:
	for source in $(SOURCES) ; do php -l $$source || exit 1 ; done

build: phpcs lint phpunit

update:
	php manage.php --update --expire --thumbs


# vim: set tabstop=4 shiftwidth=4 noexpandtab:


