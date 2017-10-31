default:

SCSS=$(wildcard html/*.scss)
CSS=$(patsubst %.scss,%.css,$(SCSS))

%.css: %.scss
	rm -f $@
	sassc $< $@

build: $(CSS)

clean:
	find html/ -name "*.css" -delete
