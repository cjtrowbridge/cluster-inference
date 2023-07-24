build:
	docker build -t cluster-inference:2.1 .
	mkdir -p /var/ai/models
	docker create -v /var/ai/models:/var/ai/models --name cluster-inference cluster-inference
run:
	docker run cluster-inference
