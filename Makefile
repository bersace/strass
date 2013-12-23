all:

setup:
	aptitude install php5-cli php5-sqlite php-pear php5-gd

serve:
	php -S localhost:8000

.PHONY: all serve setup
