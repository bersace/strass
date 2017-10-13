#
# Strass servi avec PHP-FCGI sur le port 8000.
#

FROM debian:jessie-slim
MAINTAINER Étienne BERSAC <bersace03@gmail.com>

RUN apt-get update -y && \
    DEBIAN_FRONTEND=noninteractive apt-get install -y --no-install-recommends \
        locales \
        php5-fpm \
        php5-imagick \
        php5-sqlite \
        && \
    apt-get clean && \
    rm -rf /var/lib/apt/lists/* && \
    :

RUN sed -i "/fr_FR.*UTF-8/s/^# //" /etc/locale.gen && \
    locale-gen && \
    useradd --home-dir /strass --create-home --system strass && \
    :
ENV LC_ALL fr_FR.UTF-8

WORKDIR /strass
ADD index.php .
ADD include ./include
ADD static ./static

VOLUME /strass/htdocs
ENV STRASS_ROOT /strass/htdocs

ADD docker/php5-fpm.conf /etc/php5/fpm/php-fpm.conf
ADD docker/php5-fpm-pool.conf /etc/php5/fpm/pool.d/strass.conf
EXPOSE 8000

ADD docker/entrypoint.sh /usr/local/bin/entrypoint.sh
ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["/usr/sbin/php5-fpm", "--nodaemonize", "--fpm-config", "/etc/php5/fpm/php-fpm.conf"]
