#!/bin/bash

# Make sure the script is being run as root
if [ "$EUID" -ne 0 ]
  then echo "Please run as root"
  exit
fi

# Check if the git repository is up to date
git fetch origin
LOCAL=$(git rev-parse @)
REMOTE=$(git rev-parse @{u})

if [ $LOCAL = $REMOTE ]; then
    echo "Repository is up to date. Continuing with the script."
else
    echo "Repository is not up to date. Pulling changes from GitHub..."
    git pull origin
    exec ./setup.sh
    exit 0
fi

# Install/verify the required packages and dependencies
apt-get update
apt-get install -y git apache2 wget curl php php-{cli,bcmath,bz2,curl,intl,gd,mbstring,mysql,zip,sqlite3} \
    clang make cmake libsqlite3-dev build-essential nlohmann-json3-dev


# Now make sure the /var/ai directory is there and permissioned correctly
mkdir /var/ai
# Determine the current user
chown -R www-data:users /var/ai
chmod -R 755 /var/ai

echo "Found Models"
cd /var/ai/models && ls

# Install GGML
cd /var/ai/
if [ -d "GGML" ]; then rm -Rf GGML; fi
git clone https://github.com/ggerganov/ggml 
cd /var/ai/ggml
mkdir build
cd /var/ai/ggml/build
cmake ..
make

# Install llama.cpp
cd /var/ai
git clone https://github.com/ggerganov/llama.cpp
cd llama.cpp
make

# Copy the php directory to apache webroot
cp -R /var/ai/cluster-inference/php/* /var/www/html/

# Set up the new config file to allow htaccess
cp /var/ai/cluster-inference/apache2.conf /etc/apache2/sites-available/000-default.conf

# Enable the rewrite module
a2enmod rewrite

service apache2 start
service ssh start

while true
do
	sleep 100
done
