// DeployFlow.io CDN Worker - Working Installation Script
export default {
  async fetch(request, env, ctx) {
    const url = new URL(request.url);
    const path = url.pathname;
    
    // CORS headers
    const corsHeaders = {
      'Access-Control-Allow-Origin': '*',
      'Access-Control-Allow-Methods': 'GET, HEAD, OPTIONS',
      'Access-Control-Allow-Headers': 'Content-Type',
      'Cache-Control': 'public, max-age=3600'
    };
    
    // Handle preflight requests
    if (request.method === 'OPTIONS') {
      return new Response(null, { 
        status: 204, 
        headers: corsHeaders 
      });
    }
    
    // Working file mappings with proper escaping
    const files = {
      '/install.sh': `#!/bin/bash
# DeployFlow.io Installation Script
set -e

# Colors for output
RED='\\033[0;31m'
GREEN='\\033[0;32m'
YELLOW='\\033[1;33m'
BLUE='\\033[0;34m'
NC='\\033[0m'

log_info() {
    echo -e "\\${BLUE}[INFO]\\${NC} $1"
}

log_success() {
    echo -e "\\${GREEN}[SUCCESS]\\${NC} $1"
}

log_error() {
    echo -e "\\${RED}[ERROR]\\${NC} $1"
}

# Configuration
DEPLOYFLOW_DIR="/opt/deployflow"
DEPLOYFLOW_USER="deployflow"
DEPLOYFLOW_PORT="8000"

# Check if running as root
if [[ $EUID -eq 0 ]]; then
    log_error "This script should not be run as root for security reasons"
    log_info "Please run as a regular user with sudo privileges"
    exit 1
fi

# Check sudo privileges
if ! sudo -n true 2>/dev/null; then
    log_error "This script requires sudo privileges"
    exit 1
fi

log_info "Starting DeployFlow.io installation..."

# Detect OS
if [[ -f /etc/os-release ]]; then
    . /etc/os-release
    OS=$NAME
    VER=$VERSION_ID
    log_info "Detected OS: $OS $VER"
else
    log_error "Cannot detect OS"
    exit 1
fi

# Install Docker
log_info "Installing Docker..."
if command -v docker &> /dev/null; then
    log_success "Docker is already installed"
else
    case $OS in
        "Ubuntu"|"Debian GNU/Linux")
            sudo apt-get update
            sudo apt-get install -y apt-transport-https ca-certificates curl gnupg lsb-release
            curl -fsSL https://download.docker.com/linux/ubuntu/gpg | sudo gpg --dearmor -o /usr/share/keyrings/docker-archive-keyring.gpg
            echo "deb [arch=$(dpkg --print-architecture) signed-by=/usr/share/keyrings/docker-archive-keyring.gpg] https://download.docker.com/linux/ubuntu $(lsb_release -cs) stable" | sudo tee /etc/apt/sources.list.d/docker.list > /dev/null
            sudo apt-get update
            sudo apt-get install -y docker-ce docker-ce-cli containerd.io docker-compose-plugin
            ;;
        "CentOS Linux"|"Red Hat Enterprise Linux")
            sudo yum install -y yum-utils
            sudo yum-config-manager --add-repo https://download.docker.com/linux/centos/docker-ce.repo
            sudo yum install -y docker-ce docker-ce-cli containerd.io docker-compose-plugin
            ;;
        *)
            log_error "Unsupported OS: $OS"
            exit 1
            ;;
    esac
    
    sudo systemctl start docker
    sudo systemctl enable docker
    sudo usermod -aG docker $USER
    log_success "Docker installed successfully"
fi

# Install Docker Compose
log_info "Installing Docker Compose..."
if command -v docker-compose &> /dev/null; then
    log_success "Docker Compose is already installed"
else
    sudo curl -L "https://github.com/docker/compose/releases/latest/download/docker-compose-$(uname -s)-$(uname -m)" -o /usr/local/bin/docker-compose
    sudo chmod +x /usr/local/bin/docker-compose
    log_success "Docker Compose installed successfully"
fi

# Create DeployFlow user
log_info "Creating DeployFlow user..."
if id "$DEPLOYFLOW_USER" &>/dev/null; then
    log_success "User $DEPLOYFLOW_USER already exists"
else
    sudo useradd -r -s /bin/bash -d $DEPLOYFLOW_DIR -m $DEPLOYFLOW_USER
    sudo usermod -aG docker $DEPLOYFLOW_USER
    log_success "User $DEPLOYFLOW_USER created"
fi

# Create directories
log_info "Creating directories..."
sudo mkdir -p $DEPLOYFLOW_DIR/{data,logs,config,ssl}
sudo chown -R $DEPLOYFLOW_USER:$DEPLOYFLOW_USER $DEPLOYFLOW_DIR
log_success "Directories created"

# Generate secure values
log_info "Generating secure values..."
APP_KEY=$(openssl rand -base64 32)
DB_PASSWORD=$(openssl rand -base64 32)
REDIS_PASSWORD=$(openssl rand -base64 32)
PUSHER_APP_SECRET=$(openssl rand -base64 32)
log_success "Secure values generated"

# Create environment file
log_info "Creating environment configuration..."
sudo tee $DEPLOYFLOW_DIR/.env > /dev/null << 'ENVEOF'
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
ENVEOF

# Replace placeholders with actual values
sudo sed -i "s/APP_KEY_PLACEHOLDER/$APP_KEY/g" $DEPLOYFLOW_DIR/.env
sudo sed -i "s/DB_PASSWORD_PLACEHOLDER/$DB_PASSWORD/g" $DEPLOYFLOW_DIR/.env
sudo sed -i "s/REDIS_PASSWORD_PLACEHOLDER/$REDIS_PASSWORD/g" $DEPLOYFLOW_DIR/.env
sudo sed -i "s/PUSHER_APP_SECRET_PLACEHOLDER/$PUSHER_APP_SECRET/g" $DEPLOYFLOW_DIR/.env

sudo chown $DEPLOYFLOW_USER:$DEPLOYFLOW_USER $DEPLOYFLOW_DIR/.env
sudo chmod 600 $DEPLOYFLOW_DIR/.env
log_success "Environment file created"

# Create Docker Compose file
log_info "Creating Docker Compose configuration..."
sudo tee $DEPLOYFLOW_DIR/docker-compose.yml > /dev/null << 'COMPOSEEOF'
version: '3.8'

services:
  deployflow-app:
    image: deployflow/deployflow:latest
    container_name: deployflow-app
    restart: unless-stopped
    ports:
      - "8000:8000"
    environment:
      - APP_ENV=production
    env_file:
      - .env
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
COMPOSEEOF

sudo chown $DEPLOYFLOW_USER:$DEPLOYFLOW_USER $DEPLOYFLOW_DIR/docker-compose.yml
log_success "Docker Compose file created"

# Create systemd service
log_info "Creating systemd service..."
sudo tee /etc/systemd/system/deployflow.service > /dev/null << 'SERVICEEOF'
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
SERVICEEOF

sudo systemctl daemon-reload
sudo systemctl enable deployflow.service
log_success "Systemd service created"

# Start DeployFlow.io
log_info "Starting DeployFlow.io..."
sudo systemctl start deployflow.service

# Wait for services to start
sleep 30

# Check if services are running
if sudo systemctl is-active --quiet deployflow.service; then
    log_success "DeployFlow.io started successfully"
else
    log_error "Failed to start DeployFlow.io"
    sudo systemctl status deployflow.service
    exit 1
fi

# Display installation summary
log_success "DeployFlow.io installation completed!"
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
echo ""`,

      '/upgrade.sh': `#!/bin/bash
# DeployFlow.io Upgrade Script
set -e

# Colors
RED='\\033[0;31m'
GREEN='\\033[0;32m'
YELLOW='\\033[1;33m'
BLUE='\\033[0;34m'
NC='\\033[0m'

log_info() {
    echo -e "\\${BLUE}[INFO]\\${NC} $1"
}

log_success() {
    echo -e "\\${GREEN}[SUCCESS]\\${NC} $1"
}

log_error() {
    echo -e "\\${RED}[ERROR]\\${NC} $1"
}

# Configuration
DEPLOYFLOW_DIR="/opt/deployflow"
DEPLOYFLOW_USER="deployflow"

# Check if DeployFlow.io is installed
if [ ! -d "$DEPLOYFLOW_DIR" ]; then
    log_error "DeployFlow.io is not installed"
    log_info "Please run the installation script first:"
    log_info "curl -fsSL https://cdn.deployflow.io/install.sh | sudo bash"
    exit 1
fi

if [ ! -f "$DEPLOYFLOW_DIR/docker-compose.yml" ]; then
    log_error "DeployFlow.io installation appears to be corrupted"
    log_info "Please reinstall DeployFlow.io"
    exit 1
fi

log_success "DeployFlow.io installation found"

# Backup current installation
log_info "Creating backup..."
BACKUP_DIR="/opt/deployflow-backup-$(date +%Y%m%d-%H%M%S)"
sudo mkdir -p "$BACKUP_DIR"

# Backup data and configuration
sudo cp -r "$DEPLOYFLOW_DIR/data" "$BACKUP_DIR/" 2>/dev/null || true
sudo cp -r "$DEPLOYFLOW_DIR/config" "$BACKUP_DIR/" 2>/dev/null || true
sudo cp "$DEPLOYFLOW_DIR/.env" "$BACKUP_DIR/" 2>/dev/null || true
sudo cp "$DEPLOYFLOW_DIR/docker-compose.yml" "$BACKUP_DIR/" 2>/dev/null || true

log_success "Backup created at: $BACKUP_DIR"

# Stop DeployFlow.io services
log_info "Stopping DeployFlow.io services..."
sudo systemctl stop deployflow.service || true
sleep 10
log_success "Services stopped"

# Pull latest images
log_info "Pulling latest Docker images..."
cd "$DEPLOYFLOW_DIR"
sudo -u "$DEPLOYFLOW_USER" docker-compose pull
log_success "Latest images pulled"

# Run database migrations
log_info "Running database migrations..."
cd "$DEPLOYFLOW_DIR"
sudo -u "$DEPLOYFLOW_USER" docker-compose exec -T deployflow-app php artisan migrate --force || true
log_success "Database migrations completed"

# Clear application cache
log_info "Clearing application cache..."
cd "$DEPLOYFLOW_DIR"
sudo -u "$DEPLOYFLOW_USER" docker-compose exec -T deployflow-app php artisan cache:clear || true
sudo -u "$DEPLOYFLOW_USER" docker-compose exec -T deployflow-app php artisan config:clear || true
sudo -u "$DEPLOYFLOW_USER" docker-compose exec -T deployflow-app php artisan route:clear || true
sudo -u "$DEPLOYFLOW_USER" docker-compose exec -T deployflow-app php artisan view:clear || true
log_success "Cache cleared"

# Start DeployFlow.io services
log_info "Starting DeployFlow.io services..."
sudo systemctl start deployflow.service
sleep 30

# Check if services are running
if sudo systemctl is-active --quiet deployflow.service; then
    log_success "DeployFlow.io upgraded successfully"
else
    log_error "Failed to start DeployFlow.io after upgrade"
    sudo systemctl status deployflow.service
    exit 1
fi

# Display upgrade summary
log_success "DeployFlow.io upgrade completed!"
echo ""
echo "=========================================="
echo "DeployFlow.io Upgrade Summary"
echo "=========================================="
echo ""
echo "🌐 Access URL: http://localhost:8000"
echo "📁 Installation Directory: $DEPLOYFLOW_DIR"
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
echo "  docker-compose -f $DEPLOYFLOW_DIR/docker-compose.yml logs -f"
echo ""
echo "🎉 DeployFlow.io has been upgraded!"
echo ""`,

      '/test.txt': `Hello from DeployFlow.io CDN!
This is a test file to verify the Worker is working.`
    };
    
    // Serve files with proper headers
    if (files[path]) {
      return new Response(files[path], {
        headers: {
          'Content-Type': 'text/plain',
          'Content-Disposition': `attachment; filename="${path.substring(1)}"`,
          ...corsHeaders
        }
      });
    }
    
    // Return 404 for unknown files
    return new Response('File not found', { 
      status: 404,
      headers: corsHeaders
    });
  }
}
