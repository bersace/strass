SCSS=$(shell find data/styles/ -name "*.scss")
CSS=$(patsubst %.scss,%.css,$(SCSS))
INSTDB=include/Strass/Installer/sql/strass.sqlite

all: $(CSS) $(INSTDB)

%.css: %.scss
	sassc $^ > $@

$(INSTDB): include/Strass/Installer/sql/schema.sql
	rm -vf $@
	sqlite3 -batch $@ ".read $^"

clean:
	rm -vf $(CSS)
	rm -vf $(INSTDB)

setup:
	aptitude install php5-cli php5-sqlite php-pear php5-gd php5-imagick python-pip
	pip install libsass

serve:
	php -S localhost:8000 devel.php

# Restaure les données uniquement. Pour tester la migration.
restore:
	git checkout data/ private/
	git clean --force -d data/ private/
	rm -f private/cache/*

ifdef ORIG
# Restaure un site en version 1
restore1: restore
	cp --archive --link $(ORIG)/config/ $(ORIG)/data $(ORIG)/resources ./
endif

test:
	phpunit --bootstrap tests/bootstrap.php tests

.PHONY: all clean serve setup test
