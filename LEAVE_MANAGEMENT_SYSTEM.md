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
- Supports recurring holidays and adjustment rules

### Updated User Model

- Added employment fields (start date, contract type, probation status)
- Added salary fields (hourly, daily, monthly rates)
- Added work schedule fields
- Relationships to leaves and entitlements

## Security and Permissions

- **Employees**: Can view/create/update own leave requests
- **Admins**: Full access to all leave requests and approvals
- **Salary Calculations**: Admin-only access
- **Holiday Management**: Admin-only for CRUD operations
- **Public Holiday Viewing**: Available to all authenticated users

This comprehensive system provides complete leave management with salary integration, following the specified company policies and providing flexibility for future policy changes.
