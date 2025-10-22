#!/bin/bash
# DeployFlow.io Complete CDN Upload Helper
# This script helps you upload all files from cdn-upload folder to Cloudflare

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
    print_step "Checking all files in cdn-upload folder..."
    
    local files=("install.sh" "upgrade.sh" "docker-compose.prod.yml" "env.production.template" "cloudflare-worker.js")
    local missing_files=()
    
    for file in "${files[@]}"; do
        if [ ! -f "$file" ]; then
            missing_files+=("$file")
        fi
    done
    
    if [ ${#missing_files[@]} -gt 0 ]; then
        print_error "Missing files: ${missing_files[*]}"
        print_error "Please run this script from the cdn-upload directory"
        exit 1
    fi
    
    print_success "All required files found"
}

# Function to show file summary
show_file_summary() {
    print_step "File Summary:"
    echo ""
    echo -e "${CYAN}Core Installation Files:${NC}"
    echo "  • install.sh ($(wc -c < install.sh | awk '{print int($1/1024)"KB"}')) - Main installer"
    echo "  • upgrade.sh ($(wc -c < upgrade.sh | awk '{print int($1/1024)"KB"}')) - Upgrade script"
    echo "  • docker-compose.prod.yml ($(wc -c < docker-compose.prod.yml | awk '{print int($1/1024)"KB"}')) - Docker config"
    echo "  • env.production.template ($(wc -c < env.production.template | awk '{print int($1/1024)"KB"}')) - Environment template"
    echo ""
    echo -e "${CYAN}Cloudflare Setup Files:${NC}"
    echo "  • cloudflare-worker.js ($(wc -c < cloudflare-worker.js | awk '{print int($1/1024)"KB"}')) - Complete Worker code"
    echo "  • CLOUDFLARE_SETUP_GUIDE.md ($(wc -c < CLOUDFLARE_SETUP_GUIDE.md | awk '{print int($1/1024)"KB"}')) - Setup guide"
    echo ""
    echo -e "${CYAN}Documentation Files:${NC}"
    echo "  • README.md ($(wc -c < README.md | awk '{print int($1/1024)"KB"}')) - Complete docs"
    echo "  • COMPLETE_UPLOAD_GUIDE.md ($(wc -c < COMPLETE_UPLOAD_GUIDE.md | awk '{print int($1/1024)"KB"}')) - Upload guide"
    echo ""
    
    local total_size=$(du -sh . | cut -f1)
    print_status "Total package size: $total_size"
}

# Function to show Cloudflare Workers instructions
show_workers_instructions() {
    print_step "🚀 Cloudflare Workers Upload (Recommended)"
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
    echo "   • Copy ALL code from cloudflare-worker.js"
    echo "   • Paste into Worker editor (replace existing code)"
    echo "   • Click Deploy"
    echo ""
    echo -e "${CYAN}4. Configure Custom Domain:${NC}"
    echo "   • Settings → Triggers"
    echo "   • Add custom domain: cdn.deployflow.io"
    echo "   • SSL will be automatic"
    echo ""
    echo -e "${CYAN}5. Set Up DNS:${NC}"
    echo "   • DNS → Add record"
    echo "   • Type: CNAME, Name: cdn, Target: deployflow.io"
    echo "   • Proxy: Proxied (orange cloud) ✅"
    echo ""
}

# Function to show Pages instructions
show_pages_instructions() {
    print_step "🌐 Cloudflare Pages Upload (Alternative)"
    echo ""
    echo -e "${CYAN}1. Create Pages Project:${NC}"
    echo "   • Workers & Pages → Pages"
    echo "   • Create a project → Upload assets"
    echo ""
    echo -e "${CYAN}2. Upload Core Files:${NC}"
    echo "   • Drag and drop these 4 files:"
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

# Function to show testing commands
show_testing_commands() {
    print_step "🧪 Testing Your CDN"
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
    print_step "🎯 Why Cloudflare CDN?"
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
    echo ""
    echo -e "${GREEN}Security:${NC}"
    echo "  🔒 Automatic SSL/TLS"
    echo "  🔒 Web Application Firewall"
    echo "  🔒 Bot protection"
    echo ""
    echo -e "${GREEN}Cost:${NC}"
    echo "  💰 Free tier includes everything"
    echo "  💰 No bandwidth limits"
    echo "  💰 No setup fees"
    echo ""
}

# Function to show completion message
show_completion() {
    print_success "Complete CDN upload guide ready!"
    echo ""
    echo -e "${GREEN}╔══════════════════════════════════════════════════════════════╗${NC}"
    echo -e "${GREEN}║                    🚀 READY FOR UPLOAD! 🚀                    ║${NC}"
    echo -e "${GREEN}╚══════════════════════════════════════════════════════════════╝${NC}"
    echo ""
    echo -e "${CYAN}Next steps:${NC}"
    echo -e "${YELLOW}  1. Choose Cloudflare Workers (recommended) or Pages${NC}"
    echo -e "${YELLOW}  2. Follow the instructions above${NC}"
    echo -e "${YELLOW}  3. Use cloudflare-worker.js for Workers method${NC}"
    echo -e "${YELLOW}  4. Upload 4 core files for Pages method${NC}"
    echo -e "${YELLOW}  5. Configure custom domain: cdn.deployflow.io${NC}"
    echo -e "${YELLOW}  6. Test with the provided commands${NC}"
    echo ""
    echo -e "${PURPLE}Files ready for upload:${NC}"
    ls -1 *.sh *.yml *.template *.js *.md | sed 's/^/  • /'
    echo ""
    echo -e "${CYAN}Quick start:${NC}"
    echo "  • Workers: Copy cloudflare-worker.js → Deploy → Add domain"
    echo "  • Pages: Upload 4 core files → Deploy → Add domain"
    echo ""
}

# Main function
main() {
    show_logo
    print_status "DeployFlow.io Complete CDN Upload Helper"
    echo ""
    
    check_files
    show_file_summary
    show_workers_instructions
    show_pages_instructions
    show_testing_commands
    show_benefits
    show_completion
}

# Run main function
main "$@"
