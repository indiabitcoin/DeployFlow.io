// Ultra-Minimal DeployFlow.io Worker
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
    
    // Ultra-simple file mappings
    const files = {
      '/install.sh': `#!/bin/bash
echo "DeployFlow.io Installation Script"
echo "This is a minimal test version"
echo "Installation would start here..."`,
      
      '/test.txt': `Hello from DeployFlow.io CDN!
This is a test file.`
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
