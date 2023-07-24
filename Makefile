build:
  docker build -t cluster-inference .
  mkdir -p /var/ai/models
  docker create -v /var/ai/models --name ai-models cluster-inference
run:
  docker run --volumes-from ai-models cluster-inference
