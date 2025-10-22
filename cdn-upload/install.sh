#!/bin/bash
# DeployFlow.io Single-Command Installer
# Based on Coolify's proven installation approach
# Usage: curl -fsSL https://cdn.deployflow.io/install.sh | sudo bash

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
PURPLE='\033[0;35m'
CYAN='\033[0;36m'
NC='\033[0m' # No Color

# DeployFlow.io branding
DEPLOYFLOW_LOGO="
╔══════════════════════════════════════════════════════════════╗
║                                                              ║
║    ██████╗ ███████╗██████╗ ██╗     ██╗      ██████╗ ██╗    ██╗ ║
║    ██╔══██╗██╔════╝██╔══██╗██║     ██║     ██╔═══██╗██║    ██║ ║
║    ██║  ██║█████╗  ██████╔╝██║     ██║     ██║   ██║██║ █╗ ██║ ║
║    ██║  ██║██╔══╝  ██╔═══╝ ██║     ██║     ██║   ██║██║███╗██║ ║
║    ██████╔╝███████╗██║     ███████╗███████╗╚██████╔╝╚███╔███╔╝ ║
║    ╚═════╝ ╚══════╝╚═╝     ╚══════╝╚══════╝ ╚═════╝  ╚══╝╚══╝  ║
║                                                              ║
║                    Where Deployments Flow Smoothly           ║
║                                                              ║
╚══════════════════════════════════════════════════════════════╝
"

# Function to print colored output
print_logo() {
    echo -e "${CYAN}${DEPLOYFLOW_LOGO}${NC}"
}

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

print_step() {
    echo -e "${PURPLE}[STEP]${NC} $1"
}

# Function to check if command exists
command_exists() {
    command -v "$1" >/dev/null 2>&1
}

# Function to detect OS
detect_os() {
    if [ -f /etc/os-release ]; then
        . /etc/os-release
        OS=$NAME
        VER=$VERSION_ID
    elif type lsb_release >/dev/null 2>&1; then
        OS=$(lsb_release -si)
        VER=$(lsb_release -sr)
    elif [ -f /etc/lsb-release ]; then
        . /etc/lsb-release
        OS=$DISTRIB_ID
        VER=$DISTRIB_RELEASE
    elif [ -f /etc/debian_version ]; then
        OS=Debian
        VER=$(cat /etc/debian_version)
    elif [ -f /etc/SuSe-release ]; then
        OS=SuSE
    elif [ -f /etc/redhat-release ]; then
        OS=RedHat
    else
        OS=$(uname -s)
        VER=$(uname -r)
    fi
    
    print_status "Detected OS: $OS $VER"
}

# Function to check system requirements
check_requirements() {
    print_step "Checking system requirements..."
    
    # Check if running as root
    if [ "$EUID" -ne 0 ]; then
        print_error "Please run as root or with sudo"
        exit 1
    fi
    
    # Check architecture
    ARCH=$(uname -m)
    if [[ "$ARCH" != "x86_64" && "$ARCH" != "aarch64" ]]; then
        print_error "Unsupported architecture: $ARCH. Only x86_64 and aarch64 are supported."
        exit 1
    fi
    
    # Check memory
    MEMORY=$(free -m | awk 'NR==2{printf "%.0f", $2}')
    if [ "$MEMORY" -lt 2048 ]; then
        print_warning "Low memory detected: ${MEMORY}MB. Minimum 2GB recommended."
    fi
    
    # Check disk space
    DISK_SPACE=$(df / | awk 'NR==2{printf "%.0f", $4}')
    if [ "$DISK_SPACE" -lt 30720 ]; then
        print_warning "Low disk space detected: ${DISK_SPACE}MB. Minimum 30GB recommended."
    fi
    
    print_success "System requirements check completed"
}

# Function to install essential tools
install_essentials() {
    print_step "Installing essential tools..."
    
    # Update package lists
    if command_exists apt-get; then
        apt-get update
        apt-get install -y curl wget git jq openssl ca-certificates gnupg lsb-release
    elif command_exists yum; then
        yum update -y
        yum install -y curl wget git jq openssl ca-certificates gnupg
    elif command_exists dnf; then
        dnf update -y
        dnf install -y curl wget git jq openssl ca-certificates gnupg
    elif command_exists zypper; then
        zypper refresh
        zypper install -y curl wget git jq openssl ca-certificates gnupg
    elif command_exists pacman; then
        pacman -Syu --noconfirm curl wget git jq openssl ca-certificates gnupg
    elif command_exists apk; then
        apk update
        apk add curl wget git jq openssl ca-certificates gnupg
    else
        print_error "Unsupported package manager"
        exit 1
    fi
    
    print_success "Essential tools installed"
}

# Function to install Docker
install_docker() {
    print_step "Installing Docker Engine..."
    
    if command_exists docker; then
        print_warning "Docker already installed"
        return
    fi
    
    # Remove old Docker versions
    if command_exists apt-get; then
        apt-get remove -y docker docker-engine docker.io containerd runc || true
    fi
    
    # Install Docker using official script
    curl -fsSL https://get.docker.com -o get-docker.sh
    sh get-docker.sh
    rm get-docker.sh
    
    # Start and enable Docker
    systemctl start docker
    systemctl enable docker
    
    # Configure Docker
    mkdir -p /etc/docker
    cat > /etc/docker/daemon.json << EOF
{
    "log-driver": "json-file",
    "log-opts": {
        "max-size": "10m",
        "max-file": "3"
    },
    "live-restore": true,
    "userland-proxy": false,
    "experimental": false,
    "metrics-addr": "0.0.0.0:9323",
    "default-address-pools": [
        {
            "base": "172.17.0.0/12",
            "size": 24
        }
    ]
}
EOF
    
    systemctl restart docker
    
    print_success "Docker Engine installed and configured"
}

# Function to create DeployFlow.io directories
create_directories() {
    print_step "Creating DeployFlow.io directories..."
    
    mkdir -p /data/deployflow/{source,ssh,applications,databases,backups,services,proxy,webhooks-during-maintenance}
    mkdir -p /data/deployflow/ssh/{keys,mux}
    mkdir -p /data/deployflow/proxy/dynamic
    
    print_success "Directories created"
}

# Function to generate SSH key
generate_ssh_key() {
    print_step "Generating SSH key for DeployFlow.io..."
    
    ssh-keygen -f /data/deployflow/ssh/keys/deployflow@localhost -t ed25519 -N '' -C deployflow@localhost
    
    # Add public key to authorized_keys
    cat /data/deployflow/ssh/keys/deployflow@localhost.pub >> ~/.ssh/authorized_keys
    chmod 600 ~/.ssh/authorized_keys
    
    print_success "SSH key generated"
}

# Function to download DeployFlow.io files
download_files() {
    print_step "Downloading DeployFlow.io configuration files..."
    
    # Download Docker Compose files
    curl -fsSL https://cdn.deployflow.io/docker-compose.yml -o /data/deployflow/source/docker-compose.yml
    curl -fsSL https://cdn.deployflow.io/docker-compose.prod.yml -o /data/deployflow/source/docker-compose.prod.yml
    curl -fsSL https://cdn.deployflow.io/.env.production -o /data/deployflow/source/.env
    curl -fsSL https://cdn.deployflow.io/upgrade.sh -o /data/deployflow/source/upgrade.sh
    
    # Make upgrade script executable
    chmod +x /data/deployflow/source/upgrade.sh
    
    print_success "Configuration files downloaded"
}

# Function to set permissions
set_permissions() {
    print_step "Setting permissions..."
    
    chown -R 9999:root /data/deployflow
    chmod -R 700 /data/deployflow
    
    print_success "Permissions set"
}

