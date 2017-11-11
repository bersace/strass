default:

clean:
	rm -f html/manifest.json html/*.css
	find . -name .webassets-cache | xargs -rt rm -rf

%:
	webassets --config src/webassets.yml $@
