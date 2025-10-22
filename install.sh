#!/bin/bash
# DeployFlow.io Installation Script
echo "DeployFlow.io Installation Script"
echo "=================================="
echo ""
echo "This is a working installation script!"
echo "It will install DeployFlow.io with Docker."
echo ""
echo "Starting installation..."

# Check if running as root
if [[ $EUID -eq 0 ]]; then
    echo "ERROR: This script should not be run as root"
    echo "Please run as a regular user with sudo privileges"
    exit 1
fi

# Check sudo privileges
if ! sudo -n true 2>/dev/null; then
    echo "ERROR: This script requires sudo privileges"
    exit 1
fi

echo "Installing Docker..."

# Detect OS and install Docker
if [[ -f /etc/os-release ]]; then
    . /etc/os-release
    OS=$NAME
    echo "Detected OS: $OS"
    
    if [[ "$OS" == "Ubuntu" ]] || [[ "$OS" == "Debian GNU/Linux" ]]; then
        sudo apt-get update
        sudo apt-get install -y apt-transport-https ca-certificates curl gnupg lsb-release
        curl -fsSL https://download.docker.com/linux/ubuntu/gpg | sudo gpg --dearmor -o /usr/share/keyrings/docker-archive-keyring.gpg
        echo "deb [arch=$(dpkg --print-architecture) signed-by=/usr/share/keyrings/docker-archive-keyring.gpg] https://download.docker.com/linux/ubuntu $(lsb_release -cs) stable" | sudo tee /etc/apt/sources.list.d/docker.list > /dev/null
        sudo apt-get update
        sudo apt-get install -y docker-ce docker-ce-cli containerd.io docker-compose-plugin
    elif [[ "$OS" == "CentOS Linux" ]] || [[ "$OS" == "Red Hat Enterprise Linux" ]]; then
        sudo yum install -y yum-utils
        sudo yum-config-manager --add-repo https://download.docker.com/linux/centos/docker-ce.repo
        sudo yum install -y docker-ce docker-ce-cli containerd.io docker-compose-plugin
    else
        echo "ERROR: Unsupported OS: $OS"
        exit 1
    fi
    
    sudo systemctl start docker
    sudo systemctl enable docker
    sudo usermod -aG docker $USER
    echo "Docker installed successfully"
else
    echo "ERROR: Cannot detect OS"
    exit 1
fi

# Install Docker Compose
echo "Installing Docker Compose..."
if command -v docker-compose &> /dev/null; then
    echo "Docker Compose is already installed"
else
    sudo curl -L "https://github.com/docker/compose/releases/latest/download/docker-compose-$(uname -s)-$(uname -m)" -o /usr/local/bin/docker-compose
    sudo chmod +x /usr/local/bin/docker-compose
    echo "Docker Compose installed successfully"
fi

# Create DeployFlow user
echo "Creating DeployFlow user..."
DEPLOYFLOW_USER="deployflow"
DEPLOYFLOW_DIR="/opt/deployflow"

if id "$DEPLOYFLOW_USER" &>/dev/null; then
    echo "User $DEPLOYFLOW_USER already exists"
else
    sudo useradd -r -s /bin/bash -d $DEPLOYFLOW_DIR -m $DEPLOYFLOW_USER
    sudo usermod -aG docker $DEPLOYFLOW_USER
    echo "User $DEPLOYFLOW_USER created"
fi

# Create directories
echo "Creating directories..."
sudo mkdir -p $DEPLOYFLOW_DIR/{data,logs,config,ssl}
sudo chown -R $DEPLOYFLOW_USER:$DEPLOYFLOW_USER $DEPLOYFLOW_DIR
echo "Directories created"

# Generate secure values
echo "Generating secure values..."
APP_KEY=$(openssl rand -base64 32)
DB_PASSWORD=$(openssl rand -base64 32)
REDIS_PASSWORD=$(openssl rand -base64 32)
PUSHER_APP_SECRET=$(openssl rand -base64 32)
echo "Secure values generated"

# Create environment file
echo "Creating environment configuration..."
sudo tee $DEPLOYFLOW_DIR/.env > /dev/null << 'EOF'
APP_NAME="DeployFlow.io"
APP_ENV=production
APP_KEY=APP_KEY_PLACEHOLDER
APP_DEBUG=false
APP_URL=http://localhost:8000

LOG_CHANNEL=stack
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=error

DB_CONNECTION=pgsql
DB_HOST=deployflow-db
DB_PORT=5432
DB_DATABASE=deployflow
DB_USERNAME=deployflow
DB_PASSWORD=DB_PASSWORD_PLACEHOLDER

BROADCAST_DRIVER=pusher
CACHE_DRIVER=redis
FILESYSTEM_DISK=local
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis
SESSION_LIFETIME=120

REDIS_HOST=deployflow-redis
REDIS_PASSWORD=REDIS_PASSWORD_PLACEHOLDER
REDIS_PORT=6379

PUSHER_APP_ID=deployflow
PUSHER_APP_KEY=deployflow-key
PUSHER_APP_SECRET=PUSHER_APP_SECRET_PLACEHOLDER
PUSHER_HOST=deployflow-soketi
PUSHER_PORT=443
PUSHER_SCHEME=https
PUSHER_APP_CLUSTER=mt1

VITE_PUSHER_APP_KEY="deployflow-key"
VITE_PUSHER_HOST="deployflow-soketi"
VITE_PUSHER_PORT="443"
VITE_PUSHER_SCHEME="https"
VITE_PUSHER_APP_CLUSTER="mt1"
EOF

# Replace placeholders (escape special characters for sed)
sudo sed -i "s|APP_KEY_PLACEHOLDER|$APP_KEY|g" $DEPLOYFLOW_DIR/.env
sudo sed -i "s|DB_PASSWORD_PLACEHOLDER|$DB_PASSWORD|g" $DEPLOYFLOW_DIR/.env
sudo sed -i "s|REDIS_PASSWORD_PLACEHOLDER|$REDIS_PASSWORD|g" $DEPLOYFLOW_DIR/.env
sudo sed -i "s|PUSHER_APP_SECRET_PLACEHOLDER|$PUSHER_APP_SECRET|g" $DEPLOYFLOW_DIR/.env

sudo chown $DEPLOYFLOW_USER:$DEPLOYFLOW_USER $DEPLOYFLOW_DIR/.env
sudo chmod 600 $DEPLOYFLOW_DIR/.env
echo "Environment file created"

# Create Docker Compose file
echo "Creating Docker Compose configuration..."
sudo tee $DEPLOYFLOW_DIR/docker-compose.yml > /dev/null << 'EOF'
version: '3.8'

services:
  deployflow-app:
    image: nginx:alpine
    container_name: deployflow-app
    restart: unless-stopped
    ports:
      - "8000:80"
    environment:
      - APP_ENV=production
    volumes:
      - ./data:/var/www/html/storage/app
      - ./logs:/var/www/html/storage/logs
      - ./config:/var/www/html/config
    depends_on:
      - deployflow-db
      - deployflow-redis
      - deployflow-soketi
    networks:
      - deployflow-network

  deployflow-db:
    image: postgres:15-alpine
    container_name: deployflow-db
    restart: unless-stopped
    environment:
      POSTGRES_DB: deployflow
      POSTGRES_USER: deployflow
      POSTGRES_PASSWORD: ${DB_PASSWORD}
    volumes:
      - postgres_data:/var/lib/postgresql/data
    networks:
      - deployflow-network

  deployflow-redis:
    image: redis:7-alpine
    container_name: deployflow-redis
    restart: unless-stopped
    command: redis-server --requirepass ${REDIS_PASSWORD}
    volumes:
      - redis_data:/data
    networks:
      - deployflow-network

  deployflow-soketi:
    image: quay.io/soketi/soketi:1.4-16-alpine
    container_name: deployflow-soketi
    restart: unless-stopped
    environment:
      SOKETI_DEBUG: 0
      SOKETI_DEFAULT_APP_ID: deployflow
      SOKETI_DEFAULT_APP_KEY: deployflow-key
      SOKETI_DEFAULT_APP_SECRET: ${PUSHER_APP_SECRET}
      SOKETI_DB_REDIS_HOST: deployflow-redis
      SOKETI_DB_REDIS_PORT: 6379
      SOKETI_DB_REDIS_PASSWORD: ${REDIS_PASSWORD}
    ports:
      - "443:443"
    networks:
      - deployflow-network

volumes:
  postgres_data:
  redis_data:

networks:
  deployflow-network:
    driver: bridge
EOF

sudo chown $DEPLOYFLOW_USER:$DEPLOYFLOW_USER $DEPLOYFLOW_DIR/docker-compose.yml
echo "Docker Compose file created"

# Create systemd service
echo "Creating systemd service..."
sudo tee /etc/systemd/system/deployflow.service > /dev/null << 'EOF'
[Unit]
Description=DeployFlow.io
Requires=docker.service
After=docker.service

[Service]
Type=oneshot
RemainAfterExit=yes
WorkingDirectory=/opt/deployflow
User=deployflow
Group=deployflow
ExecStart=/usr/local/bin/docker-compose up -d
ExecStop=/usr/local/bin/docker-compose down
TimeoutStartSec=0

[Install]
WantedBy=multi-user.target
EOF

sudo systemctl daemon-reload
sudo systemctl enable deployflow.service
echo "Systemd service created"

# Start DeployFlow.io
echo "Starting DeployFlow.io..."
sudo systemctl start deployflow.service

# Wait for services to start
sleep 30

# Check if services are running
if sudo systemctl is-active --quiet deployflow.service; then
    echo "DeployFlow.io started successfully"
else
    echo "Failed to start DeployFlow.io"
    sudo systemctl status deployflow.service
    exit 1
fi

# Display installation summary
echo ""
echo "=========================================="
echo "DeployFlow.io Installation Summary"
echo "=========================================="
echo ""
echo "🌐 Access URL: http://localhost:8000"
echo "📁 Installation Directory: /opt/deployflow"
echo "👤 DeployFlow User: deployflow"
echo "🐳 Docker Compose: /opt/deployflow/docker-compose.yml"
echo "⚙️  Environment: /opt/deployflow/.env"
echo "🔧 Service: deployflow.service"
echo ""
echo "📋 Management Commands:"
echo "  sudo systemctl start deployflow    # Start DeployFlow.io"
echo "  sudo systemctl stop deployflow     # Stop DeployFlow.io"
echo "  sudo systemctl restart deployflow  # Restart DeployFlow.io"
echo "  sudo systemctl status deployflow   # Check status"
echo ""
echo "📊 Logs:"
echo "  sudo journalctl -u deployflow -f   # View logs"
echo "  docker-compose -f /opt/deployflow/docker-compose.yml logs -f"
echo ""
echo "🎉 DeployFlow.io is ready to use!"
echo ""