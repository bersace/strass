all:

setup:
	aptitude install php5-cli php5-sqlite php-pear php5-gd

serve:
	php -S localhost:8000

# Pour le moment, on restaure un site en version 1
restore:
	git add include/ Makefile migrate
	git commit -m "pre-restore" || true
	git reset --hard
	git clean --force -d
	cp --archive --link $(ORIG)/config/ $(ORIG)/data $(ORIG)/resources ./

.PHONY: all serve setup
