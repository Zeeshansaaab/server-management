# ForgeLite

A personal Forge-style server control & deploy platform built with Laravel. Manage your own servers and deploy Laravel applications with ease.

## Features

- **Server Management**: Register and manage multiple servers via SSH
- **Site Deployment**: Deploy Laravel applications with zero-downtime releases
- **SSL Certificates**: Automatic Let's Encrypt certificate provisioning
- **Database Management**: Create and manage MySQL databases
- **Monitoring**: Real-time server metrics and health monitoring
- **Deployment Logs**: Track all deployment steps with detailed logs
- **Scheduled Commands**: Schedule artisan commands via cron
- **Backups**: Automated database and file backups

## Requirements

- PHP 8.2+
- MySQL 8.0+
- Redis
- Node.js 18+ and npm
- Docker & Docker Compose (for local development)
- Composer

## Quick Start

### Local Development with Docker

1. **Clone the repository**
   ```bash
   git clone <repository-url>
   cd server-control
   ```

2. **Set up environment**
   ```bash
   cp .env.example .env
   # Edit .env with your settings
   ```

3. **Run development setup**
   ```bash
   ./scripts/dev_setup.sh
   ```

4. **Start Docker containers**
   ```bash
   docker-compose up -d
   ```

5. **Run migrations**
   ```bash
   docker-compose exec app php artisan migrate
   ```

6. **Create your first user**
   ```bash
   docker-compose exec app php artisan tinker
   ```
   ```php
   User::create([
       'name' => 'Your Name',
       'email' => 'your@email.com',
       'password' => Hash::make('password'),
       'role' => 'owner',
   ]);
   ```

7. **Access the application**
   - Web UI: http://localhost:8080
   - Redis Insight: http://localhost:8001

### Manual Setup (without Docker)

1. **Install dependencies**
   ```bash
   composer install
   npm install
   ```

2. **Configure environment**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

3. **Set up database**
   ```bash
   php artisan migrate
   ```

4. **Build assets**
   ```bash
   npm run build
   ```

5. **Start services**
   ```bash
   php artisan serve
   php artisan horizon
   npm run dev
   ```

## Installing the Agent on a Server

The ForgeLite agent runs on each managed server and reports metrics back to the platform.

### One-Line Install

```bash
FORGELITE_AGENT_TOKEN=your_token_here FORGELITE_API_URL=https://your-platform.com bash -s < agent/install.sh
```

### Manual Install

1. **Get your agent token** from the ForgeLite dashboard after creating a server
2. **SSH into your server**
3. **Run the installer**:
   ```bash
   export FORGELITE_AGENT_TOKEN=your_token_here
   export FORGELITE_API_URL=https://your-platform.com
   curl -sSL https://raw.githubusercontent.com/yourusername/forgelite/main/agent/install.sh | bash
   ```

The agent will:
- Install required dependencies (curl, jq, bc)
- Create a systemd service
- Start reporting metrics every minute
- Register itself with the platform

### Agent Configuration

The agent configuration is stored in `/opt/forgelite-agent/config`:
```bash
API_URL=https://your-platform.com
AGENT_TOKEN=your_token_here
```

### Agent Service Management

```bash
# Check status
systemctl status forgelite-agent

# View logs
journalctl -u forgelite-agent -f

# Restart
systemctl restart forgelite-agent

# Stop
systemctl stop forgelite-agent
```

## Usage

### Registering a Server

1. **Via Web UI**:
   - Go to Servers → Add Server
   - Enter server details (IP, hostname, SSH credentials)
   - Optionally provide SSH private key or use agent token

2. **Via Agent** (Recommended):
   - Create server in UI (just IP and name)
   - Copy the agent token
   - Run the one-line install command on your server

### Creating a Site

1. Go to Sites → Add Site
2. Select a server
3. Enter domain name
4. Provide Git repository URL
5. Configure PHP version and branch
6. Add environment variables

