export STRASS_ROOT ?= htdocs/

STYLES_DIRS=static/styles
ifeq (,$(wildcard $(STRASS_ROOT)data/styles/))
	STYLES_DIRS+=$(STRASS_ROOT)data/styles/
endif

SCSS=$(shell find $(STYLE_DIRS) -name "*.scss")
CSS=$(patsubst %.scss,%.css,$(SCSS))
SUFSQL=include/Strass/Installer/sql/dump-suf.sql
FSESQL=include/Strass/Installer/sql/dump-fse.sql
GIT=git -C $(STRASS_ROOT)
COMMIT=$(GIT) diff --staged --exit-code --quiet || $(GIT) commit --quiet --message

.PHONY: all
all: $(CSS) $(SUFSQL) $(FSESQL)

.PHONY: help
help:
	less maint/DOC

%.css: %.scss
	rm -f $@
	sassc $< $@

$(STRASS_ROOT)maintenance.html: maint/scripts/maintenance $(CSS)
	$< > $@
.INTERMEDIATE: $(STRASS_ROOT)maintenance.html

$(STRASS_ROOT)500.html: maint/scripts/500 $(CSS)
	$< > $@
.INTERMEDIATE: $(STRASS_ROOT)500.html

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
	rm -vf $(STRASS_ROOT)maintenance.html $(STRASS_ROOT)500.html
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
	STRASS_MODE=devel php -S localhost:8000 \
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

REMOTE=maint/scripts/remote --verbose --config $(STRASS_ROOT)strass.conf

.PHONY: config
config:
	test -d $(STRASS_ROOT) || mkdir -p $(STRASS_ROOT)
	test -d $(STRASS_ROOT).git || $(GIT) init .
	$(GIT) rev-parse --quiet --verify HEAD || $(GIT) commit --quiet --allow-empty --message INIT
	$(REMOTE) config
	$(GIT) add strass.conf
	$(COMMIT) CONFIG

.PHONY: setmaint
setmaint: $(STRASS_ROOT)maintenance.html
	$(REMOTE) $@

.PHONY: unsetmaint
unsetmaint:
	$(REMOTE) $@

.PHONY: backup
backup:
	$(MAKE) setmaint
	$(REMOTE) $@
	$(GIT) add .
	$(COMMIT) BACKUP

.PHONY: migrate
migrate: all
	maint/scripts/migrate;
	$(GIT) add --ignore-errors --all -- $(STRASS_ROOT) data/ private/;
	$(COMMIT) MIGRATION

.PHONY: upgrade
upgrade: $(STRASS_ROOT)500.html
	$(MAKE) setmaint
	$(REMOTE) $@
	$(MAKE) unsetmaint

.PHONY: mirror
mirror: $(STRASS_ROOT)500.html
	$(MAKE) setmaint
	$(REMOTE) $@
	$(MAKE) unsetmaint

.PHONY: partialmirror
partialmirror: $(STRASS_ROOT)500.html
	$(MAKE) setmaint
	$(REMOTE) mirror --partial
	$(MAKE) unsetmaint
