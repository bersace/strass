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

.PHONY: all
all: $(CSS) $(SUFSQL) $(FSESQL)

.PHONY: help
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

include/Strass/Installer/sql/dump-%.sql: include/Strass/Installer/sql/schema.sql include/Strass/Installer/sql/%.sql
	$(MAKE) installer-$*.db
	sqlite3 installer-$*.db .dump > $@
	rm -f installer-$*.db

installer-%.db: include/Strass/Installer/sql/schema.sql include/Strass/Installer/sql/%.sql
	for f in $^ ; do sqlite3 -batch $@ ".read $$f"; done

.PHONY: clean
clean:
	rm -vf $(CSS)
	rm -vf $(SUFSQL) $(FSESQL)
	rm -vf maintenance.html 500.html
	rm -vf $(STRASS_ROOT)private/cache/*

.PHONY: distclean
distclean:
	$(MAKE) clean
	rm -rvf $(STRASS_ROOT)

.PHONY: setup
setup:
	aptitude install php5-cli php5-sqlite php-pear php5-gd php5-imagick phpunit \
	python-pip python-dev sqlite3
	pip install --upgrade libsass

.PHONY: serve
serve: all
	php -S localhost:8000 \
	-d include_path=$(shell pwd)/include/ \
	-d xdebug.profiler_output_dir=$(shell pwd) \
	-d xdebug.profiler_enable_trigger=1 \
	devel.php

# Restaure les données uniquement. Pour tester la migration.
.PHONY: restore
restore:
	git checkout -- $(STRASS_ROOT)
	git clean --force -d $(STRASS_ROOT)

TESTROOT=tests/root/
TESTDB=$(TESTROOT)/private/strass.sqlite
$(TESTDB): include/Strass/Installer/sql/schema.sql
	rm -vf $@
	mkdir -p $$(dirname $@)
	sqlite3 -batch $@ ".read $<"
.INTERMEDIATE: $(TESTDB)

.PHONY: test
test:
	rm -rf $(TESTROOT)/*
	make $(TESTDB)
	STRASS_ROOT=$(shell realpath $(TESTROOT)) phpunit --bootstrap tests/bootstrap.php $(shell realpath tests)

ifdef PROD
REMOTE=maint/scripts/remote --verbose --config $(REMOTECONF) --production
else
REMOTE=maint/scripts/remote --verbose --config $(REMOTECONF)
endif

.PHONY: config
config:
	$(REMOTE) config

.PHONY: setmaint
setmaint: maintenance.html
	$(REMOTE) $@

.PHONY: unsetmaint
unsetmaint:
	$(REMOTE) $@

.PHONY: backup
backup:
	$(MAKE) setmaint
	$(REMOTE) $@
	git add $(STRASS_ROOT);
	git diff --staged --exit-code --quiet || git commit -m BACKUP

.PHONY: migrate
migrate: all
	maint/scripts/migrate;
	git add --ignore-errors --all -- $(STRASS_ROOT) data/ private/;
	git commit -m MIGRATION

.PHONY: upgrade
upgrade: 500.html
	$(MAKE) setmaint
	$(REMOTE) $@
	$(MAKE) unsetmaint

.PHONY: mirror
mirror: 500.html
	$(MAKE) setmaint
	$(REMOTE) $@
	$(MAKE) unsetmaint

.PHONY: partialmirror
partialmirror: 500.html
	$(MAKE) setmaint
	$(REMOTE) mirror --partial
	$(MAKE) unsetmaint
