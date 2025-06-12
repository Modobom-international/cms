# DNS Records Sync Feature

This feature allows you to sync DNS records from CloudFlare API and store them in the database for each domain.

## Database Schema

### DNS Records Table

The `dns_records` table stores CloudFlare DNS records with the following fields:

- `id` - Primary key
- `cloudflare_id` - Unique CloudFlare DNS record ID
- `zone_id` - CloudFlare Zone ID
- `domain` - Domain name this record belongs to
- `type` - DNS record type (A, CNAME, MX, etc.)
- `name` - DNS record name
- `content` - DNS record content/value
- `ttl` - Time to Live
- `proxied` - Whether record is proxied through CloudFlare
- `meta` - Additional metadata from CloudFlare (JSON)
- `comment` - CloudFlare record comment
- `tags` - CloudFlare record tags (JSON)
- `cloudflare_created_on` - When record was created in CloudFlare
- `cloudflare_modified_on` - When record was last modified in CloudFlare
- `created_at` - Local creation timestamp
- `updated_at` - Local update timestamp

## Components

### 1. Models

#### DnsRecord Model

```php
use App\Models\DnsRecord;

// Get DNS records for a domain
$records = DnsRecord::forDomain('example.com')->get();

// Get specific type of records
$aRecords = DnsRecord::forDomain('example.com')->ofType('A')->get();

// Get proxied records only
$proxiedRecords = DnsRecord::forDomain('example.com')->proxied()->get();
```

#### Domain Model (Updated)

```php
use App\Models\Domain;

// Get domain with its DNS records
$domain = Domain::with('dnsRecords')->where('domain', 'example.com')->first();
```

### 2. Repository

#### DnsRecordRepository

```php
use App\Repositories\DnsRecordRepository;

$dnsRepo = app(DnsRecordRepository::class);

// Get records by domain
$records = $dnsRepo->getByDomain('example.com');

// Get records by type
$aRecords = $dnsRepo->getByDomainAndType('example.com', 'A');

// Get statistics
$stats = $dnsRepo->getStatistics('example.com');
```

### 3. Sync Job

#### SyncDnsRecords Job

```php
use App\Jobs\SyncDnsRecords;

// Sync specific domain
SyncDnsRecords::dispatch('example.com');

// Sync all domains
SyncDnsRecords::dispatch();

// Force sync (ignore cache)
SyncDnsRecords::dispatch('example.com', true);
```

## Usage

### Console Commands

#### Sync DNS Records

```bash
# Sync specific domain
php artisan dns:sync example.com --stats

# Sync all domains
php artisan dns:sync --all --stats

# Queue the sync job
php artisan dns:sync example.com --queue

# Force sync even if recently synced
php artisan dns:sync example.com --force

# Just show statistics without syncing
php artisan dns:sync example.com --stats
```

### Programmatic Usage

#### Basic Sync

```php
use App\Jobs\SyncDnsRecords;
use App\Services\CloudFlareService;
use App\Repositories\DnsRecordRepository;

// Direct sync (synchronous)
$cloudFlareService = app(CloudFlareService::class);
$dnsRecordRepository = app(DnsRecordRepository::class);
$domainRepository = app(DomainRepository::class);

$job = new SyncDnsRecords('example.com');
$job->handle($cloudFlareService, $domainRepository, $dnsRecordRepository);

// Queue sync (asynchronous)
SyncDnsRecords::dispatch('example.com');
```

#### Query DNS Records

```php
use App\Models\DnsRecord;
use App\Repositories\DnsRecordRepository;

$dnsRepo = app(DnsRecordRepository::class);

// Get all records for a domain
$records = $dnsRepo->getByDomain('example.com');

// Get specific record types
$aRecords = DnsRecord::forDomain('example.com')->ofType('A')->get();
$cnameRecords = DnsRecord::forDomain('example.com')->ofType('CNAME')->get();

// Get proxied vs non-proxied records
$proxiedRecords = DnsRecord::forDomain('example.com')->proxied(true)->get();
$nonProxiedRecords = DnsRecord::forDomain('example.com')->proxied(false)->get();

// Get statistics
$stats = $dnsRepo->getStatistics('example.com');
echo "Total records: " . $stats['total_records'];
echo "A records: " . ($stats['by_type']['A'] ?? 0);
echo "Proxied records: " . $stats['proxied_count'];
```

