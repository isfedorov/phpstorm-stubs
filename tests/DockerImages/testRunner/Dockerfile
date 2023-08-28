FROM php:8.3.0beta3-apache

RUN echo 'memory_limit = 1024M' >> /usr/local/etc/php/conf.d/docker-php-memlimit.ini
COPY --from=composer /usr/bin/composer /usr/bin/composer

RUN apt-get update && apt-get -y install git zip unzip
RUN apt-get update -y && \
    apt-get install git -y && \
    git clone https://github.com/xdebug/xdebug
WORKDIR xdebug
RUN phpize && \
    chmod +x configure && \
    ./configure --enable-xdebug && \
    make && \
    make install
# Enable xdebug extension
RUN echo "zend_extension=$(php-config --extension-dir)/xdebug.so" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini
RUN echo "xdebug.mode=develop,debug,coverage" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini
RUN echo "xdebug.client_host=host.docker.internal" >> /usr/local/etc/php/php.ini
WORKDIR /opt/project/phpstorm-stubs
