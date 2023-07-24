build:
	docker build -t cluster-inference:2.1 .
	mkdir -p /var/ai/models
	docker create -v /var/ai/models:/var/ai/models -p 888:80 -p 889:443 --name cluster-inference cluster-inference
run:
	docker run -d cluster-inference
