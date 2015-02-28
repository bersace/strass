export STRASS_ROOT ?= htdocs/

STYLES_DIRS=static/styles
ifeq (,$(wildcard $(STRASS_ROOT)/data/styles/))
	STYLES_DIRS+=$(STRASS_ROOT)/data/styles/
endif

SCSS=$(shell find $(STYLE_DIRS) -name "*.scss")
CSS=$(patsubst %.scss,%.css,$(SCSS))
SUFSQL=include/Strass/Installer/sql/dump-suf.sql
FSESQL=include/Strass/Installer/sql/dump-fse.sql
REMOTECONF=$(STRASS_ROOT)/strass-remote.conf

all: $(CSS) $(SUFSQL) $(FSESQL)

help:
	less maint/DOC

%.css: %.scss
	rm -f $@
	sassc $< $@

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

$(FSESQL): include/Strass/Installer/sql/schema.sql include/Strass/Installer/sql/fse.sql
	rm -vf $@.db
	for f in $^ ; do sqlite3 -batch $@.db ".read $$f"; done
	sqlite3 $@.db .dump > $@
	rm -vf $@.db

clean:
	rm -vf $(CSS)
	rm -vf $(SUFSQL) $(FSESQL)
	rm -vf maintenance.html 500.html
	rm -vf $(STRASS_ROOT)private/cache/*

distclean:
	$(MAKE) clean
	rm -rvf $(STRASS_ROOT)

setup:
	aptitude install php5-cli php5-sqlite php-pear php5-gd php5-imagick phpunit \
	python-pip python-dev sqlite3
	pip install --upgrade libsass

serve: all
	php -S localhost:8000 \
	-d include_path=$(shell pwd)/include/ \
	-d xdebug.profiler_output_dir=$(shell pwd) \
	-d xdebug.profiler_enable_trigger=1 \
	devel.php

# Restaure les données uniquement. Pour tester la migration.
restore:
	git checkout -- $(STRASS_ROOT)
	git clean --force -d $(STRASS_ROOT)

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

TESTROOT=tests/root/
TESTDB=$(TESTROOT)/private/strass.sqlite
$(TESTDB): include/Strass/Installer/sql/schema.sql
	rm -vf $@
	mkdir -p $$(dirname $@)
	sqlite3 -batch $@ ".read $<"
.INTERMEDIATE: $(TESTDB)

test:
	rm -rf $(TESTROOT)/*
	make $(TESTDB)
	STRASS_ROOT=$(shell realpath $(TESTROOT)) phpunit --bootstrap tests/bootstrap.php $(shell realpath tests)

ifdef PROD
REMOTE=maint/scripts/remote --verbose --config $(REMOTECONF) --production
else
REMOTE=maint/scripts/remote --verbose --config $(REMOTECONF)
endif

config:
	$(REMOTE) config

setmaint: maintenance.html
	$(REMOTE) $@

unsetmaint:
	$(REMOTE) $@

backup1:
	$(MAKE) setmaint
	$(REMOTE) $@
	git add data/ resources/ config/;
	git commit -m BACKUP

backup:
	$(MAKE) setmaint
	$(REMOTE) $@
	git add $(STRASS_ROOT);
	git diff --staged --exit-code --quiet || git commit -m BACKUP

migrate: all
	maint/scripts/migrate;
	git add --ignore-errors --all -- $(STRASS_ROOT) data/ private/;
	git commit -m MIGRATION

upgrade: 500.html
	$(MAKE) setmaint
	$(REMOTE) $@
	$(MAKE) unsetmaint

mirror: 500.html
	$(MAKE) setmaint
	$(REMOTE) $@
	$(MAKE) unsetmaint

partialmirror: 500.html
	$(MAKE) setmaint
	$(REMOTE) mirror --partial
	$(MAKE) unsetmaint

.PHONY: all doc clean setup serve restore restore1 test
.PHONY: config setmaint unsetmaint backup1 migrate mirror partialmirror upgrade backup
