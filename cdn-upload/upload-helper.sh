#!/bin/bash
# DeployFlow.io CDN Upload Helper Script
# This script helps you upload files to Cloudflare CDN

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
PURPLE='\033[0;35m'
CYAN='\033[0;36m'
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

print_step() {
    echo -e "${PURPLE}[STEP]${NC} $1"
}

# Function to show DeployFlow.io logo
show_logo() {
    echo -e "${CYAN}"
    echo "╔══════════════════════════════════════════════════════════════╗"
    echo "║                                                              ║"
    echo "║    ██████╗ ███████╗██████╗ ██╗     ██╗      ██████╗ ██╗    ██╗ ║"
    echo "║    ██╔══██╗██╔════╝██╔══██╗██║     ██║     ██╔═══██╗██║    ██║ ║"
    echo "║    ██║  ██║█████╗  ██████╔╝██║     ██║     ██║   ██║██║ █╗ ██║ ║"
    echo "║    ██║  ██║██╔══╝  ██╔═══╝ ██║     ██║     ██║   ██║██║███╗██║ ║"
    echo "║    ██████╔╝███████╗██║     ███████╗███████╗╚██████╔╝╚███╔███╔╝ ║"
    echo "║    ╚═════╝ ╚══════╝╚═╝     ╚══════╝╚══════╝ ╚═════╝  ╚══╝╚══╝  ║"
    echo "║                                                              ║"
    echo "║                    Where Deployments Flow Smoothly           ║"
    echo "║                                                              ║"
    echo "╚══════════════════════════════════════════════════════════════╝"
    echo -e "${NC}"
}

# Function to check files
check_files() {
    print_step "Checking files for upload..."
    
    local files=("install.sh" "upgrade.sh" "docker-compose.prod.yml" "env.production.template")
    local missing_files=()
    
    for file in "${files[@]}"; do
        if [ ! -f "$file" ]; then
            missing_files+=("$file")
        fi
    done
    
    if [ ${#missing_files[@]} -gt 0 ]; then
        print_error "Missing files: ${missing_files[*]}"
        print_error "Please run this script from the directory containing the files"
        exit 1
    fi
    
    print_success "All required files found"
}

# Function to show file sizes
show_file_sizes() {
    print_step "File sizes:"
    echo ""
    ls -lh *.sh *.yml *.template | awk '{print "  " $5 " - " $9}'
    echo ""
    
    local total_size=$(du -sh . | cut -f1)
    print_status "Total size: $total_size"
}

# Function to show upload instructions
show_upload_instructions() {
    print_step "Cloudflare CDN Upload Instructions"
    echo ""
    echo -e "${CYAN}Method 1: Cloudflare Pages (Recommended)${NC}"
    echo "1. Go to https://dash.cloudflare.com"
    echo "2. Navigate to Workers & Pages → Pages"
    echo "3. Click 'Create a project' → 'Upload assets'"
    echo "4. Drag and drop these files:"
    echo "   • install.sh"
    echo "   • upgrade.sh" 
    echo "   • docker-compose.prod.yml"
    echo "   • env.production.template"
    echo "5. Deploy the project"
    echo "6. Add custom domain: cdn.deployflow.io"
    echo ""
    
    echo -e "${CYAN}Method 2: Cloudflare Workers${NC}"
    echo "1. Go to Workers & Pages → Workers"
    echo "2. Create new Worker"
    echo "3. Use the provided Worker code (see README.md)"
    echo "4. Deploy and configure custom domain"
    echo ""
    
    echo -e "${CYAN}Method 3: GitHub Pages (Alternative)${NC}"
    echo "1. Create new repository: deployflow-cdn"
    echo "2. Enable GitHub Pages"
    echo "3. Upload files to repository root"
    echo "4. Configure custom domain"
    echo ""
}

# Function to test CDN (after upload)
test_cdn() {
    print_step "Testing CDN (run this after upload)..."
    echo ""
    echo -e "${YELLOW}Test commands:${NC}"
    echo "curl -I https://cdn.deployflow.io/install.sh"
    echo "curl -I https://cdn.deployflow.io/upgrade.sh"
    echo "curl -I https://cdn.deployflow.io/docker-compose.prod.yml"
    echo "curl -I https://cdn.deployflow.io/env.production.template"
    echo ""
    echo -e "${YELLOW}Test installation:${NC}"
    echo "curl -fsSL https://cdn.deployflow.io/install.sh | sudo bash"
    echo ""
}

# Function to show completion message
show_completion() {
    print_success "CDN upload preparation completed!"
    echo ""
    echo -e "${GREEN}╔══════════════════════════════════════════════════════════════╗${NC}"
    echo -e "${GREEN}║                    🚀 READY FOR UPLOAD! 🚀                    ║${NC}"
    echo -e "${GREEN}╚══════════════════════════════════════════════════════════════╝${NC}"
    echo ""
    echo -e "${CYAN}Next steps:${NC}"
    echo -e "${YELLOW}  1. Follow the upload instructions above${NC}"
    echo -e "${YELLOW}  2. Test your CDN with the provided commands${NC}"
    echo -e "${YELLOW}  3. Share DeployFlow.io with the world!${NC}"
    echo ""
    echo -e "${PURPLE}Files ready for upload:${NC}"
    ls -1 *.sh *.yml *.template | sed 's/^/  • /'
    echo ""
}

# Main function
main() {
    show_logo
    print_status "DeployFlow.io CDN Upload Helper"
    echo ""
    
    check_files
    show_file_sizes
    show_upload_instructions
    test_cdn
    show_completion
}

# Run main function
main "$@"
