SOURCES = index.php controllers/*.php lib/*.php plugins/*.php setup.php

all: phpcs update

phpcs:
	./vendor/bin/phpcs -s $(SOURCES)

phpunit: 
	rm -f data/*json
	tar xzf tests/testdata.tar.gz
	./vendor/bin/phpunit --strict tests

lint:
	for source in $(SOURCES) ; do php -l $$source || exit 1 ; done

build: phpcs lint phpunit

update:
	php index.php --update


# vim: set tabstop=4 shiftwidth=4 noexpandtab:


