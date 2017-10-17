#
# Strass servi avec PHP-FCGI sur le port 8000.
#

FROM debian:jessie-slim
MAINTAINER Étienne BERSAC <bersace03@gmail.com>

RUN apt-get update -y && \
    DEBIAN_FRONTEND=noninteractive apt-get install -y --no-install-recommends \
        locales \
        make \
        php5-fpm \
        php5-imagick \
        php5-sqlite \
        rsync \
        sudo \
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
ADD maint/ ./maint
ADD static ./static

VOLUME /strass/htdocs
ENV STRASS_ROOT /strass/htdocs

ADD docker/php5-fpm.conf /etc/php5/fpm/php-fpm.conf
ADD docker/php5-fpm-pool.conf /etc/php5/fpm/pool.d/strass.conf
EXPOSE 8000

ADD docker/entrypoint.mk /usr/local/bin/entrypoint.mk
ENTRYPOINT ["/usr/local/bin/entrypoint.mk"]
CMD ["fcgi"]