# Function to generate secure values
generate_values() {
    print_step "Generating secure random values..."
    
    # Generate secure random values
    APP_ID=$(openssl rand -hex 16)
    APP_KEY="base64:$(openssl rand -base64 32)"
    DB_PASSWORD=$(openssl rand -base64 32)
    REDIS_PASSWORD=$(openssl rand -base64 32)
    PUSHER_APP_ID=$(openssl rand -hex 32)
    PUSHER_APP_KEY=$(openssl rand -hex 32)
    PUSHER_APP_SECRET=$(openssl rand -hex 32)
    
    # Update .env file
    sed -i "s|APP_ID=.*|APP_ID=$APP_ID|g" /data/deployflow/source/.env
    sed -i "s|APP_KEY=.*|APP_KEY=$APP_KEY|g" /data/deployflow/source/.env
    sed -i "s|DB_PASSWORD=.*|DB_PASSWORD=$DB_PASSWORD|g" /data/deployflow/source/.env
    sed -i "s|REDIS_PASSWORD=.*|REDIS_PASSWORD=$REDIS_PASSWORD|g" /data/deployflow/source/.env
    sed -i "s|PUSHER_APP_ID=.*|PUSHER_APP_ID=$PUSHER_APP_ID|g" /data/deployflow/source/.env
    sed -i "s|PUSHER_APP_KEY=.*|PUSHER_APP_KEY=$PUSHER_APP_KEY|g" /data/deployflow/source/.env
    sed -i "s|PUSHER_APP_SECRET=.*|PUSHER_APP_SECRET=$PUSHER_APP_SECRET|g" /data/deployflow/source/.env
    
    print_success "Secure values generated"
}

# Function to create Docker network
create_docker_network() {
    print_step "Creating Docker network..."
    
    docker network create --attachable deployflow || true
    
    print_success "Docker network created"
}

# Function to start DeployFlow.io
start_deployflow() {
    print_step "Starting DeployFlow.io..."
    
    cd /data/deployflow/source
    
    # Start DeployFlow.io
    docker compose --env-file .env -f docker-compose.yml -f docker-compose.prod.yml up -d --pull always --remove-orphans --force-recreate
    
    print_success "DeployFlow.io started"
}

# Function to show completion message
show_completion() {
    print_success "DeployFlow.io installation completed successfully!"
    
    # Get server IP
    SERVER_IP=$(curl -s ifconfig.me || curl -s ipinfo.io/ip || hostname -I | awk '{print $1}')
    
    echo ""
    echo -e "${GREEN}╔══════════════════════════════════════════════════════════════╗${NC}"
    echo -e "${GREEN}║                    🎉 INSTALLATION COMPLETE! 🎉              ║${NC}"
    echo -e "${GREEN}╚══════════════════════════════════════════════════════════════╝${NC}"
    echo ""
    echo -e "${CYAN}DeployFlow.io is now running on:${NC}"
    echo -e "${YELLOW}  🌐 http://$SERVER_IP:8000${NC}"
    echo ""
    echo -e "${CYAN}Next steps:${NC}"
    echo -e "${YELLOW}  1. Visit the URL above to access DeployFlow.io${NC}"
    echo -e "${YELLOW}  2. Create your admin account${NC}"
    echo -e "${YELLOW}  3. Start building your deployment flows!${NC}"
    echo ""
    echo -e "${PURPLE}Important:${NC}"
    echo -e "${RED}  ⚠️  Create your admin account immediately!${NC}"
    echo -e "${RED}  ⚠️  Anyone who accesses the registration page first becomes admin${NC}"
    echo ""
    echo -e "${CYAN}Useful commands:${NC}"
    echo -e "${YELLOW}  • View logs: docker logs deployflow-app${NC}"
    echo -e "${YELLOW}  • Restart: docker compose -f /data/deployflow/source/docker-compose.yml restart${NC}"
    echo -e "${YELLOW}  • Upgrade: /data/deployflow/source/upgrade.sh${NC}"
    echo ""
}

# Function to handle errors
handle_error() {
    print_error "Installation failed at step: $1"
    print_error "Please check the logs above for details"
    print_error "For support, visit: https://github.com/yourusername/DeployFlow.io/issues"
    exit 1
}

# Main installation function
main() {
    # Set error handling
    trap 'handle_error "Unknown"' ERR
    
    # Show logo
    print_logo
    
    print_status "Starting DeployFlow.io installation..."
    print_status "This may take a few minutes depending on your internet connection"
    echo ""
    
    # Run installation steps
    detect_os
    check_requirements
    install_essentials
    install_docker
    create_directories
    generate_ssh_key
    download_files
    set_permissions
    generate_values
    create_docker_network
    start_deployflow
    
    # Show completion message
    show_completion
}

# Run main function
main "$@"
