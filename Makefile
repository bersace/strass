SCSS=$(shell find data/styles/ -name "*.scss")
CSS=$(patsubst %.scss,%.css,$(SCSS))
SUFSQL=include/Strass/Installer/sql/dump-suf.sql
FSESQL=include/Strass/Installer/sql/dump-fse.sql

all: $(CSS) $(SUFSQL) $(FSESQL)

help:
	less maint/DOC

%.css: %.scss
	sassc $< > $@ || rm $@

maintenance.html: maint/scripts/maintenance $(CSS)
	$< > $@

.INTERMEDIATE: maintenance.html

500.html: maint/scripts/500 $(CSS)
	$< > $@

.INTERMEDIATE: 500.html

$(SUFDUMP): $(SUFDB)
	sqlite3 $< .dump > $@

$(SUFSQL): include/Strass/Installer/sql/schema.sql include/Strass/Installer/sql/suf.sql
	rm -vf $@.db
	for f in $^ ; do sqlite3 -batch $@.db ".read $$f"; done
	sqlite3 $@.db .dump > $@
	rm -vf $@.db

$(FSESQL): include/Strass/Installer/sql/schema.sql include/Strass/Installer/sql/suf.sql
	rm -vf $@.db
	for f in $^ ; do sqlite3 -batch $@.db ".read $$f"; done
	sqlite3 $@.db .dump > $@
	rm -vf $@.db

clean:
	rm -vf $(CSS)
	rm -vf $(SUFSQL) $(FSESQL)
	rm -vf maintenance.html 500.html
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

ifdef PROD
REMOTE=maint/scripts/remote --production --verbose --config maint/strass.conf
else
REMOTE=maint/scripts/remote --verbose --config maint/strass.conf
endif

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
	git commit -m BACKUP

backup:
	make setmaint
	$(REMOTE) $@
	git add data/ private/;
	git diff --staged --exit-code --quiet || git commit -m BACKUP

migrate: all
	maint/scripts/migrate;
	git add --all -- data/ private/ config/ resources/;
	git commit -m MIGRATION

upgrade: 500.html
	make setmaint
	$(REMOTE) $@
	make unsetmaint

mirror: 500.html
	make setmaint
	$(REMOTE) $@
	make unsetmaint

partialmirror: 500.html
	make setmaint
	$(REMOTE) mirror --partial
	make unsetmaint

.PHONY: all doc clean setup serve restore restore1 test
.PHONY: config setmaint unsetmaint backup1 migrate mirror partialmirror upgrade backup
