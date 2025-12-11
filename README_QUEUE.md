# Queue Workers Setup

This project uses Laravel queues for heavy tasks like site scanning and deployments.

## Setup

1. **Run migrations** (if not already done):
```bash
php artisan migrate
```

2. **Start the queue worker**:
```bash
php artisan queue:work
```

Or for development with auto-reload:
```bash
php artisan queue:listen
```

3. **For production**, use Supervisor to keep the queue worker running:
```bash
# Install supervisor (Ubuntu/Debian)
sudo apt-get install supervisor

# Create config file at /etc/supervisor/conf.d/laravel-worker.conf
# See templates/supervisor-queue.conf for example
```

## Caching

The application uses Laravel's cache for:
- Dashboard data (2 minutes)
- Server lists (5 minutes)
- Site lists (5 minutes)
- Server details (2 minutes)
- Job status (stored in cache)

Cache is automatically cleared when:
- Servers are added/updated
- Sites are added/updated/imported
- Cron jobs are added/deleted

## Queue Jobs

### ScanSitesJob
- Scans server for existing websites
- Timeout: 2 minutes
- Results stored in cache with job ID

### DeploySiteJob
- Deploys a site
- Timeout: 10 minutes
- Results stored in cache with job ID

## Frontend Polling

The frontend polls for job status every 2 seconds until:
- Job completes (status: 'completed')
- Job fails (status: 'failed')
- Timeout reached (2 minutes for scan, 10 minutes for deploy)

