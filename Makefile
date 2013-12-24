SCSS=$(shell find data/styles/ -name "*.scss")
CSS=$(patsubst %.scss,%.css,$(SCSS))

all: $(CSS)

%.css: %.scss
	sassc $^ > $@

clean:
	rm -vf $(CSS)

setup:
	aptitude install php5-cli php5-sqlite php-pear php5-gd python-pip
	pip install libsass

serve:
	php -S localhost:8000

# Pour le moment, on restaure un site en version 1
restore:
	if ! test -n "$(ORIG)" ; then echo "ORIG manquant"; exit 1; fi
	git add include/ Makefile migrate
	git commit -m "pre-restore" || true
	git reset --hard
	git clean --force -d
	cp --archive --link $(ORIG)/config/ $(ORIG)/data $(ORIG)/resources ./

.PHONY: all clean serve setup
