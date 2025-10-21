# DeployFlow.io Single-Command Installation

## 🚀 Quick Installation (Recommended)

DeployFlow.io can be installed with a single command, just like Coolify:

```bash
curl -fsSL https://cdn.deployflow.io/install.sh | sudo bash
```

This script will automatically:
- ✅ Check system requirements
- ✅ Install Docker Engine
- ✅ Create necessary directories
- ✅ Generate SSH keys
- ✅ Download configuration files
- ✅ Start DeployFlow.io

## 📋 Prerequisites

### Server Requirements
- **OS**: Linux (Ubuntu, Debian, CentOS, Fedora, etc.)
- **Architecture**: x86_64 or aarch64
- **Memory**: Minimum 2GB RAM (4GB recommended)
- **Storage**: Minimum 30GB free space
- **CPU**: Minimum 2 cores

### Supported Operating Systems
- **Debian-based**: Ubuntu, Debian (all versions)
- **RedHat-based**: CentOS, Fedora, RedHat, AlmaLinux, Rocky
- **SUSE-based**: SLES, SUSE, openSUSE
- **Arch Linux**: Arch Linux and derivatives
- **Alpine Linux**: Alpine Linux
- **Raspberry Pi**: Raspberry Pi OS 64-bit

## 🔧 Installation Methods

### 1. Quick Installation (Recommended)

```bash
# Run as root
curl -fsSL https://cdn.deployflow.io/install.sh | bash

# Or with sudo
curl -fsSL https://cdn.deployflow.io/install.sh | sudo bash
```

### 2. Manual Installation

If the automatic script fails, you can install manually:

```bash
# 1. Install Docker
curl -fsSL https://get.docker.com -o get-docker.sh
sudo sh get-docker.sh

# 2. Create directories
sudo mkdir -p /data/deployflow/{source,ssh,applications,databases,backups,services,proxy}

# 3. Download files
cd /data/deployflow/source
sudo curl -fsSL https://cdn.deployflow.io/docker-compose.yml -o docker-compose.yml
sudo curl -fsSL https://cdn.deployflow.io/docker-compose.prod.yml -o docker-compose.prod.yml
sudo curl -fsSL https://cdn.deployflow.io/.env.production -o .env

# 4. Generate SSH key
sudo ssh-keygen -f /data/deployflow/ssh/keys/deployflow@localhost -t ed25519 -N '' -C deployflow@localhost
sudo cat /data/deployflow/ssh/keys/deployflow@localhost.pub >> ~/.ssh/authorized_keys

# 5. Set permissions
sudo chown -R 9999:root /data/deployflow
sudo chmod -R 700 /data/deployflow

# 6. Generate secure values
sudo sed -i "s|APP_ID=.*|APP_ID=$(openssl rand -hex 16)|g" .env
sudo sed -i "s|APP_KEY=.*|APP_KEY=base64:$(openssl rand -base64 32)|g" .env
sudo sed -i "s|DB_PASSWORD=.*|DB_PASSWORD=$(openssl rand -base64 32)|g" .env
sudo sed -i "s|REDIS_PASSWORD=.*|REDIS_PASSWORD=$(openssl rand -base64 32)|g" .env

# 7. Create Docker network
sudo docker network create --attachable deployflow

# 8. Start DeployFlow.io
sudo docker compose --env-file .env -f docker-compose.yml -f docker-compose.prod.yml up -d --pull always --remove-orphans --force-recreate
```

## 🌐 Access DeployFlow.io

After installation, DeployFlow.io will be available at:

```
http://YOUR_SERVER_IP:8000
```

**Important**: Create your admin account immediately! The first user to register becomes the administrator.

## 🔄 Upgrading DeployFlow.io

To upgrade to the latest version:

```bash
sudo /data/deployflow/source/upgrade.sh
```

This will:
- ✅ Create a backup
- ✅ Download latest files
- ✅ Pull latest images
- ✅ Run database migrations
- ✅ Clear caches
- ✅ Restart services

## 🛠️ Management Commands

### View Logs
```bash
# Application logs
docker logs deployflow-app

# Database logs
docker logs deployflow-db

# Redis logs
docker logs deployflow-redis
```

### Restart Services
```bash
# Restart all services
docker compose -f /data/deployflow/source/docker-compose.yml restart

# Restart specific service
docker restart deployflow-app
```

### Check Status
```bash
# Check running containers
docker ps

# Check resource usage
docker stats
```

### Backup
```bash
# Manual backup
sudo /data/deployflow/source/backup.sh
```

## 🔧 Configuration

### Environment Variables

Key environment variables in `/data/deployflow/source/.env`:

```bash
# Application
APP_NAME="DeployFlow.io"
APP_URL=http://YOUR_SERVER_IP:8000

# Database
DB_PASSWORD=your_secure_password

# Redis
REDIS_PASSWORD=your_secure_password

# Pusher (WebSocket)
PUSHER_APP_ID=your_app_id
PUSHER_APP_KEY=your_app_key
PUSHER_APP_SECRET=your_app_secret
```

### Custom Domain

To use a custom domain:

1. Update `APP_URL` in `.env`
2. Configure DNS to point to your server
3. Set up SSL certificates
4. Restart DeployFlow.io

## 🚨 Troubleshooting

### Common Issues

#### Installation Fails
- Ensure you're running as root or with sudo
- Check internet connection
- Verify system requirements

#### Can't Access DeployFlow.io
- Check if port 8000 is open
- Verify containers are running: `docker ps`
- Check logs: `docker logs deployflow-app`

#### Database Connection Issues
- Verify database container is running
- Check database logs: `docker logs deployflow-db`
- Ensure passwords match in `.env`

#### Memory Issues
- Increase server memory
- Enable swap space
- Monitor resource usage: `docker stats`

### Getting Help

- **GitHub Issues**: [Report bugs and request features](https://github.com/yourusername/DeployFlow.io/issues)
- **Documentation**: [Full documentation](https://docs.deployflow.io)
- **Community**: [Join our Discord](https://discord.gg/deployflow)

## 🔒 Security Considerations

### Firewall Configuration
```bash
# Allow SSH
sudo ufw allow 22

# Allow DeployFlow.io
sudo ufw allow 8000

# Enable firewall
sudo ufw enable
```

### SSL/TLS Setup
For production deployments, set up SSL certificates:

```bash
# Install Certbot
sudo apt install certbot

# Get certificate
sudo certbot certonly --standalone -d your-domain.com

# Update nginx configuration
sudo nano /etc/nginx/sites-available/deployflow
```

### Regular Updates
Keep DeployFlow.io updated:

```bash
# Check for updates
sudo /data/deployflow/source/upgrade.sh

# Or manually pull latest
sudo docker compose -f /data/deployflow/source/docker-compose.yml pull
sudo docker compose -f /data/deployflow/source/docker-compose.yml up -d
```

## 📊 Performance Optimization

### Resource Monitoring
```bash
# Monitor resource usage
docker stats

# Check disk usage
df -h

# Check memory usage
free -h
```

### Scaling
For high-traffic deployments:
- Use multiple servers
- Set up load balancing
- Configure Redis clustering
- Use external database services

## 🎯 Next Steps

After installation:

1. **Create Admin Account** - Register as the first user
2. **Configure Servers** - Add your deployment servers
3. **Create Projects** - Set up your first project
4. **Build Flows** - Create your first deployment flow
5. **Deploy Applications** - Start deploying your apps

---

**DeployFlow.io** - Where Deployments Flow Smoothly! 🚀
