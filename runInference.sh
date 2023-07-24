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
chown -R $user:users /var/ai
chmod -R 755 /var/ai
cd /var/ai 

# Install GGML
git clone https://github.com/ggerganov/ggml 
cd ggml
mkdir build
cd build
cmake ..

/var/ai/ggml/examples/gpt-2/download-ggml-model.sh 117M

if [ -z "$1" ]
  then
    /var/ai/ggml/build/bin/gpt-2 -m models/gpt-2-117M/ggml-model.bin -p "The meaning of life is"
  else
    /var/ai/ggml/build/bin/gpt-2 -m models/gpt-2-117M/ggml-model.bin -p "$1"
fi

