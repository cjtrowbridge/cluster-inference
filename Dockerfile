FROM debian:11.7-slim

RUN mkdir -p /var/ai/models

RUN apt update && apt install -y git apache2 wget curl php \
	clang make cmake libsqlite3-dev build-essential nlohmann-json3-dev \
	php-bcmath php-cli php-bcmath php-bz2 php-curl php-intl php-gd php-mbstring php-zip

RUN apt-get update \
	&& apt-get install -y --no-install-recommends dialog \
	&& apt-get install -y --no-install-recommends openssh-server \
	&& echo "root:chadgpt!" | chpasswd
 
COPY sshd_config /etc/ssh/


RUN cd /var/ai && git clone https://github.com/cjtrowbridge/cluster-inference/ && cd cluster-inference && chmod +x setup.sh

RUN rm -rf /var/www/html/index.html

EXPOSE 2222 80 443

CMD /var/ai/cluster-inference/setup.sh
