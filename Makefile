STRASS_ROOT ?= htdocs/
export STRASS_ROOT:=$(shell readlink -f $(STRASS_ROOT))/
export DEBIAN_FRONTEND=noninteractive
CIRCLE_TEST_REPORTS ?= .

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
	pager maint/DOC

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
	apt-get install -y php5-cli php5-sqlite php-pear php5-gd php5-imagick \
	phpunit python-pip python-dev sqlite3 phantomjs
	pip install --upgrade libsass selenium

.PHONY: serve
serve: all
	maint/scripts/serve.sh

# Restaure les données uniquement. Pour tester la migration.
.PHONY: restore
restore:
ifeq (,$(wildcard $(STRASS_ROOT).git/))
	$(GIT) reset --hard
	$(GIT) clean --force -d
else
	git checkout -- $(STRASS_ROOT)
	git clean --force -d $(STRASS_ROOT)
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

.PHONY: test
test: test-unit test-func

test-unit:
	rm -rf $(TESTROOT)/*
	make $(TESTDB)
	STRASS_ROOT=$(shell readlink -f $(TESTROOT)) \
	phpunit --bootstrap $(shell readlink -e tests/unit/bootstrap.php) \
		--log-junit $(CIRCLE_TEST_REPORTS)/junit.xml \
		$(shell readlink -e tests/unit)

test-func: all
	STRASS_TEST_REPORTS=$(CIRCLE_TEST_REPORTS) tests/func/run.sh

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
setmaint: $(STRASS_ROOT)maintenance.html
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
	$(GIT) add .
	$(COMMIT) BACKUP

.PHONY: migrate
migrate: all
	maint/scripts/migrate;
	$(GIT) add --ignore-errors --all -- $(STRASS_ROOT) data/ private/;
	$(COMMIT) MIGRATION

.PHONY: upload
upload: $(STRASS_ROOT)500.html
	$(COMMIT) --allow-empty UPLOAD
	$(MAKE) setmaint
	$(REMOTE) --verbose $@
	$(MAKE) unsetmaint

.PHONY: upload
upgrade: $(STRASS_ROOT)500.html
	$(COMMIT) --allow-empty UPGRADE
	$(MAKE) setmaint
	$(REMOTE) --verbose upload --partial
	$(MAKE) unsetmaint
