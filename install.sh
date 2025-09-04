# Create directories
mkdir -p /free-nginx/public/
mkdir -p /free-nginx/

# Clone the GitHub repository
git clone https://github.com/TheBombGamer/servergeek.work.gd /free-nginx/
# Update package list
sudo apt update

# Install Certbot
sudo apt install -y certbot

# Install PHP and necessary extensions
sudo apt install -y php php-cli php-fpm php-mysql php-curl php-xml php-mbstring

# Install Composer
if ! command -v composer &> /dev/null; then
    php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
    php -r "if (hash_file('sha384', 'composer-setup.php') === 'hash_value_here') { echo 'Installer verified'; } else { echo 'Installer corrupt'; unlink('composer-setup.php'); } echo PHP_EOL;"
    php composer-setup.php --install-dir=/usr/local/bin --filename=composer
    php -r "unlink('composer-setup.php');"
fi

# Install Python
sudo apt install -y python3 python3-pip

# Install Go (Golang)
GO_VERSION="1.20.5" # Change this to the desired version
wget https://golang.org/dl/go$GO_VERSION.linux-amd64.tar.gz
sudo tar -C /usr/local -xzf go$GO_VERSION.linux-amd64.tar.gz
echo 'export PATH=$PATH:/usr/local/go/bin' >> ~/.bashrc
source ~/.bashrc

# Clean up
rm go$GO_VERSION.linux-amd64.tar.gz

# Change permissions on installer.sh if it exists
#if [ -f /free-nginx/conf.d/installer.sh ]; then
#    chmod +x /free-nginx/installer.sh
#else
#    echo "installer.sh not found in the cloned directory."
#fi

# Run the Nginx Docker container
docker run --name free-nginx \
  -p 80:80 \
  -p 443:443 \
  -v /free-nginx/public/:/usr/share/nginx/html:ro \
  -v /free-nginx/conf.d/:/etc/nginx/conf.d/ \
  -d nginx

echo "Nginx is running. Access it at https://servergeek.work.gd:80"

echo "Starting secondary services"

pip install -r requirements.txt


echo "Setup is complete."