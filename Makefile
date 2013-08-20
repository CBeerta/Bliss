SOURCES = php/index.php src/Bliss/Controllers/*.php src/Bliss/*.php plugins/*.php setup.php manage.php

all: phpcs update

phpcs:
	./vendor/bin/phpcs --standard=PEAR -s $(SOURCES)

phpunit: 
	rm -f data/*json
	tar xzf tests/testdata.tar.gz
	./vendor/bin/phpunit --stderr --strict tests

lint:
	for source in $(SOURCES) ; do php -l $$source || exit 1 ; done

build: phpcs lint phpunit

update:
	php index.php --update


# vim: set tabstop=4 shiftwidth=4 noexpandtab:

