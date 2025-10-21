# DeployFlow.io Production Deployment Guide

## Hosting Options

### 1. VPS/Server Hosting (Recommended)
- **DigitalOcean Droplets**
- **Linode VPS**
- **Vultr Cloud Compute**
- **AWS EC2**
- **Google Cloud Compute Engine**
- **Azure Virtual Machines**

### 2. Shared Hosting (Limited)
- **Hostinger**
- **SiteGround**
- **Bluehost**
- **Note**: Requires PHP 8.4+ and Laravel support

### 3. Cloud Platforms
- **Railway**
- **Render**
- **Heroku** (with modifications)
- **Fly.io**
- **DigitalOcean App Platform**

### 4. Container Platforms
- **Docker** (any Docker-compatible hosting)
- **Kubernetes** (GKE, EKS, AKS)

## System Requirements

### Minimum Requirements
- **PHP**: 8.4+
- **Database**: PostgreSQL 15+ or MySQL 8.0+
- **Web Server**: Nginx or Apache
- **Memory**: 2GB RAM
- **Storage**: 20GB SSD
- **CPU**: 2 cores

### Recommended Requirements
- **PHP**: 8.4+
- **Database**: PostgreSQL 15+
- **Web Server**: Nginx
- **Memory**: 4GB RAM
- **Storage**: 50GB SSD
- **CPU**: 4 cores

## Quick Deployment Script

```bash
#!/bin/bash
# DeployFlow.io Installation Script

# Update system
sudo apt update && sudo apt upgrade -y

# Install PHP 8.4
sudo apt install -y software-properties-common
sudo add-apt-repository ppa:ondrej/php -y
sudo apt update
sudo apt install -y php8.4 php8.4-fpm php8.4-cli php8.4-mysql php8.4-pgsql php8.4-xml php8.4-gd php8.4-curl php8.4-mbstring php8.4-zip php8.4-bcmath php8.4-intl php8.4-redis

# Install Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

# Install Node.js and NPM
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
sudo apt install -y nodejs

# Install PostgreSQL
sudo apt install -y postgresql postgresql-contrib

# Install Nginx
sudo apt install -y nginx

# Install Redis
sudo apt install -y redis-server

# Install Git
sudo apt install -y git

# Create DeployFlow.io user
sudo adduser deployflow --disabled-password --gecos ""

# Clone repository
sudo -u deployflow git clone https://github.com/yourusername/DeployFlow.io.git /home/deployflow/deployflow
cd /home/deployflow/deployflow

# Install dependencies
sudo -u deployflow composer install --no-dev --optimize-autoloader
sudo -u deployflow npm install
sudo -u deployflow npm run build

# Set permissions
sudo chown -R deployflow:deployflow /home/deployflow/deployflow
sudo chmod -R 755 /home/deployflow/deployflow
sudo chmod -R 775 /home/deployflow/deployflow/storage
sudo chmod -R 775 /home/deployflow/deployflow/bootstrap/cache

# Configure environment
sudo -u deployflow cp .env.example .env
sudo -u deployflow php artisan key:generate

# Configure database
sudo -u postgres createdb deployflow
sudo -u postgres createuser deployflow --pwprompt

# Run migrations
sudo -u deployflow php artisan migrate --force

# Configure Nginx
sudo tee /etc/nginx/sites-available/deployflow << EOF
server {
    listen 80;
    server_name your-domain.com;
    root /home/deployflow/deployflow/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;

    charset utf-8;

    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.4-fpm.sock;
        fastcgi_param SCRIPT_FILENAME \$realpath_root\$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
EOF

# Enable site
sudo ln -s /etc/nginx/sites-available/deployflow /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx

# Configure PHP-FPM
sudo tee /etc/php/8.4/fpm/pool.d/deployflow.conf << EOF
[deployflow]
user = deployflow
group = deployflow
listen = /var/run/php/php8.4-fpm-deployflow.sock
listen.owner = www-data
listen.group = www-data
php_admin_value[disable_functions] = exec,passthru,shell_exec,system
php_admin_flag[allow_url_fopen] = off
php_admin_value[memory_limit] = 512M
php_admin_value[max_execution_time] = 300
php_admin_value[max_input_vars] = 3000
php_admin_value[post_max_size] = 100M
php_admin_value[upload_max_filesize] = 100M
EOF

# Restart services
sudo systemctl restart php8.4-fpm
sudo systemctl restart nginx
sudo systemctl restart postgresql
sudo systemctl restart redis-server

# Setup SSL (Let's Encrypt)
sudo apt install -y certbot python3-certbot-nginx
sudo certbot --nginx -d your-domain.com

echo "DeployFlow.io installation completed!"
echo "Visit: https://your-domain.com"
echo "Default admin: First registered user becomes admin"
