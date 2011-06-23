SOURCES = index.php controllers/*.php lib/*.php plugins/*.php

all: csstidy phpcs

csstidy:
	#csstidy public/css/style.css --silent=true | tr -d '\n' > public/css/style.compressed.css
	#csstidy public/js/libs/fancybox/jquery.fancybox-1.3.4.css --silent=true | tr -d '\n' > public/js/libs/fancybox/jquery.fancybox.compressed-1.3.4.css

phpcs:
	phpcs $(SOURCES)
	php index.php --update
    


# vim: set tabstop=4 shiftwidth=4 noexpandtab:
