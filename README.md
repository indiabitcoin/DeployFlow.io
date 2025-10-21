# 🚀 DeployFlow.io

**Where Deployments Flow Smoothly**

DeployFlow.io is a modern, visual deployment control panel built on top of Coolify. It provides an intuitive drag-and-drop interface for creating deployment pipelines, making complex deployments as simple as drawing a flowchart.

## ✨ Features

### 🎨 Visual Flow Builder
- **Drag-and-drop interface** for creating deployment pipelines
- **Pre-built templates** for common deployment scenarios
- **Real-time flow execution** with live status updates
- **Smart suggestions** based on your project type

### 📊 Deployment Analytics
- **Success rate tracking** across all deployments
- **Performance metrics** and timing analysis
- **Error reporting** with detailed logs
- **Historical deployment data**

### 🔄 One-Command Installation
Just like Coolify, DeployFlow.io can be installed with a single command:

```bash
curl -fsSL https://cdn.deployflow.io/install.sh | sudo bash
```

### 🌐 Multi-Platform Support
- **Docker** containers and Docker Compose
- **Static sites** (React, Vue, Angular, etc.)
- **Node.js** applications
- **PHP** applications (Laravel, Symfony, etc.)
- **Python** applications (Django, Flask, etc.)
- **Ruby** applications (Rails, Sinatra, etc.)

### 🔐 Enterprise Security
- **Team-based access control**
- **SSH key management**
- **SSL certificate automation**
- **Environment variable encryption**
- **Audit logging**

## 🚀 Quick Start

### Prerequisites
- **Server**: Linux (Ubuntu, Debian, CentOS, Fedora, etc.)
- **Memory**: Minimum 2GB RAM (4GB recommended)
- **Storage**: Minimum 30GB free space
- **CPU**: Minimum 2 cores

### Installation

1. **Install DeployFlow.io**:
   ```bash
   curl -fsSL https://cdn.deployflow.io/install.sh | sudo bash
   ```

2. **Access the dashboard**:
   ```
   http://YOUR_SERVER_IP:8000
   ```

3. **Create your admin account** (first user becomes admin)

4. **Start building deployment flows**!

## 📖 Documentation

- **[Installation Guide](INSTALLATION.md)** - Detailed installation instructions
- **[Deployment Guide](DEPLOYMENT.md)** - Various deployment options
- **[Cloud Deployment](CLOUD_DEPLOYMENT.md)** - Cloud platform deployments
- **[API Reference](docs/api.md)** - REST API documentation

## 🛠️ Development

### Local Development Setup

1. **Clone the repository**:
   ```bash
   git clone https://github.com/indiabitcoin/DeployFlow.io.git
   cd DeployFlow.io
   ```

2. **Install dependencies**:
   ```bash
   composer install
   npm install
   ```

3. **Set up environment**:
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

4. **Run migrations**:
   ```bash
   php artisan migrate
   ```

5. **Start development server**:
   ```bash
   php artisan serve
   npm run dev
   ```

### Docker Development

```bash
# Start development environment
docker-compose -f docker-compose.dev.yml up -d

# Access at http://localhost:8000
```

## 🔧 Configuration

### Environment Variables

Key configuration options in `.env`:

```bash
# Application
APP_NAME="DeployFlow.io"
APP_URL=http://localhost:8000

# Database
DB_CONNECTION=pgsql
DB_HOST=localhost
DB_DATABASE=deployflow

# Redis
REDIS_HOST=localhost
REDIS_PORT=6379

# DeployFlow.io Features
DEPLOYFLOW_FEATURES_VISUAL_FLOW_BUILDER=true
DEPLOYFLOW_FEATURES_FLOW_ANALYTICS=true
DEPLOYFLOW_FEATURES_SMART_SUGGESTIONS=true
```

### Custom Branding

DeployFlow.io supports custom branding:

```bash
DEPLOYFLOW_BRANDING_NAME="Your Company"
DEPLOYFLOW_BRANDING_TAGLINE="Your Custom Tagline"
```

## 🚀 Deployment Options

### Self-Hosted (Recommended)
- **VPS/Server**: Full control, one-command installation
- **Docker**: Containerized deployment
- **Kubernetes**: Scalable container orchestration

### Cloud Platforms
- **Railway**: $5/month, automatic deployments
- **Render**: Free tier available, simple setup
- **Fly.io**: Global deployment, $1.94+/month
- **DigitalOcean**: Best value, $5+/month

### One-Command Deployments
```bash
# Deploy to Railway
./deploy.sh railway

# Deploy to VPS
./deploy.sh vps

# Deploy with Docker
./deploy.sh docker
```

## 📊 Architecture

DeployFlow.io is built on modern technologies:

- **Backend**: Laravel 12 (PHP 8.4)
- **Frontend**: Livewire 3.5 + Alpine.js + Tailwind CSS 4
- **Database**: PostgreSQL 15
- **Cache**: Redis 7
- **WebSockets**: Soketi
- **Containerization**: Docker & Docker Compose

## 🤝 Contributing

We welcome contributions! Please see our [Contributing Guide](CONTRIBUTING.md) for details.

### Development Workflow

1. **Fork the repository**
2. **Create a feature branch**: `git checkout -b feature/amazing-feature`
3. **Make your changes**
4. **Run tests**: `php artisan test`
5. **Commit changes**: `git commit -m 'Add amazing feature'`
6. **Push to branch**: `git push origin feature/amazing-feature`
7. **Open a Pull Request**

## 📝 License

This project is licensed under the Apache-2.0 License - see the [LICENSE](LICENSE) file for details.

## 🙏 Acknowledgments

- **Coolify** - The amazing foundation this project is built on
- **Laravel** - The elegant PHP framework
- **Livewire** - The reactive frontend framework
- **Tailwind CSS** - The utility-first CSS framework

## 📞 Support

- **GitHub Issues**: [Report bugs and request features](https://github.com/indiabitcoin/DeployFlow.io/issues)
- **Documentation**: [Full documentation](https://docs.deployflow.io)
- **Community**: [Join our Discord](https://discord.gg/deployflow)

## 🌟 Star History

[![Star History Chart](https://api.star-history.com/svg?repos=indiabitcoin/DeployFlow.io&type=Date)](https://star-history.com/#indiabitcoin/DeployFlow.io&Date)

---

**DeployFlow.io** - Where Deployments Flow Smoothly! 🚀

Made with ❤️ by the DeployFlow.io team