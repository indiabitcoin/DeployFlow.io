# Complete Cloudflare CDN Upload Guide for DeployFlow.io

## 📁 **Files to Upload from cdn-upload Folder**

### **Core Installation Files (Required):**
- **`install.sh`** (12.5KB) - Main installer script
- **`upgrade.sh`** (5.4KB) - Upgrade script  
- **`docker-compose.prod.yml`** (4.4KB) - Production Docker config
- **`env.production.template`** (1.8KB) - Environment template

### **Cloudflare Setup Files (Recommended):**
- **`cloudflare-worker.js`** (25KB) - Complete Worker code with all files embedded
- **`CLOUDFLARE_SETUP_GUIDE.md`** (6KB) - Detailed setup guide
- **`cloudflare-upload-guide.sh`** (8.5KB) - Interactive upload helper

### **Documentation Files (Optional):**
- **`README.md`** (28KB) - Complete documentation
- **`upload-helper.sh`** (6.3KB) - Alternative helper script

**Total size: ~112KB**

## 🚀 **Method 1: Cloudflare Workers (Recommended)**

This is the **easiest and most powerful** method. The `cloudflare-worker.js` file contains everything you need!

### **Step 1: Go to Cloudflare Dashboard**
1. Open [dash.cloudflare.com](https://dash.cloudflare.com)
2. Sign in to your account
3. Add your domain (`deployflow.io`) if not already added

### **Step 2: Create Worker**
1. Click **"Workers & Pages"** in the left sidebar
2. Click **"Workers"**
3. Click **"Create application"**
4. Choose **"Worker"**
5. Name: `deployflow-cdn`
6. Click **"Create"**

### **Step 3: Deploy Worker Code**
1. In the Worker editor, **delete all existing code**
2. Open `cloudflare-worker.js` from your `cdn-upload` folder
3. **Copy all the code** from the file
4. **Paste it** into the Worker editor
5. Click **"Deploy"**

### **Step 4: Configure Custom Domain**
1. In your Worker dashboard, click **"Settings"**
2. Click **"Triggers"**
3. Click **"Add Custom Domain"**
4. Enter: `cdn.deployflow.io`
5. Click **"Add"**
6. Cloudflare will automatically configure SSL

### **Step 5: Set Up DNS**
1. Go to **"DNS"** in your Cloudflare dashboard
2. Click **"Add record"**
3. Configure:
   ```
   Type: CNAME
   Name: cdn
   Target: deployflow.io (or your domain)
   Proxy: Proxied (orange cloud) ✅
   TTL: Auto
   ```
4. Click **"Save"**

## 🌐 **Method 2: Cloudflare Pages (Alternative)**

If you prefer to upload individual files:

### **Step 1: Create Pages Project**
1. Go to **"Workers & Pages"** → **"Pages"**
2. Click **"Create a project"**
3. Choose **"Upload assets"**

### **Step 2: Upload Files**
1. **Drag and drop** these 4 core files:
   - `install.sh`
   - `upgrade.sh`
   - `docker-compose.prod.yml`
   - `env.production.template`

2. **Project name:** `deployflow-cdn`
3. Click **"Deploy"**

### **Step 3: Configure Custom Domain**
1. Go to **"Custom domains"**
2. Add domain: `cdn.deployflow.io`
3. SSL will be automatic

## 🧪 **Test Your CDN**

After upload, test with these commands:

### **Test File Access:**
```bash
# Test individual files
curl -I https://cdn.deployflow.io/install.sh
curl -I https://cdn.deployflow.io/upgrade.sh
curl -I https://cdn.deployflow.io/docker-compose.prod.yml
curl -I https://cdn.deployflow.io/env.production.template
```

### **Expected Response:**
```bash
HTTP/2 200
content-type: text/plain
cache-control: public, max-age=3600, s-maxage=86400
access-control-allow-origin: *
content-length: 12476
```

### **Test Installation:**
```bash
# Test full installation
curl -fsSL https://cdn.deployflow.io/install.sh | sudo bash
```

## 🎯 **What Happens After Upload**

Once uploaded, users can install DeployFlow.io with:

```bash
curl -fsSL https://cdn.deployflow.io/install.sh | sudo bash
```

This will:
- ✅ Download from Cloudflare's global CDN (200+ locations)
- ✅ Install Docker and dependencies
- ✅ Set up DeployFlow.io containers
- ✅ Configure secure environment
- ✅ Start DeployFlow.io on port 8000

## 📊 **Cloudflare Benefits**

### **Performance:**
- ⚡ **200+ global locations** for fast downloads
- ⚡ **HTTP/3 support** for modern browsers
- ⚡ **Automatic compression** (Brotli, Gzip)
- ⚡ **Edge caching** for instant delivery

### **Reliability:**
- 🛡️ **99.9% uptime SLA** guaranteed
- 🛡️ **DDoS protection** against attacks
- 🛡️ **Automatic failover** for high availability

### **Security:**
- 🔒 **Automatic SSL/TLS** certificates
- 🔒 **Web Application Firewall** (WAF)
- 🔒 **Bot protection** and rate limiting

## 🚀 **Quick Start Commands**

### **For Cloudflare Workers:**
1. Copy code from `cloudflare-worker.js`
2. Paste into Worker editor
3. Deploy
4. Add custom domain: `cdn.deployflow.io`

### **For Cloudflare Pages:**
1. Upload 4 core files
2. Deploy project
3. Add custom domain: `cdn.deployflow.io`

## 🎉 **Success!**

After upload, DeployFlow.io will be available at:
- **CDN URL:** `https://cdn.deployflow.io/`
- **Installation:** `curl -fsSL https://cdn.deployflow.io/install.sh | sudo bash`

**Your DeployFlow.io CDN will be as fast and reliable as Coolify's!** 🚀
