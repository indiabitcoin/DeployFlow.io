#!/bin/bash
# DeployFlow.io One-Command Deployment Script
# Usage: ./deploy.sh [platform] [environment]

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Default values
PLATFORM=${1:-"railway"}
ENVIRONMENT=${2:-"production"}
REPO_URL="https://github.com/yourusername/DeployFlow.io.git"

# Function to print colored output
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

# Function to deploy to Railway
deploy_railway() {
    print_status "Deploying to Railway..."
    
    if ! command_exists railway; then
        print_status "Installing Railway CLI..."
        npm install -g @railway/cli
    fi
    
    railway login
    railway link
    railway up --detach
    
    print_success "Deployed to Railway successfully!"
    print_status "Your app is available at: https://$(railway domain)"
}

# Function to deploy to Render
deploy_render() {
    print_status "Deploying to Render..."
    
    if ! command_exists render; then
        print_status "Installing Render CLI..."
        curl -fsSL https://cli.render.com/install.sh | sh
    fi
    
    render deploy
    
    print_success "Deployed to Render successfully!"
}

# Function to deploy to Fly.io
deploy_fly() {
    print_status "Deploying to Fly.io..."
    
    if ! command_exists flyctl; then
        print_status "Installing Fly CLI..."
        curl -L https://fly.io/install.sh | sh
    fi
    
    flyctl auth login
    flyctl deploy
    
    print_success "Deployed to Fly.io successfully!"
}

# Function to deploy to DigitalOcean
deploy_digitalocean() {
    print_status "Deploying to DigitalOcean App Platform..."
    
    if ! command_exists doctl; then
        print_status "Installing DigitalOcean CLI..."
        curl -sL https://github.com/digitalocean/doctl/releases/download/v1.94.0/doctl-1.94.0-linux-amd64.tar.gz | tar -xzv
        sudo mv doctl /usr/local/bin
    fi
    
    doctl auth init
    doctl apps create-deployment $APP_ID
    
    print_success "Deployed to DigitalOcean successfully!"
}

# Function to deploy to VPS
deploy_vps() {
    print_status "Deploying to VPS..."
    
    read -p "Enter VPS IP address: " VPS_IP
    read -p "Enter VPS username: " VPS_USER
    
    print_status "Connecting to VPS and deploying..."
    
    ssh $VPS_USER@$VPS_IP << EOF
        # Update system
        sudo apt update && sudo apt upgrade -y
        
        # Install dependencies
        sudo apt install -y git curl
        
        # Clone or update repository
        if [ -d "deployflow" ]; then
            cd deployflow
            git pull origin main
        else
            git clone $REPO_URL deployflow
            cd deployflow
        fi
        
        # Run deployment script
        chmod +x scripts/deploy-vps.sh
        ./scripts/deploy-vps.sh
        
        # Restart services
        sudo systemctl restart nginx
        sudo systemctl restart php8.4-fpm
EOF
    
    print_success "Deployed to VPS successfully!"
}

# Function to deploy to Docker
deploy_docker() {
    print_status "Deploying with Docker..."
    
    if ! command_exists docker; then
        print_error "Docker is not installed. Please install Docker first."
        exit 1
    fi
    
    if ! command_exists docker-compose; then
        print_error "Docker Compose is not installed. Please install Docker Compose first."
        exit 1
    fi
    
    # Build and deploy
    docker-compose -f docker-compose.deployflow.yml down
    docker-compose -f docker-compose.deployflow.yml build
    docker-compose -f docker-compose.deployflow.yml up -d
    
    print_success "Deployed with Docker successfully!"
}

# Function to deploy to Heroku
deploy_heroku() {
    print_status "Deploying to Heroku..."
    
    if ! command_exists heroku; then
        print_status "Installing Heroku CLI..."
        curl https://cli-assets.heroku.com/install.sh | sh
    fi
    
    heroku login
    heroku create deployflow-io-$RANDOM
    heroku addons:create heroku-postgresql:mini
    heroku addons:create heroku-redis:mini
    git push heroku main
    
    print_success "Deployed to Heroku successfully!"
}

# Main deployment function
deploy() {
    print_status "Starting DeployFlow.io deployment..."
    print_status "Platform: $PLATFORM"
    print_status "Environment: $ENVIRONMENT"
    
    case $PLATFORM in
        "railway")
            deploy_railway
            ;;
        "render")
            deploy_render
            ;;
        "fly")
            deploy_fly
            ;;
        "digitalocean")
            deploy_digitalocean
            ;;
        "vps")
            deploy_vps
            ;;
        "docker")
            deploy_docker
            ;;
        "heroku")
            deploy_heroku
            ;;
        *)
            print_error "Unknown platform: $PLATFORM"
            print_status "Available platforms: railway, render, fly, digitalocean, vps, docker, heroku"
            exit 1
            ;;
    esac
}

# Function to show help
show_help() {
    echo "DeployFlow.io One-Command Deployment Script"
    echo ""
    echo "Usage: $0 [platform] [environment]"
    echo ""
    echo "Platforms:"
    echo "  railway      - Deploy to Railway (default)"
    echo "  render       - Deploy to Render"
    echo "  fly          - Deploy to Fly.io"
    echo "  digitalocean - Deploy to DigitalOcean App Platform"
    echo "  vps          - Deploy to VPS/Server"
    echo "  docker       - Deploy with Docker"
    echo "  heroku       - Deploy to Heroku"
    echo ""
    echo "Environments:"
    echo "  production   - Production deployment (default)"
    echo "  staging      - Staging deployment"
    echo "  development  - Development deployment"
    echo ""
    echo "Examples:"
    echo "  $0                    # Deploy to Railway (production)"
    echo "  $0 render             # Deploy to Render (production)"
    echo "  $0 fly staging        # Deploy to Fly.io (staging)"
    echo "  $0 vps production     # Deploy to VPS (production)"
    echo ""
    echo "Prerequisites:"
    echo "  - Git repository with DeployFlow.io code"
    echo "  - Platform-specific CLI tools (installed automatically)"
    echo "  - Platform-specific API tokens/credentials"
}

# Check if help is requested
if [[ "$1" == "-h" || "$1" == "--help" ]]; then
    show_help
    exit 0
fi

# Run deployment
deploy

print_success "DeployFlow.io deployment completed!"
print_status "Check your platform dashboard for deployment status."
