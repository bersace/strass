SCSS=$(shell find data/styles/ -name "*.scss")
CSS=$(patsubst %.scss,%.css,$(SCSS))
INSTDB=include/Strass/Installer/sql/strass.sqlite

all: $(CSS) $(INSTDB) maintenance.html

%.css: %.scss
	sassc $< > $@

maintenance.html: maint/scripts/maintenance $(CSS)
	$< > $@

$(INSTDB): include/Strass/Installer/sql/schema.sql
	rm -vf $@
	sqlite3 -batch $@ ".read $<"

clean:
	rm -vf $(CSS)
	rm -vf $(INSTDB)
	rm -vf maintenance.html

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
	cd $(ORIG); git reset --hard;
	cp --archive --link $(ORIG)/config/ $(ORIG)/data $(ORIG)/resources ./
endif

test:
	phpunit --bootstrap tests/bootstrap.php tests

setmaint: maintenance.html
	maint/scripts/remote --config maint/strass.conf $@

unsetmaint:
	maint/scripts/remote --config maint/strass.conf $@

.PHONY: all clean setup serve restore restore1 test
