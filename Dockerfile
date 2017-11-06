#
# Strass servi avec PHP-FCGI sur le port 8000.
#

FROM python:3 AS static

# D'abord générer les CSS et SQL.

RUN apt-get update -y && \
    DEBIAN_FRONTEND=noninteractive apt-get install -y --no-install-recommends \
        make \
        sqlite3 \
        && \
    apt-get clean && \
    rm -rf /var/lib/apt/lists/* && \
    pip install --no-cache-dir --upgrade libsass pyyaml webassets && \
    :

WORKDIR /strass
ADD Makefile .
ADD include/Strass ./include/Strass
ADD static/styles ./static/styles

RUN make clean all && \
    rm -rf static/styles/*/scss && \
    :

FROM bersace/strass-runtime

WORKDIR /strass
ADD index.php .
ADD include ./include
ADD scripts/ ./scripts
COPY --from=static /strass/static ./static

VOLUME /strass/htdocs

ADD docker/php5-fpm.conf /etc/php5/fpm/php-fpm.conf
ADD docker/php5-fpm-pool.conf /etc/php5/fpm/pool.d/strass.conf
EXPOSE 8000

ADD docker/entrypoint.mk /usr/local/bin/entrypoint.mk
ENTRYPOINT ["/usr/local/bin/entrypoint.mk"]
CMD ["fcgi"]
