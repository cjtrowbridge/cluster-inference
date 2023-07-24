build:
	docker build --no-cache -t cluster-inference:2.3 .
	docker run -d -v /var/ai/models:/var/ai/models -p 888:80 -p 889:443 --name cluster-inference cluster-inference:2.3
	mkdir -p /var/ai/models
