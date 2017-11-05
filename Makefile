STRASS_ROOT ?= htdocs/
export STRASS_ROOT:=$(shell readlink -f $(STRASS_ROOT))/
CIRCLE_TEST_REPORTS ?= .

STRASS_EXEC=$(if $(CI),,docker run --rm --entrypoint "/usr/bin/env" -v $(PWD):/strass -v $(STRASS_ROOT):/strass/htdocs bersace/strass)

SUFSQL=include/Strass/Installer/sql/dump-suf.sql
FSESQL=include/Strass/Installer/sql/dump-fse.sql
HTML=$(STRASS_ROOT)500.html $(STRASS_ROOT)maintenance.html

default:

all: $(SUFSQL) $(FSESQL)
	$(MAKE) -C static/styles/strass build
	$(MAKE) -C static/styles/joubert build
	$(MAKE) -C static/styles/modele build

include/Strass/Installer/sql/dump-%.sql: include/Strass/Installer/sql/schema.sql include/Strass/Installer/sql/%.sql
	$(MAKE) installer-$*.db
	sqlite3 installer-$*.db .dump > $@
	rm -f installer-$*.db

installer-%.db: include/Strass/Installer/sql/schema.sql include/Strass/Installer/sql/%.sql
	for f in $^ ; do sqlite3 -batch $@ ".read $$f"; done

clean:
	$(MAKE) -C static/styles/modele $@
	$(MAKE) -C static/styles/joubert clean
	$(MAKE) -C static/styles/strass clean
	rm -vf $(HTML)
	rm -vf $(SUFSQL) $(FSESQL)
	rm -vf $(STRASS_ROOT)private/cache/*

distclean:
	$(MAKE) clean
	rm -rvf $(STRASS_ROOT)

fixperms:
	chgrp -R $(shell stat -c %G Makefile) $(STRASS_ROOT)
	find $(STRASS_ROOT) -exec chmod g+rw {} ';'
	find $(STRASS_ROOT) -type d -exec chmod g+x {} ';'

setup:
	which sqlite3
	pip3 install --upgrade libsass pyyaml webassets

setup-tests:
	apt install -y faketime wget
	pip3 install --upgrade selenium
	$(MAKE) phantomjs

.PHONY: phantomjs
phantomjs: phantomjs/bin/phantomjs

PHANTOM_JS=phantomjs-1.9.8-linux-x86_64
phantomjs/bin/phantomjs:
	mkdir -p phantomjs
	curl -L https://bitbucket.org/ariya/phantomjs/downloads/$(PHANTOM_JS).tar.bz2 | tar -jxf - -C phantomjs --strip-components=1

dbshell:
	sqlite3 $(STRASS_ROOT)/private/strass.sqlite

TESTROOT=tests/unit/root/
TESTDB=$(TESTROOT)/private/strass.sqlite
$(TESTDB): include/Strass/Installer/sql/schema.sql
	rm -vf $@
	mkdir -p $$(dirname $@)
	sqlite3 -batch $@ ".read $<"
.INTERMEDIATE: $(TESTDB)

test: test-unit test-func

test-unit:
	rm -rf $(TESTROOT)/*
	make $(TESTDB)
	STRASS_ROOT=$(shell readlink -f $(TESTROOT)) \
	phpunit --bootstrap $(shell readlink -e tests/unit/bootstrap.php) \
		--log-junit $(CIRCLE_TEST_REPORTS)/junit.xml \
		$(shell readlink -e tests/unit)

test-func: all
	STRASS_TEST_REPORTS=$(CIRCLE_TEST_REPORTS) tests/func/runall.sh
