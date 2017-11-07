default:

clean:
	find . -name "*.css" -delete
	find . -name .webassets-cache | xargs -rt rm -rf

%:
	webassets --config src/webassets.yml $@
