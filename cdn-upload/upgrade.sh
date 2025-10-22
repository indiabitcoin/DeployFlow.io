#!/bin/bash
# DeployFlow.io Upgrade Script
# Usage: ./upgrade.sh

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

# Function to check if running as root
check_root() {
    if [ "$EUID" -ne 0 ]; then
        print_error "Please run as root or with sudo"
        exit 1
    fi
}

# Function to backup current installation
backup_installation() {
    print_status "Creating backup..."
    
    BACKUP_DIR="/data/deployflow/backups/$(date +%Y%m%d_%H%M%S)"
    mkdir -p "$BACKUP_DIR"
    
    # Backup database
    docker exec deployflow-db pg_dump -U deployflow deployflow > "$BACKUP_DIR/database.sql"
    
    # Backup volumes
    cp -r /data/deployflow/applications "$BACKUP_DIR/" || true
    cp -r /data/deployflow/databases "$BACKUP_DIR/" || true
    cp -r /data/deployflow/services "$BACKUP_DIR/" || true
    cp -r /data/deployflow/ssh "$BACKUP_DIR/" || true
    
    print_success "Backup created at: $BACKUP_DIR"
}

# Function to download latest files
download_latest() {
    print_status "Downloading latest DeployFlow.io files..."
    
    cd /data/deployflow/source
    
    # Download latest Docker Compose files
    curl -fsSL https://cdn.deployflow.io/docker-compose.yml -o docker-compose.yml
    curl -fsSL https://cdn.deployflow.io/docker-compose.prod.yml -o docker-compose.prod.yml
    
    print_success "Latest files downloaded"
}

# Function to pull latest images
pull_images() {
    print_status "Pulling latest Docker images..."
    
    cd /data/deployflow/source
    
    docker compose --env-file .env -f docker-compose.yml -f docker-compose.prod.yml pull
    
    print_success "Latest images pulled"
}

# Function to upgrade DeployFlow.io
upgrade_deployflow() {
    print_status "Upgrading DeployFlow.io..."
    
    cd /data/deployflow/source
    
    # Stop current containers
    docker compose --env-file .env -f docker-compose.yml -f docker-compose.prod.yml down
    
    # Start with latest images
    docker compose --env-file .env -f docker-compose.yml -f docker-compose.prod.yml up -d --pull always --remove-orphans --force-recreate
    
    print_success "DeployFlow.io upgraded"
}

# Function to run migrations
run_migrations() {
    print_status "Running database migrations..."
    
    docker exec deployflow-app php artisan migrate --force
    
    print_success "Migrations completed"
}

# Function to clear caches
clear_caches() {
    print_status "Clearing application caches..."
    
    docker exec deployflow-app php artisan config:cache
    docker exec deployflow-app php artisan route:cache
    docker exec deployflow-app php artisan view:cache
    
    print_success "Caches cleared"
}

# Function to restart services
restart_services() {
    print_status "Restarting services..."
    
    docker restart deployflow-worker
    
    print_success "Services restarted"
}

# Function to verify installation
verify_installation() {
    print_status "Verifying installation..."
    
    # Check if containers are running
    if ! docker ps | grep -q deployflow-app; then
        print_error "DeployFlow.io app container is not running"
        return 1
    fi
    
    if ! docker ps | grep -q deployflow-db; then
        print_error "DeployFlow.io database container is not running"
        return 1
    fi
    
    if ! docker ps | grep -q deployflow-redis; then
        print_error "DeployFlow.io Redis container is not running"
        return 1
    fi
    
    # Check if application is responding
    sleep 10
    if ! curl -f http://localhost:8000 >/dev/null 2>&1; then
        print_warning "Application may not be fully ready yet"
    fi
    
    print_success "Installation verified"
}

# Function to show completion message
show_completion() {
    print_success "DeployFlow.io upgrade completed successfully!"
    
    echo ""
    echo -e "${GREEN}╔══════════════════════════════════════════════════════════════╗${NC}"
    echo -e "${GREEN}║                    🎉 UPGRADE COMPLETE! 🎉                  ║${NC}"
    echo -e "${GREEN}╚══════════════════════════════════════════════════════════════╝${NC}"
    echo ""
    echo -e "${CYAN}DeployFlow.io is now running the latest version!${NC}"
    echo ""
    echo -e "${CYAN}Useful commands:${NC}"
    echo -e "${YELLOW}  • View logs: docker logs deployflow-app${NC}"
    echo -e "${YELLOW}  • Check status: docker ps${NC}"
    echo -e "${YELLOW}  • Restart: docker compose -f /data/deployflow/source/docker-compose.yml restart${NC}"
    echo ""
}

# Main upgrade function
main() {
    print_status "Starting DeployFlow.io upgrade..."
    
    check_root
    backup_installation
    download_latest
    pull_images
    upgrade_deployflow
    run_migrations
    clear_caches
    restart_services
    verify_installation
    show_completion
}

# Run main function
main "$@"
