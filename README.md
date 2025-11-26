<div align="center">

# DeployFlow.io
An open-source & self-hostable Heroku / Netlify / Vercel alternative. 

![Latest Release Version](https://img.shields.io/badge/dynamic/json?labelColor=grey&color=6366f1&label=Latest%20released%20version&url=https%3A%2F%2Fcdn.deployflow.io%2Fdeployflow%2Fversions.json&query=deployflow.v4.version&style=for-the-badge
)
</div>

## About the Project

DeployFlow.io is an open-source & self-hostable alternative to Heroku / Netlify / Vercel / etc.

It helps you manage your servers, applications, and databases on your own hardware; you only need an SSH connection. You can manage VPS, Bare Metal, Raspberry PIs, and anything else.

Imagine having the ease of a cloud but with your own servers. That is **DeployFlow.io**.

No vendor lock-in, which means that all the configurations for your applications/databases/etc are saved to your server. So, if you decide to stop using DeployFlow.io, you could still manage your running resources. You lose the automations and all the magic. 🪄️

For more information, take a look at our landing page at [deployflow.io](https://deployflow.io).

## Installation

```bash
curl -fsSL https://cdn.deployflow.io/deployflow/install.sh | bash
```
You can find the installation script source [here](./scripts/install.sh).

> [!NOTE]
> Please refer to the [docs](https://deployflow.io/docs/installation) for more information about the installation.

## Features

- 🚀 **One-Click Deployments** - Deploy applications with ease
- 🐳 **Docker Support** - Full Docker and Docker Compose support
- 📊 **Database Management** - Manage PostgreSQL, MySQL, MongoDB, and more
- 🔒 **SSL Automation** - Automatic SSL certificate management
- 🌐 **Multi-Server** - Manage multiple servers from one dashboard
- 📱 **Real-time Updates** - Live deployment status and logs
- 🔐 **Team Collaboration** - Team-based access control
- 📦 **Service Templates** - Pre-configured service templates

## Support

Contact us at [deployflow.io/docs/contact](https://deployflow.io/docs/contact).

## Cloud

If you do not want to self-host DeployFlow.io, there is a paid cloud version available: [app.deployflow.io](https://app.deployflow.io)

For more information & pricing, take a look at our landing page [deployflow.io](https://deployflow.io).

## Why should I use the Cloud version?

The recommended way to use DeployFlow.io is to have one server for DeployFlow.io and one (or more) for the resources you are deploying. A server is around 4-5$/month.

By subscribing to the cloud version, you get the DeployFlow.io server for the same price, but with:
- High-availability
- Free email notifications
- Better support
- Less maintenance for you

## Contributing

We welcome contributions! Please see our [Contributing Guide](CONTRIBUTING.md) for details.

## License

This project is licensed under the Apache-2.0 License - see the [LICENSE](LICENSE) file for details.

## Acknowledgments

* Built on top of [Coolify](https://github.com/coollabsio/coolify) - An amazing open-source PaaS platform
* [Laravel](https://laravel.com) - The elegant PHP framework
* [Livewire](https://livewire.laravel.com) - The reactive frontend framework
* [Tailwind CSS](https://tailwindcss.com) - The utility-first CSS framework

---

**DeployFlow.io** - Where Deployments Flow Smoothly! 🚀
