// Minimal DeployFlow.io Worker - Test Version
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
# DeployFlow.io Installation Script
echo "Installing DeployFlow.io..."
echo "This is a test version"
echo "Installation completed!"`,
      
      '/upgrade.sh': `#!/bin/bash
# DeployFlow.io Upgrade Script  
echo "Upgrading DeployFlow.io..."
echo "This is a test version"
echo "Upgrade completed!"`,
      
      '/test.txt': `Hello from DeployFlow.io CDN!
This is a test file to verify the Worker is working.`
    };
    
    // Serve files
    if (files[path]) {
      return new Response(files[path], {
        headers: {
          'Content-Type': 'text/plain',
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
