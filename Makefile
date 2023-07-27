build:
	docker build --no-cache -t cluster-inference:2.0 .
	docker run -d -v /var/ai/models:/var/ai/models -p 800:80 -p 801:443 -p 802:2222 --name cluster-inference cluster-inference:2.0
	mkdir -p /var/ai/models
