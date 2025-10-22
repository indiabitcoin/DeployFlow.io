#!/bin/bash
# DeployFlow.io Cloudflare CDN Upload Script
# Based on official Cloudflare documentation

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

# Function to show Cloudflare Workers instructions
show_workers_instructions() {
    print_step "Cloudflare Workers Setup (Recommended)"
    echo ""
    echo -e "${CYAN}1. Go to Cloudflare Dashboard:${NC}"
    echo "   https://dash.cloudflare.com"
    echo ""
    echo -e "${CYAN}2. Create Worker:${NC}"
    echo "   • Workers & Pages → Workers"
    echo "   • Create application → Worker"
    echo "   • Name: deployflow-cdn"
    echo ""
    echo -e "${CYAN}3. Deploy Worker Code:${NC}"
    echo "   • Copy code from cloudflare-worker.js"
    echo "   • Paste into Worker editor"
    echo "   • Click Deploy"
    echo ""
    echo -e "${CYAN}4. Configure Custom Domain:${NC}"
    echo "   • Settings → Triggers"
    echo "   • Add custom domain: cdn.deployflow.io"
    echo "   • SSL will be automatic"
    echo ""
}

# Function to show Pages instructions
show_pages_instructions() {
    print_step "Cloudflare Pages Setup (Alternative)"
    echo ""
    echo -e "${CYAN}1. Create Pages Project:${NC}"
    echo "   • Workers & Pages → Pages"
    echo "   • Create a project → Upload assets"
    echo ""
    echo -e "${CYAN}2. Upload Files:${NC}"
    echo "   • Drag and drop these files:"
    echo "     - install.sh"
    echo "     - upgrade.sh"
    echo "     - docker-compose.prod.yml"
    echo "     - env.production.template"
    echo ""
    echo -e "${CYAN}3. Deploy and Configure:${NC}"
    echo "   • Project name: deployflow-cdn"
    echo "   • Click Deploy"
    echo "   • Add custom domain: cdn.deployflow.io"
    echo ""
}

# Function to show DNS setup
show_dns_setup() {
    print_step "DNS Configuration"
    echo ""
    echo -e "${CYAN}Add DNS Record:${NC}"
    echo "Type: CNAME"
    echo "Name: cdn"
    echo "Target: deployflow.io (or your domain)"
    echo "Proxy: Proxied (orange cloud) ✅"
    echo "TTL: Auto"
    echo ""
    echo -e "${YELLOW}Note: Replace 'deployflow.io' with your actual domain${NC}"
    echo ""
}

# Function to test CDN
test_cdn() {
    print_step "Testing CDN (run after upload)"
    echo ""
    echo -e "${YELLOW}Test file access:${NC}"
    echo "curl -I https://cdn.deployflow.io/install.sh"
    echo "curl -I https://cdn.deployflow.io/upgrade.sh"
    echo "curl -I https://cdn.deployflow.io/docker-compose.prod.yml"
    echo "curl -I https://cdn.deployflow.io/env.production.template"
    echo ""
    echo -e "${YELLOW}Test installation:${NC}"
    echo "curl -fsSL https://cdn.deployflow.io/install.sh | sudo bash"
    echo ""
    echo -e "${YELLOW}Expected response:${NC}"
    echo "HTTP/2 200"
    echo "content-type: text/plain"
    echo "cache-control: public, max-age=3600, s-maxage=86400"
    echo "access-control-allow-origin: *"
    echo ""
}

# Function to show benefits
show_benefits() {
    print_step "Cloudflare CDN Benefits"
    echo ""
    echo -e "${GREEN}Performance:${NC}"
    echo "  ⚡ 200+ global locations"
    echo "  ⚡ HTTP/3 support"
    echo "  ⚡ Automatic compression"
    echo "  ⚡ Edge caching"
    echo ""
    echo -e "${GREEN}Reliability:${NC}"
    echo "  🛡️ 99.9% uptime SLA"
    echo "  🛡️ DDoS protection"
    echo "  🛡️ Automatic failover"
    echo "  🛡️ Global load balancing"
    echo ""
    echo -e "${GREEN}Security:${NC}"
    echo "  🔒 Automatic SSL/TLS"
    echo "  🔒 Web Application Firewall"
    echo "  🔒 Bot protection"
    echo "  🔒 Rate limiting"
    echo ""
    echo -e "${GREEN}Cost:${NC}"
    echo "  💰 Free tier includes everything"
    echo "  💰 No bandwidth limits"
    echo "  💰 No setup fees"
    echo "  💰 Professional features"
    echo ""
}

# Function to show completion message
show_completion() {
    print_success "Cloudflare CDN setup guide completed!"
    echo ""
    echo -e "${GREEN}╔══════════════════════════════════════════════════════════════╗${NC}"
    echo -e "${GREEN}║                    🚀 READY FOR CLOUDFLARE! 🚀                ║${NC}"
    echo -e "${GREEN}╚══════════════════════════════════════════════════════════════╝${NC}"
    echo ""
    echo -e "${CYAN}Next steps:${NC}"
    echo -e "${YELLOW}  1. Follow the Cloudflare Workers instructions above${NC}"
    echo -e "${YELLOW}  2. Use the provided Worker code (cloudflare-worker.js)${NC}"
    echo -e "${YELLOW}  3. Configure your DNS record${NC}"
    echo -e "${YELLOW}  4. Test your CDN with the provided commands${NC}"
    echo -e "${YELLOW}  5. Share DeployFlow.io with the world!${NC}"
    echo ""
    echo -e "${PURPLE}Files ready for upload:${NC}"
    ls -1 *.sh *.yml *.template | sed 's/^/  • /'
    echo ""
    echo -e "${CYAN}Worker code:${NC}"
    echo "  • cloudflare-worker.js (includes all files)"
    echo ""
}

# Main function
main() {
    show_logo
    print_status "DeployFlow.io Cloudflare CDN Upload Guide"
    echo ""
    
    check_files
    show_file_sizes
    show_workers_instructions
    show_pages_instructions
    show_dns_setup
    test_cdn
    show_benefits
    show_completion
}

# Run main function
main "$@"
