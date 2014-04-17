FROM fedora
MAINTAINER Claus Beerta <claus@beeta.de>

RUN yum update -y
RUN yum install -y git php php-cli php-sqlite3 apache curl php-mcrypt subversion php-pecl-zip supervisor openssh-server

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin && mv /usr/local/bin/composer.phar /usr/local/bin/composer

RUN mkdir -p /var/run/sshd
RUN mkdir -p /var/log/supervisor
ADD supervisord.conf /etc/supervisord.conf

RUN sed -ibak -e 's/AllowOverride None/AllowOverride All/i' /etc/httpd/conf/httpd.conf

ADD . /app
RUN rm -rf /var/www/html
RUN ln -sf /app/public /var/www/html

RUN cd /app ; composer install

RUN mv /app/config.ini.docker /app/config.ini

RUN echo "@reboot apache cd /app/ ; make update" >> /etc/crontab
RUN echo "0 */6 * * * apache cd /app/ ; make update" >> /etc/crontab

VOLUME /data

EXPOSE 22 80
CMD ["/usr/bin/supervisord"]

