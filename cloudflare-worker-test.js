// Simple DeployFlow.io Test Worker
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
    
    // Simple file mappings
    const files = {
      '/install.sh': `#!/bin/bash
# DeployFlow.io Test Installer
echo "=========================================="
echo "DeployFlow.io Installation Script"
echo "=========================================="
echo ""
echo "🎉 DeployFlow.io Worker is working!"
echo "📦 This is a test installation script"
echo "🚀 Full installer will be available soon"
echo ""
echo "Next steps:"
echo "1. Update Worker with full installation code"
echo "2. Test full installation process"
echo "3. Deploy DeployFlow.io to production"
echo ""
echo "✅ Test completed successfully!"
`,

      '/upgrade.sh': `#!/bin/bash
# DeployFlow.io Test Upgrader
echo "DeployFlow.io Upgrade Script - Test Version"
echo "This is a test upgrade script"
`,

      '/docker-compose.prod.yml': `version: '3.8'
services:
  test:
    image: hello-world
`,

      '/env.production.template': `APP_NAME="DeployFlow.io Test"
APP_ENV=production
APP_KEY=test-key
`
    };
    
    // Serve files
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
