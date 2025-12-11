# Deployment Guide

This guide covers deploying Laravel applications using ForgeLite.

## Prerequisites

- A server registered in ForgeLite
- SSH access configured
- Git repository with your Laravel application
- Domain name pointing to your server

## Creating a Site

### Step 1: Add Site in ForgeLite

1. Navigate to **Sites** → **Add Site**
2. Fill in the form:
   - **Server**: Select the server
   - **Domain**: Your domain name (e.g., `example.com`)
   - **Repository URL**: Git repository URL
   - **Branch**: Default branch (usually `main` or `master`)
   - **PHP Version**: PHP version to use (8.2, 8.3, etc.)
   - **Environment Variables**: Add your `.env` variables

### Step 2: Site Setup

ForgeLite will automatically:
- Create a system user (`forge_example_com`)
- Create directory structure:
  ```
  /home/forge_example_com/sites/example.com/
  ├── releases/
  ├── shared/
  └── current -> symlink to latest release
  ```
- Create nginx configuration
- Set up PHP-FPM pool

## Deployment Process

### Automatic Deployment

1. Go to your site's detail page
2. Click **Deploy** button
3. Watch real-time deployment logs

### Deployment Steps

Each deployment follows these steps:

1. **Create Release Directory**
   - Creates timestamped directory: `releases/20241210120000`

2. **Clone Repository**
   ```bash
   git clone -b main https://github.com/user/repo.git .
   ```

3. **Install Composer Dependencies**
   ```bash
   composer install --no-dev --optimize-autoloader
   ```

4. **Install NPM Dependencies**
   ```bash
   npm ci
   ```

5. **Build Assets**
   ```bash
   npm run build
   ```

6. **Copy Environment File**
   - Copies `.env` from `shared/` directory

7. **Set Permissions**
   ```bash
   chown -R forge_example_com:forge_example_com releases/20241210120000
   ```

8. **Update Symlink** (Zero-Downtime)
   ```bash
   ln -sfn releases/20241210120000 current
   ```

9. **Run Migrations**
   ```bash
   php artisan migrate --force
   ```

10. **Optimize Application**
    ```bash
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache
    php artisan queue:restart
    ```

### Manual Deployment via API

```bash
curl -X POST https://your-platform.com/api/deployments \
  -H "Authorization: Bearer your_token" \
  -H "Content-Type: application/json" \
  -d '{
    "site_id": 1,
    "commit_hash": "abc123"
  }'
```

### Deployment via Webhook

You can trigger deployments via webhook from GitHub, GitLab, etc.:

```bash
curl -X POST https://your-platform.com/api/deployments \
  -H "X-Webhook-Token: your_webhook_token" \
  -H "Content-Type: application/json" \
  -d '{
    "site_id": 1,
    "commit_hash": "abc123"
  }'
```

## Environment Variables

### Setting Environment Variables

1. Go to site details
2. Edit environment variables
3. Variables are encrypted and stored in database
4. Deployed as `.env` file in site directory

### Default Variables

ForgeLite automatically sets:
- `APP_ENV=production`
- `APP_DEBUG=false`
- `APP_URL=https://your-domain.com`

### Database Connection

If you created a database via ForgeLite:
- `DB_HOST=localhost`
- `DB_DATABASE=db_name`
- `DB_USERNAME=db_user`
- `DB_PASSWORD=encrypted_password`

## Rollback

### Automatic Rollback

If deployment fails, the previous release remains active.

### Manual Rollback

1. Go to site details
2. View deployment history
3. Click "Rollback" on a previous successful deployment

The system will:
- Update symlink to previous release
- Clear caches
- Restart queues

## Deployment Best Practices

### 1. Use Release Directories

ForgeLite uses release directories for zero-downtime deployments:
- Each deployment creates a new directory
- Symlink switches atomically
- Previous releases kept for rollback

### 2. Shared Directories

Store persistent files in `shared/`:
- `.env` file
- `storage/` directory (symlinked)
- Uploaded files

### 3. Database Migrations

- Always test migrations locally first
- Use `--force` flag in production
- Consider migration rollback strategies

### 4. Asset Building

- Build assets before deployment
- Use `npm run build` for production
- Consider CDN for static assets

### 5. Queue Workers

- Restart queue workers after deployment
- Use `php artisan queue:restart`
- Consider using Horizon for queue management

## Troubleshooting

### Deployment Fails

1. **Check logs**:
   - View deployment logs in ForgeLite UI
   - Check server logs: `tail -f /var/log/nginx/error.log`

2. **Common issues**:
   - Git repository not accessible
   - Composer/NPM dependencies fail
   - Insufficient disk space
   - Permission issues

### Site Not Accessible

1. **Check nginx**:
   ```bash
   nginx -t
   systemctl status nginx
   ```

2. **Check PHP-FPM**:
   ```bash
   systemctl status php8.2-fpm
   ```

3. **Check permissions**:
   ```bash
   ls -la /home/forge_example_com/sites/example.com/current
   ```

### Database Connection Issues

1. Verify database credentials in environment variables
2. Check MySQL is running: `systemctl status mysql`
3. Test connection manually:
   ```bash
   mysql -u db_user -p db_name
   ```

## Advanced Configuration

### Custom Deployment Scripts

You can add custom deployment steps by modifying the deployment service or adding artisan commands.

### Deployment Hooks

ForgeLite supports deployment hooks:
- `before_deploy`: Run before deployment starts
- `after_deploy`: Run after successful deployment
- `on_failure`: Run if deployment fails

### Multiple Environments

Create separate sites for:
- `staging.example.com` (staging)
- `example.com` (production)

Use different branches and environment variables for each.