## Features

### Automatic Domain Resolution

The system automatically resolves the correct domain for DNS records, handling:

- Root domain records (@ records)
- Subdomain records
- Complex TLD scenarios (e.g., .co.uk, .com.au)

### Data Integrity

- Records are synced based on CloudFlare ID to prevent duplicates
- Obsolete records (deleted from CloudFlare) are automatically removed
- Timestamps are preserved from CloudFlare

### Error Handling

- Individual record sync failures don't stop the entire process
- Detailed logging for troubleshooting
- Graceful handling of domains not in CloudFlare

### Performance

- Batch processing for multiple domains
- Queue support for large sync operations
- Indexed database fields for fast queries

## Error Handling

### Common Issues and Solutions

1. **Zone ID not found**

   - Domain not added to CloudFlare
   - Check domain spelling
   - Verify CloudFlare API credentials

2. **API rate limits**

   - Use queue processing for large batches
   - Add delays between requests if needed

3. **Invalid DNS records**
   - CloudFlare API might return incomplete data
   - Check CloudFlare dashboard for record status

### Logging

All sync operations are logged to Laravel's log system:

```bash
# Check logs
tail -f storage/logs/laravel.log | grep "DNS"
```

## Migration

To set up the DNS records table:

```bash
php artisan migrate
```

The migration creates the `dns_records` table with appropriate indexes for performance.

## Scheduled Sync

The DNS records sync is automatically scheduled to run every 6 hours. This is configured in `app/Console/Kernel.php`.

### Default Schedule

- **Frequency**: Every 6 hours
- **Overlap protection**: 2 hours (prevents multiple jobs running simultaneously)
- **Server restriction**: Runs on one server only (for multi-server setups)
- **Background execution**: Doesn't block other scheduled tasks

### Alternative Schedules

You can modify the schedule in `app/Console/Kernel.php`:

```php
// Daily sync at 2 AM (recommended for most cases)
$schedule->job(new SyncDnsRecords())->dailyAt('02:00')
    ->name('sync-dns-records-daily')
    ->withoutOverlapping(240)
    ->onOneServer()
    ->runInBackground();

// Hourly sync (for high-traffic environments)
$schedule->job(new SyncDnsRecords())->hourly()
    ->name('sync-dns-records-hourly')
    ->withoutOverlapping(30)
    ->onOneServer()
    ->runInBackground();

// Custom schedule (every 12 hours)
$schedule->job(new SyncDnsRecords())->twiceDaily(1, 13)
    ->name('sync-dns-records-twice-daily')
    ->withoutOverlapping(180)
    ->onOneServer()
    ->runInBackground();
```

### Running the Scheduler

Make sure your Laravel scheduler is running via cron:

```bash
# Add this to your crontab (crontab -e)
* * * * * cd /path-to-your-project && php artisan schedule:run >> /dev/null 2>&1
```

### Monitoring Scheduled Jobs

```bash
# View scheduled tasks
php artisan schedule:list

# Run scheduler manually (for testing)
php artisan schedule:run

# View recent job activity
php artisan queue:monitor
```

## Testing

After setup, you can test the functionality:

1. **Check available domains:**

   ```bash
   php artisan tinker
   >>> App\Models\Domain::count()
   >>> App\Models\Domain::take(5)->pluck('domain')
   ```

2. **Test CloudFlare connectivity:**

   ```bash
   php artisan dns:sync your-domain.com --stats
   ```

3. **Test with all domains:**
   ```bash
   php artisan dns:sync --all --stats
   ```

## Integration with Existing Code

The DNS records feature integrates seamlessly with the existing domain management system:

- DNS records are linked to domains via the `domain` field
- No foreign key constraints at database level for flexibility
- Existing CloudFlareService methods are reused
- Compatible with the existing job queue system

## Performance Considerations

- DNS records sync is I/O intensive due to API calls
- Consider running large syncs during off-peak hours
- Use queue processing for better user experience
- Database indexes are optimized for common query patterns

## Security

- API credentials are managed through Laravel's config system
- No sensitive data is stored in DNS records table
- CloudFlare API tokens should have minimal required permissions
