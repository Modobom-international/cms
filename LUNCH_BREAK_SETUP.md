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
