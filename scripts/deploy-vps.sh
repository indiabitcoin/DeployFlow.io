#!/bin/bash
# DeployFlow.io VPS Deployment Script
# This script runs on the VPS server to set up DeployFlow.io

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

print_status() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

print_success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

print_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# Function to check if command exists
command_exists() {
    command -v "$1" >/dev/null 2>&1
}

# Function to install PHP 8.4
install_php() {
    print_status "Installing PHP 8.4..."
    
    sudo apt update
    sudo apt install -y software-properties-common
    sudo add-apt-repository ppa:ondrej/php -y
    sudo apt update
    sudo apt install -y php8.4 php8.4-fpm php8.4-cli php8.4-mysql php8.4-pgsql php8.4-xml php8.4-gd php8.4-curl php8.4-mbstring php8.4-zip php8.4-bcmath php8.4-intl php8.4-redis
    
    print_success "PHP 8.4 installed successfully!"
}

# Function to install Composer
install_composer() {
    print_status "Installing Composer..."
    
    if ! command_exists composer; then
        curl -sS https://getcomposer.org/installer | php
        sudo mv composer.phar /usr/local/bin/composer
        sudo chmod +x /usr/local/bin/composer
    fi
    
    print_success "Composer installed successfully!"
}

# Function to install Node.js
install_nodejs() {
    print_status "Installing Node.js..."
    
    if ! command_exists node; then
        curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
        sudo apt install -y nodejs
    fi
    
    print_success "Node.js installed successfully!"
}

# Function to install PostgreSQL
install_postgresql() {
    print_status "Installing PostgreSQL..."
    
    if ! command_exists psql; then
        sudo apt install -y postgresql postgresql-contrib
        sudo systemctl start postgresql
        sudo systemctl enable postgresql
    fi
    
    print_success "PostgreSQL installed successfully!"
}

# Function to install Nginx
install_nginx() {
    print_status "Installing Nginx..."
    
    if ! command_exists nginx; then
        sudo apt install -y nginx
        sudo systemctl start nginx
        sudo systemctl enable nginx
    fi
    
    print_success "Nginx installed successfully!"
}

# Function to install Redis
install_redis() {
    print_status "Installing Redis..."
    
    if ! command_exists redis-server; then
        sudo apt install -y redis-server
        sudo systemctl start redis-server
        sudo systemctl enable redis-server
    fi
    
    print_success "Redis installed successfully!"
}

# Function to create DeployFlow.io user
create_user() {
    print_status "Creating DeployFlow.io user..."
    
    if ! id "deployflow" &>/dev/null; then
        sudo adduser deployflow --disabled-password --gecos ""
        sudo usermod -aG sudo deployflow
    fi
    
    print_success "DeployFlow.io user created successfully!"
}

# Function to setup application
setup_application() {
    print_status "Setting up DeployFlow.io application..."
    
    # Set working directory
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
    
    print_success "Application setup completed!"
}

# Function to configure database
configure_database() {
    print_status "Configuring database..."
    
    # Create database and user
    sudo -u postgres psql << EOF
CREATE DATABASE deployflow;
CREATE USER deployflow WITH PASSWORD 'deployflow_password';
GRANT ALL PRIVILEGES ON DATABASE deployflow TO deployflow;
\q
EOF
    
    print_success "Database configured successfully!"
}

# Function to configure environment
configure_environment() {
    print_status "Configuring environment..."
    
    # Copy environment file
    sudo -u deployflow cp .env.example .env
    
    # Generate application key
    sudo -u deployflow php artisan key:generate
    
    # Update database configuration
    sudo -u deployflow sed -i 's/DB_DATABASE=laravel/DB_DATABASE=deployflow/' .env
    sudo -u deployflow sed -i 's/DB_USERNAME=root/DB_USERNAME=deployflow/' .env
    sudo -u deployflow sed -i 's/DB_PASSWORD=/DB_PASSWORD=deployflow_password/' .env
    
    print_success "Environment configured successfully!"
}

# Function to run migrations
run_migrations() {
    print_status "Running database migrations..."
    
    sudo -u deployflow php artisan migrate --force
    
    print_success "Migrations completed successfully!"
}

# Function to configure Nginx
configure_nginx() {
    print_status "Configuring Nginx..."
    
    sudo tee /etc/nginx/sites-available/deployflow << EOF
server {
    listen 80;
    server_name _;
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
    sudo ln -sf /etc/nginx/sites-available/deployflow /etc/nginx/sites-enabled/
    sudo rm -f /etc/nginx/sites-enabled/default
    
    # Test configuration
    sudo nginx -t
    
    print_success "Nginx configured successfully!"
}

# Function to configure PHP-FPM
configure_php_fpm() {
    print_status "Configuring PHP-FPM..."
    
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
    
    print_success "PHP-FPM configured successfully!"
}

# Function to setup SSL
setup_ssl() {
    print_status "Setting up SSL with Let's Encrypt..."
    
    if ! command_exists certbot; then
        sudo apt install -y certbot python3-certbot-nginx
    fi
    
    print_warning "SSL setup requires domain name. Run manually:"
    print_warning "sudo certbot --nginx -d your-domain.com"
    
    print_success "SSL setup instructions provided!"
}

# Function to restart services
restart_services() {
    print_status "Restarting services..."
    
    sudo systemctl restart php8.4-fpm
    sudo systemctl restart nginx
    sudo systemctl restart postgresql
    sudo systemctl restart redis-server
    
    print_success "Services restarted successfully!"
}

# Function to create systemd service for queue worker
create_queue_service() {
    print_status "Creating queue worker service..."
    
    sudo tee /etc/systemd/system/deployflow-queue.service << EOF
[Unit]
Description=DeployFlow.io Queue Worker
After=network.target

[Service]
User=deployflow
Group=deployflow
WorkingDirectory=/home/deployflow/deployflow
ExecStart=/usr/bin/php artisan queue:work --verbose --tries=3 --timeout=90
Restart=always
RestartSec=10

[Install]
WantedBy=multi-user.target
EOF
    
    sudo systemctl enable deployflow-queue
    sudo systemctl start deployflow-queue
    
    print_success "Queue worker service created!"
}

# Main installation function
main() {
    print_status "Starting DeployFlow.io VPS installation..."
    
    # Update system
    sudo apt update && sudo apt upgrade -y
    
    # Install dependencies
    install_php
    install_composer
    install_nodejs
    install_postgresql
    install_nginx
    install_redis
    
    # Create user
    create_user
    
    # Setup application
    setup_application
    
    # Configure database
    configure_database
    
    # Configure environment
    configure_environment
    
    # Run migrations
    run_migrations
    
    # Configure web server
    configure_nginx
    configure_php_fpm
    
    # Setup SSL
    setup_ssl
    
    # Create queue service
    create_queue_service
    
    # Restart services
    restart_services
    
    print_success "DeployFlow.io installation completed!"
    print_status "Your application is now available at: http://$(curl -s ifconfig.me)"
    print_status "Next steps:"
    print_status "1. Configure your domain name"
    print_status "2. Setup SSL certificate: sudo certbot --nginx -d your-domain.com"
    print_status "3. Update APP_URL in .env file"
    print_status "4. Configure firewall: sudo ufw allow 80,443,22"
}

# Run main function
main
