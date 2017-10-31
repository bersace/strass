STRASS_ROOT ?= htdocs/
export STRASS_ROOT:=$(shell readlink -f $(STRASS_ROOT))/
CIRCLE_TEST_REPORTS ?= .

STRASS_EXEC=$(if $(CI),,docker run --rm --entrypoint "/usr/bin/env" -v $(PWD):/strass -v $(STRASS_ROOT):/strass/htdocs bersace/strass)

STYLES_DIRS=$(shell readlink -e static/styles $(STRASS_ROOT)/data/styles)
SCSS=$(shell find $(STYLES_DIRS) -name "*.scss")
CSS=$(patsubst %.scss,%.css,$(SCSS))
SUFSQL=include/Strass/Installer/sql/dump-suf.sql
FSESQL=include/Strass/Installer/sql/dump-fse.sql
GIT=git -C $(STRASS_ROOT)
COMMIT=$(GIT) diff --staged --exit-code --quiet || $(GIT) commit --quiet --message
HTML=$(STRASS_ROOT)500.html $(STRASS_ROOT)maintenance.html

default:

all: $(CSS) $(SUFSQL) $(FSESQL)

help:
	pager maint/DOC

%.css: %.scss
	rm -f $@
	sassc $< $@

include/Strass/Installer/sql/dump-%.sql: include/Strass/Installer/sql/schema.sql include/Strass/Installer/sql/%.sql
	$(MAKE) installer-$*.db
	sqlite3 installer-$*.db .dump > $@
	rm -f installer-$*.db

installer-%.db: include/Strass/Installer/sql/schema.sql include/Strass/Installer/sql/%.sql
	for f in $^ ; do sqlite3 -batch $@ ".read $$f"; done

clean:
	rm -vf $(CSS) $(HTML)
	rm -vf $(SUFSQL) $(FSESQL)
	rm -vf $(STRASS_ROOT)private/cache/*

distclean:
	$(MAKE) clean
	rm -rvf $(STRASS_ROOT)

setup:
	which sqlite3
	pip install --upgrade libsass

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

# Restaure les données uniquement. Pour tester la migration.
.PHONY: restore
restore:
ifeq (,$(wildcard $(STRASS_ROOT).git/))
	git checkout -- $(STRASS_ROOT)
	git clean --force -d $(STRASS_ROOT)
else
	$(GIT) reset --hard
	$(GIT) clean --force -d
endif

.PHONY: dbshell
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

REMOTE=maint/scripts/remote --config $(STRASS_ROOT)strass.conf


$(STRASS_ROOT).git:
	test -d $(STRASS_ROOT) || mkdir -p $(STRASS_ROOT)
	$(GIT) init .
	$(GIT) commit --quiet --allow-empty --message INIT

$(STRASS_ROOT).gitignore: maint/sitegitignore $(STRASS_ROOT).git
	cp $< $@
	$(GIT) add .gitignore
	$(COMMIT) SYSTÈME

.PHONY: config
config: $(STRASS_ROOT).gitignore
	$(REMOTE) config
	$(GIT) add strass.conf
	$(COMMIT) CONFIG

.PHONY: setmaint
setmaint:
	$(REMOTE) $@

.PHONY: unsetmaint
unsetmaint:
	$(REMOTE) $@

.PHONY: backup
backup:
	$(MAKE) setmaint
	$(GIT) reset --hard
	$(GIT) clean -df
	$(REMOTE) --verbose $@
	$(GIT) add -u .
	$(GIT) add .
	$(COMMIT) BACKUP

.PHONY: migrate
migrate: all
	$(STRASS_EXEC) maint/scripts/migrate;
	$(GIT) add --ignore-errors --all -- $(STRASS_ROOT) data/ private/;
	$(COMMIT) MIGRATION

.PHONY: upload
upload:
	$(COMMIT) --allow-empty UPLOAD
	$(MAKE) setmaint
	$(REMOTE) --verbose $@
	$(MAKE) unsetmaint

.PHONY: upload
upgrade:
	$(COMMIT) --allow-empty UPGRADE
	$(MAKE) setmaint
	$(REMOTE) --verbose upload --partial
	$(MAKE) unsetmaint
