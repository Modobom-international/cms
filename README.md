# Laravel Project Documentation

<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## Table of Contents
- [About Laravel](#about-laravel)
- [Lunch Break Configuration](#lunch-break-configuration)
- [DNS Records Sync Feature](#dns-records-sync-feature)
- [Leave Management System](#leave-management-system)
- [Attendance Complaints API](#attendance-complaints-api)
- [Attendance API](#attendance-api)
- [Activity Log API](#activity-log-api)
- [Activity Log API Testing Examples](#activity-log-api-testing-examples)

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework.

You may also try the [Laravel Bootcamp](https://bootcamp.laravel.com), where you will be guided through building a modern Laravel application from scratch.

If you don't feel like reading, [Laracasts](https://laracasts.com) can help. Laracasts contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

## Laravel Sponsors

We would like to extend our thanks to the following sponsors for funding Laravel development. If you are interested in becoming a sponsor, please visit the [Laravel Partners program](https://partners.laravel.com).

### Premium Partners

- **[Vehikl](https://vehikl.com/)**
- **[Tighten Co.](https://tighten.co)**
- **[WebReinvent](https://webreinvent.com/)**
- **[Kirschbaum Development Group](https://kirschbaumdevelopment.com)**
- **[64 Robots](https://64robots.com)**
- **[Curotec](https://www.curotec.com/services/technologies/laravel/)**
- **[Cyber-Duck](https://cyber-duck.co.uk)**
- **[DevSquad](https://devsquad.com/hire-laravel-developers)**
- **[Jump24](https://jump24.co.uk)**
- **[Redberry](https://redberry.international/laravel/)**
- **[Active Logic](https://activelogic.com)**
- **[byte5](https://byte5.de)**
- **[OP.GG](https://op.gg)**

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).

---

# Lunch Break Configuration

## Overview

The attendance system now automatically deducts lunch break time from total work hours based on configured constants. This ensures accurate work hour calculations without manual intervention.

## Configuration

Lunch break settings are defined in `config/attendance.php`:

```php
'lunch_break' => [
    'enabled' => true,              // Enable/disable lunch break deduction
    'start_time' => '12:00',        // Lunch break start time (HH:MM)
    'end_time' => '13:00',          // Lunch break end time (HH:MM)
    'full_day_only' => true,        // Apply only to full-day attendance
],
```

## Environment Variables

You can override these settings using environment variables in your `.env` file:

```env
LUNCH_BREAK_ENABLED=true
LUNCH_BREAK_START=12:00
LUNCH_BREAK_END=13:00
LUNCH_BREAK_FULL_DAY_ONLY=true
```

## How It Works

### Automatic Deduction

- The system automatically calculates lunch break duration and deducts it from total work hours
- Lunch break is only deducted if the employee's work period overlaps with the configured lunch time
- If `full_day_only` is true, half-day attendance won't have lunch break deducted

### Smart Calculation Examples

**Scenario 1: Standard full-day work**

- Check-in: 09:00, Check-out: 18:00
- Lunch: 12:00-13:00 (1 hour)
- **Result**: 9 hours - 1 hour = **8 hours**

**Scenario 2: Half-day work (with full_day_only=true)**

- Check-in: 09:00, Check-out: 13:00
- Lunch: 12:00-13:00 (1 hour)
- **Result**: 4 hours - 0 hours = **4 hours** (no deduction)

**Scenario 3: Work outside lunch hours**

- Check-in: 14:00, Check-out: 22:00
- Lunch: 12:00-13:00 (1 hour)
- **Result**: 8 hours - 0 hours = **8 hours** (no overlap)

**Scenario 4: Lunch break disabled**

- Check-in: 09:00, Check-out: 18:00
- Lunch: disabled
- **Result**: 9 hours - 0 hours = **9 hours**

## Customization

### Change Lunch Break Time

Edit `config/attendance.php`:

```php
'lunch_break' => [
    'start_time' => '11:30',   // Changed to 11:30 AM
    'end_time' => '12:30',     // Changed to 12:30 PM
    // ... other settings
],
```

### Disable Lunch Break

```php
'lunch_break' => [
    'enabled' => false,
    // ... other settings
],
```

### Allow Half-Day Lunch Break

```php
'lunch_break' => [
    'full_day_only' => false,  // Apply to both full and half day
    // ... other settings
],
```

### Different Lunch Break by Environment

In your `.env` files:

**.env.production:**

```env
LUNCH_BREAK_START=12:00
LUNCH_BREAK_END=13:00
```

**.env.development:**

```env
LUNCH_BREAK_START=11:30
LUNCH_BREAK_END=12:30
```

## Integration

The lunch break configuration is automatically used by:

- **Attendance calculation**: `calculateWorkHours()` method
- **Status updates**: Determines if attendance is complete/incomplete
- **Admin reports**: All attendance reports reflect lunch break deductions
- **Custom attendance**: Admin-created records also respect lunch break settings

## Validation

The system includes built-in validation:

- Time format must be HH:MM (24-hour format)
- End time must be after start time
- Only deducts lunch break when work period overlaps with lunch time

## Cache Clearing

After changing configuration values, clear the config cache:

```bash
php artisan config:clear
```

## Default Settings

- **Enabled**: Yes
- **Start Time**: 12:00 (noon)
- **End Time**: 13:00 (1 PM)
- **Duration**: 1 hour
- **Full Day Only**: Yes

---

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
cnameRecords = DnsRecord::forDomain('example.com')->ofType('CNAME')->get();

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

```

---

# Leave Management System

# Leave Management System Documentation

## Overview

This comprehensive leave management system integrates with attendance tracking for salary calculations and provides the following features:

- Employee leave requests with multiple types (sick, vacation, personal, etc.)
- Remote work requests as a separate category
- Monthly leave entitlements (1 day per month for official employees)
- Paid/unpaid leave tracking based on available entitlements
- Public holiday management with weekend adjustments
- Salary calculation considering attendance, leave, and company policies
- Saturday and holiday work bonuses
- Attendance integration with leave status

## Database Structure

### Tables Created

1. **leave_requests** - Stores all leave and remote work requests
2. **employee_leave_entitlements** - Monthly leave allocations per employee
3. **public_holidays** - Company and national holidays with adjustment rules
4. **attendance_complaints** - Employee attendance dispute system
5. **users** (updated) - Added employment and salary fields

### Key Features

#### Leave Request Types

- `sick` - Sick leave
- `vacation` - Annual vacation
- `personal` - Personal leave
- `maternity` - Maternity leave
- `paternity` - Paternity leave
- `emergency` - Emergency leave
- `remote_work` - Work from home/remote location
- `other` - Other types

#### Request Types

- `absence` - Employee will be absent from office
- `remote_work` - Employee will work remotely

## API Endpoints

### Employee Leave Requests

```
GET    /api/leave-requests              - Get my leave requests
POST   /api/leave-requests              - Create new leave request
GET    /api/leave-requests/balance      - Get my leave balance
GET    /api/leave-requests/active-leaves - Get active leaves
GET    /api/leave-requests/{id}         - Get specific leave request
PUT    /api/leave-requests/{id}         - Update leave request (if pending)
PATCH  /api/leave-requests/{id}/cancel  - Cancel leave request
```

### Admin Leave Management

```
GET    /api/admin/leave-requests                    - Get all leave requests
GET    /api/admin/leave-requests/statistics        - Get leave statistics
GET    /api/admin/leave-requests/active-leaves     - Get all active leaves
GET    /api/admin/leave-requests/{id}              - Get specific leave request
PUT    /api/admin/leave-requests/{id}/status       - Approve/reject leave request
```

### Salary Calculation

```
GET    /api/admin/salary/calculate/{employeeId}           - Calculate employee salary
GET    /api/admin/salary/calculate-all                    - Calculate all salaries
GET    /api/admin/salary/attendance-summary/{employeeId} - Get attendance summary
GET    /api/admin/salary/leave-summary/{employeeId}      - Get leave summary
```

### Public Holidays

```
GET    /api/holidays                     - Get holidays (employees)
GET    /api/holidays/upcoming            - Get upcoming holidays
GET    /api/holidays/check               - Check if date is holiday
GET    /api/holidays/month               - Get holidays for month
GET    /api/holidays/{id}                - Get specific holiday

POST   /api/admin/holidays               - Create holiday (admin)
PUT    /api/admin/holidays/{id}          - Update holiday (admin)
DELETE /api/admin/holidays/{id}          - Delete holiday (admin)
POST   /api/admin/holidays/generate-yearly - Generate yearly holidays (admin)
```

## Company Policies Implemented

### Leave Entitlements

- **Official Contract Employees**: 1 day per month (12 days annually)
- **Probation Employees**: No paid leave entitlements
- **Contract Employees**: No paid leave entitlements
- **Maximum Usage**: 2 days per month can be used
- **No Carry Over**: Unused days expire at month end

### Saturday Work Policy

- Working on Saturday earns double pay bonus
- Calculated as additional payment equal to daily rate

### Public Holiday Policy

- Holidays falling on weekends are adjusted to workdays
- Working on holidays earns double pay bonus
- Holiday adjustment rules: `none`, `previous_workday`, `next_workday`, `company_decision`

### Salary Calculation Rules

#### Base Salary

- **Monthly Salary**: Full salary regardless of attendance
- **Daily Rate**: Pay per day worked
- **Hourly Rate**: Pay per hour worked

#### Bonuses

- **Overtime**: 1.5x hourly rate for hours beyond standard work hours
- **Saturday Work**: Additional daily rate for Saturday work
- **Holiday Work**: Additional daily rate for holiday work

#### Deductions

- **Late Arrivals**: 10% of daily rate per late arrival (after 3 free instances)
- **Unauthorized Absence**: Full daily rate deduction per absent day
- **Unpaid Leave**: Deduction for leave days exceeding entitlements

## Usage Examples

### Creating a Leave Request

```json
POST /api/leave-requests
{
    "leave_type": "vacation",
    "request_type": "absence",
    "start_date": "2025-06-10",
    "end_date": "2025-06-12",
    "is_full_day": true,
    "reason": "Family vacation"
}
```

### Creating a Remote Work Request

```json
POST /api/leave-requests
{
    "leave_type": "remote_work",
    "request_type": "remote_work",
    "start_date": "2025-06-15",
    "end_date": "2025-06-15",
    "is_full_day": true,
    "reason": "Working from home",
    "remote_work_details": {
        "location": "Home office",
        "equipment_needed": ["Laptop", "VPN access"],
        "contact_method": "Slack, Email"
    }
}
```

### Calculating Monthly Salary

```json
GET /api/admin/salary/calculate/1?year=2025&month=6

Response:
{
    "employee_id": 1,
    "employee_name": "John Doe",
    "period": {
        "start_date": "2025-06-01",
        "end_date": "2025-06-30",
        "month": 6,
        "year": 2025
    },
    "salary_components": {
        "base_salary": 50000,
        "leave_adjustments": 0,
        "overtime_payment": 1500,
        "bonuses": 2000,
        "gross_salary": 53500,
        "deductions": {
            "late_arrival_penalty": 200
        },
        "total_deductions": 200,
        "net_salary": 53300
    },
    "attendance_summary": {
        "days_worked": 20,
        "days_on_leave": 2,
        "saturday_work_days": 1,
        "overtime_hours": 10
    }
}
```

## Commands Available

### Populate Leave Entitlements

```bash
# Populate for entire year
php artisan leave:populate-entitlements --year=2025

# Populate for specific month
php artisan leave:populate-entitlements --year=2025 --month=6

# Populate for specific employee
php artisan leave:populate-entitlements --employee=1 --year=2025
```

### Seed Public Holidays

```bash
php artisan db:seed --class=PublicHolidaySeeder
```

## Integration with Attendance System

The leave management system integrates seamlessly with the existing attendance system:

1. **Attendance Status Updates**: Attendance records automatically show `on_leave` or `remote_work` status when approved leave exists
2. **Salary Calculations**: Leave days are factored into salary calculations
3. **Working Days**: Public holidays and weekends are excluded from working day calculations
4. **Overtime Tracking**: Hours worked beyond standard time are tracked for bonus calculations

## Models and Relationships

### LeaveRequest Model

- Belongs to employee (User)
- Belongs to approver (User)
- Has status tracking and approval workflow

### EmployeeLeaveEntitlement Model

- Belongs to employee (User)
- Tracks monthly allocations and usage
- Enforces company policies

### PublicHoliday Model

- Independent model for holiday management

---

# Attendance Complaints API

(Copy toàn bộ nội dung từ ATTENDANCE_COMPLAINTS_API.md vào đây, thay thế dòng placeholder.)

---

# Attendance API

(Copy toàn bộ nội dung từ ATTENDANCE_API_DOCUMENTATION.md vào đây, thay thế dòng placeholder.)

---

# Activity Log API

(Copy toàn bộ nội dung từ ACTIVITY_LOG_API_DOCUMENTATION.md vào đây, thay thế dòng placeholder.)

---

# Activity Log API Testing Examples

(Copy toàn bộ nội dung từ ACTIVITY_LOG_TESTING_EXAMPLES.md vào đây, thay thế dòng placeholder.)

---
