SCSS=$(shell find data/styles/ -name "*.scss")
CSS=$(patsubst %.scss,%.css,$(SCSS))
INSTDB=include/Strass/Installer/sql/strass.sqlite

all: $(CSS) $(INSTDB)

help:
	less maint/DOC

%.css: %.scss
	sassc $< > $@ || rm $@

maintenance.html: maint/scripts/maintenance $(CSS)
	$< > $@

.INTERMEDIATE: maintenance.html

$(INSTDB): include/Strass/Installer/sql/schema.sql
	rm -vf $@
	sqlite3 -batch $@ ".read $<"

suf.sqlite: $(INSTDB) include/Strass/Installer/sql/suf.sql
	cp $< $@
	sqlite3 $@ ".read include/Strass/Installer/sql/suf.sql"

clean:
	rm -vf $(CSS)
	rm -vf $(INSTDB)
	rm -vf maintenance.html
	rm -vf private/cache/*

setup:
	aptitude install php5-cli php5-sqlite php-pear php5-gd php5-imagick python-pip
	pip install libsass

serve: all
	php -S localhost:8000 \
	-d xdebug.profiler_output_dir=$$(pwd) \
	-d xdebug.profiler_enable_trigger=1 \
	devel.php

# Restaure les données uniquement. Pour tester la migration.
restore:
	git checkout -- data/
	test -d private/ && git checkout -- private/ || true
	git clean --force -d data/ private/
	rm -f private/cache/*

# Restaure un site en version 1
ifdef ORIG
# depuis un dossier autre
restore1: restore
	cd $(ORIG); git reset --hard;
	cp --archive --link $(ORIG)/config/ $(ORIG)/data $(ORIG)/resources ./
else
# depuis HEAD
restore1: restore
	git checkout resources/ config/
endif

TESTDB=tests/strass.sqlite
$(TESTDB): include/Strass/Installer/sql/schema.sql
	rm -vf $@
	sqlite3 -batch $@ ".read $<"
.INTERMEDIATE: $(TESTDB)

ifdef TESTDB
test: $(TESTDB)
	rm -rf private/cache
	phpunit --bootstrap tests/bootstrap.php tests
endif

REMOTE=maint/scripts/remote --verbose --config maint/strass.conf

config:
	$(REMOTE) config

setmaint: maintenance.html
	$(REMOTE) $@

unsetmaint:
	$(REMOTE) $@

backup1:
	make setmaint
	$(REMOTE) $@
	git add data/ resources/ config/;
	git commit -m 'BACKUP';

migrate: all
	maint/scripts/migrate;
	git add --all -- data/ private/ config/ resources/;
	git commit -m 'MIGRATION';

upgrade:
	make setmaint
	$(REMOTE) $@
	make unsetmaint

mirror:
	make setmaint
	$(REMOTE) $@
	make unsetmaint

partialmirror:
	make setmaint
	$(REMOTE) mirror --partial
	make unsetmaint

.PHONY: all doc clean setup serve restore restore1 test
.PHONY: config setmaint unsetmaint backup1 migrate mirror partialmirror upgrade
