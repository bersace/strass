NAME="bersace/strass"

build:
	docker build --tag $(NAME) -f ../Dockerfile ..

clean:
	docker-compose down -v
	docker images --quiet $(NAME) | xargs --no-run-if-empty --verbose docker rmi -f

push:
	docker push $(NAME)
