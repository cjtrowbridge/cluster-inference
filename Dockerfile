FROM debian:11.7-slim

RUN mkdir -p /var/ai/models
RUN apt update && apt install -y git apache2 php \
        clang make cmake libsqlite3-dev build-essential nlohmann-json3-dev \
        php-bcmath php-cli php-bcmath php-bz2 php-curl php-intl php-gd php-mbstring php-zip

EXPOSE 22 80 443

CMD cd /var/ai && git clone https://github.com/cjtrowbridge/cluster-inference/ && cd cluster-inference && chmod +x runInference.sh && ./runInference.sh