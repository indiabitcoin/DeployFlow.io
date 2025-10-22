# DeployFlow.io Installation Guide

## 🚀 **Quick Installation**

DeployFlow.io can be installed with a single command:

```bash
curl -fsSL https://raw.githubusercontent.com/indiabitcoin/DeployFlow.io/main/install.sh | sudo bash
```

## 📋 **What the Installation Script Does**

The installation script automatically:

1. **Installs Docker** - Detects your OS and installs Docker with Docker Compose
2. **Creates DeployFlow User** - Sets up a dedicated `deployflow` user for security
3. **Creates Directories** - Sets up `/opt/deployflow` with proper permissions
4. **Generates Secure Values** - Creates random passwords and keys
5. **Configures Environment** - Sets up production environment variables
6. **Creates Docker Compose** - Configures all services (app, database, Redis, Soketi)
7. **Sets up Systemd Service** - Enables automatic startup and management
8. **Starts DeployFlow.io** - Launches all services

## 🔧 **System Requirements**

- **OS**: Ubuntu 18.04+, Debian 9+, CentOS 7+, RHEL 7+
- **RAM**: Minimum 2GB, Recommended 4GB+
- **Disk**: Minimum 10GB free space
- **Network**: Internet access for Docker image downloads
- **User**: Non-root user with sudo privileges

## 📊 **After Installation**

Once installed, DeployFlow.io will be available at:
- **URL**: http://localhost:8000
- **Directory**: `/opt/deployflow`
- **User**: `deployflow`

### **Management Commands**

```bash
# Start DeployFlow.io
sudo systemctl start deployflow

# Stop DeployFlow.io
sudo systemctl stop deployflow

# Restart DeployFlow.io
sudo systemctl restart deployflow

# Check status
sudo systemctl status deployflow

# View logs
sudo journalctl -u deployflow -f

# View Docker logs
docker-compose -f /opt/deployflow/docker-compose.yml logs -f
```

## 🔄 **Upgrading DeployFlow.io**

To upgrade to the latest version:

```bash
curl -fsSL https://raw.githubusercontent.com/indiabitcoin/DeployFlow.io/main/upgrade.sh | sudo bash
```

## 🛠️ **Manual Installation**

If you prefer manual installation:

1. **Clone the repository**:
   ```bash
   git clone https://github.com/indiabitcoin/DeployFlow.io.git
   cd DeployFlow.io
   ```

2. **Run the installation script**:
   ```bash
   sudo bash install.sh
   ```

## 🔍 **Troubleshooting**

### **Common Issues**

1. **Permission Denied**: Make sure you're running as a non-root user with sudo privileges
2. **Docker Installation Failed**: Check your internet connection and try again
3. **Port Already in Use**: Make sure port 8000 is not already in use
4. **Service Won't Start**: Check logs with `sudo journalctl -u deployflow -f`

### **Logs and Debugging**

```bash
# Check systemd service logs
sudo journalctl -u deployflow -f

# Check Docker container logs
docker-compose -f /opt/deployflow/docker-compose.yml logs -f

# Check individual container logs
docker logs deployflow-app
docker logs deployflow-db
docker logs deployflow-redis
docker logs deployflow-soketi
```

## 🌐 **Accessing DeployFlow.io**

After successful installation:

1. **Open your browser** and go to `http://localhost:8000`
2. **Create your account** and set up your first project
3. **Start deploying** your applications!

## 📞 **Support**

- **GitHub Issues**: [Report bugs and request features](https://github.com/indiabitcoin/DeployFlow.io/issues)
- **Documentation**: [Full documentation](https://github.com/indiabitcoin/DeployFlow.io/wiki)
- **Community**: [Discussions](https://github.com/indiabitcoin/DeployFlow.io/discussions)

---

**DeployFlow.io** - Where Deployments Flow Smoothly! 🚀