The system will:
- Create a system user for the site
- Set up directory structure
- Create nginx configuration
- Prepare for deployments

### Deploying a Site

1. Go to the site details page
2. Click "Deploy" button
3. Watch real-time deployment logs
4. Deployment includes:
   - Git pull
   - Composer install
   - NPM build
   - Artisan migrations
   - Cache optimization
   - Zero-downtime release switch

### SSL Certificates

1. Go to site details
2. Click "Request SSL Certificate"
3. System will use Let's Encrypt to provision certificate
4. Nginx config will be updated automatically
5. Certificate renewal is handled via cron

### Database Management

1. Go to site details
2. Click "Create Database"
3. System creates MySQL database and user
4. Credentials are stored encrypted
5. Use credentials in your Laravel .env

## Architecture

### Components

- **Laravel Backend**: API and web routes
- **Inertia.js + Vue 3**: Frontend SPA
- **Laravel Horizon**: Queue monitoring
- **Redis**: Queue and cache backend
- **Agent**: Bash script running on managed servers

### Deployment Flow

1. User triggers deployment via UI/API
2. `DeploySite` job is queued
3. Job creates release directory with timestamp
4. Steps executed sequentially:
   - Clone/update repository
   - Install dependencies
   - Build assets
   - Run migrations
   - Update symlink (zero-downtime)
   - Clear caches
5. Logs streamed to UI via Redis broadcasting

### Security

- SSH keys encrypted at rest using Laravel encryption
- Agent tokens are unique per server
- Database credentials encrypted
- Environment variables encrypted
- All sensitive data uses Laravel's encryption

## Development

### Running Tests

```bash
php artisan test
```

### Code Style

```bash
./vendor/bin/pint
```

### Static Analysis

```bash
./vendor/bin/phpstan analyse
```

## Production Deployment

### Server Requirements

- Ubuntu 22.04 LTS (recommended)
- PHP 8.2+ with extensions: pdo_mysql, mbstring, bcmath, gd
- MySQL 8.0+
- Redis
- Nginx
- Supervisor (for queue workers)

### Deployment Steps

1. **Clone repository**
   ```bash
   git clone <repo> /var/www/forgelite
   cd /var/www/forgelite
   ```

2. **Install dependencies**
   ```bash
   composer install --no-dev --optimize-autoloader
   npm ci && npm run build
   ```

3. **Configure environment**
   ```bash
   cp .env.example .env
   # Edit .env with production values
   php artisan key:generate
   ```

4. **Run migrations**
   ```bash
   php artisan migrate --force
   ```

5. **Set permissions**
   ```bash
   chown -R www-data:www-data storage bootstrap/cache
   ```

6. **Configure Nginx**
   ```nginx
   server {
       listen 80;
       server_name your-domain.com;
       root /var/www/forgelite/public;
       
       add_header X-Frame-Options "SAMEORIGIN";
       add_header X-Content-Type-Options "nosniff";
       
       index index.php;
       
       charset utf-8;
       
       location / {
           try_files $uri $uri/ /index.php?$query_string;
       }
       
       location = /favicon.ico { access_log off; log_not_found off; }
       location = /robots.txt  { access_log off; log_not_found off; }
       
       error_page 404 /index.php;
       
       location ~ \.php$ {
           fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
           fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
           include fastcgi_params;
       }
       
       location ~ /\.(?!well-known).* {
           deny all;
       }
   }
   ```

7. **Set up Horizon**
   ```bash
   php artisan horizon:install
   # Configure supervisor for horizon
   ```

8. **Set up queue workers** (if not using Horizon)
   ```bash
   # Create supervisor config
   ```

## Documentation

- [Agent Installation](docs/INSTALL_AGENT.md)
- [Deployment Guide](docs/DEPLOY.md)
- [Security Best Practices](docs/SECURITY.md)

## License

MIT License - see [LICENSE](LICENSE) file for details.

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## Support

For issues and questions, please open an issue on GitHub.
