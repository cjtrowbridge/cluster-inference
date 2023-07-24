#!/bin/bash

# Make sure the script is being run as root
if [ "$EUID" -ne 0 ]
  then echo "Please run as root"
  exit
fi

# Install/verify the required packages and dependencies
apt-get update
apt-get install -y git apache2 wget curl php php-{cli,bcmath,bz2,curl,intl,gd,mbstring,mysql,zip} \
    clang make cmake libsqlite3-dev build-essential nlohmann-json3-dev

# Now make sure the /var/ai directory is there and permissioned correctly
mkdir /var/ai
# Determine the current user
chown -R users:users /var/ai
chmod -R 755 /var/ai

echo "Found Models"
cd /var/ai/models && ls

# Install GGML
cd /var/ai/
git clone https://github.com/ggerganov/ggml 
cd /var/ai/ggml
mkdir build
cd /var/ai/ggml/build
cmake ..
make

#copy php directory to apache webroot
#cp -R /var/ai/cluster-inference/php /var/www/html
