NAME="bersace/strass"

build:
	$(MAKE) -C .. all
	docker build --tag $(NAME) -f Dockerfile ..

clean:
	docker-compose -f ../docker-compose.yml down -v
	docker images --quiet $(NAME) | xargs --no-run-if-empty --verbose docker rmi -f

push:
	docker push $(NAME)

run:
	docker-compose -f ../docker-compose.yml -f docker-compose.build.yml up
