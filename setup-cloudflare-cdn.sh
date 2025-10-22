#!/bin/bash
# DeployFlow.io CDN Upload Script for Cloudflare
# This script helps upload files to Cloudflare CDN

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

# Check if required tools are installed
check_dependencies() {
    print_status "Checking dependencies..."
    
    if ! command -v curl &> /dev/null; then
        print_error "curl is required but not installed"
        exit 1
    fi
    
    if ! command -v jq &> /dev/null; then
        print_warning "jq is recommended for JSON parsing"
        print_warning "Install with: brew install jq (macOS) or apt install jq (Ubuntu)"
    fi
    
    print_success "Dependencies check completed"
}

# Function to upload file to Cloudflare
upload_to_cloudflare() {
    local file_path="$1"
    local file_name="$2"
    local zone_id="$3"
    local api_token="$4"
    
    print_status "Uploading $file_name to Cloudflare CDN..."
    
    # Upload file using Cloudflare API
    response=$(curl -s -X POST \
        "https://api.cloudflare.com/client/v4/zones/$zone_id/purge_cache" \
        -H "Authorization: Bearer $api_token" \
        -H "Content-Type: application/json" \
        --data '{"purge_everything":true}')
    
    # For now, we'll use a simple approach
    print_warning "Manual upload required. Please follow the steps below."
}

# Main function
main() {
    print_status "DeployFlow.io CDN Setup Helper"
    echo ""
    
    check_dependencies
    
    echo -e "${YELLOW}Cloudflare CDN Setup Instructions:${NC}"
    echo ""
    echo "1. Go to https://dash.cloudflare.com"
    echo "2. Add your domain (e.g., deployflow.io)"
    echo "3. Add DNS record:"
    echo "   Type: CNAME"
    echo "   Name: cdn"
    echo "   Target: your-domain.com"
    echo "   Proxy: Enabled (orange cloud)"
    echo ""
    echo "4. Upload these files to Cloudflare:"
    echo "   - install.sh"
    echo "   - upgrade.sh"
    echo "   - docker-compose.prod.yml"
    echo "   - env.production.template"
    echo ""
    echo "5. Test installation:"
    echo "   curl -fsSL https://cdn.deployflow.io/install.sh | sudo bash"
    echo ""
    
    print_success "Setup instructions displayed"
}

# Run main function
main "$@"
