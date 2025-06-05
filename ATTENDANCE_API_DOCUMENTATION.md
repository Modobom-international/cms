# Attendance API Documentation

## Overview

The Attendance API provides comprehensive attendance tracking functionality with integration to the leave management system. It supports check-in/check-out operations, attendance reporting, and automatic leave status detection.

## Authentication

All endpoints require authentication using Bearer tokens:

```
Authorization: Bearer {your-token-here}
```

## Base URL

```
{BASE_URL}/api
```

## Attendance Status Types

The system supports the following attendance statuses:

- `incomplete` - Default status when checked in but not checked out
- `completed` - Successfully completed work day with check-in and check-out
- `on_leave` - Employee is on approved absence leave
- `remote_work` - Employee is working remotely (approved remote work)

## Attendance Types

- `full_day` - Full day attendance (8 hours)
- `half_day` - Half day attendance (4 hours)

---

## Employee Endpoints

### 1. Check In

**Endpoint:** `POST /attendance/checkin`

**Description:** Records employee check-in for the current day. Automatically detects if employee has approved leave.

**Request Body:**

```json
{
  "employee_id": 1,
  "type": "full_day"
}
```

**Request Parameters:**
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| employee_id | integer | Yes | ID of the employee |
| type | string | Yes | Either "full_day" or "half_day" |

**Success Response (200):**

```json
{
  "message": "Check-in successful",
  "data": {
    "id": 123,
    "employee_id": 1,
    "date": "2025-06-04",
    "type": "full_day",
    "checkin_time": "2025-06-04T08:30:00.000000Z",
    "checkout_time": null,
    "total_work_hours": null,
    "status": "incomplete",
    "description": null,
    "branch_name": null,
    "created_at": "2025-06-04T08:30:00.000000Z",
    "updated_at": "2025-06-04T08:30:00.000000Z"
  }
}
```

**Response with Remote Work (200):**

```json
{
  "message": "Check-in successful",
  "data": {
    "id": 123,
    "employee_id": 1,
    "date": "2025-06-04",
    "type": "full_day",
    "checkin_time": "2025-06-04T08:30:00.000000Z",
    "checkout_time": null,
    "total_work_hours": null,
    "status": "remote_work",
    "description": "Remote work: Working from home",
    "branch_name": null
  },
  "remote_work_info": {
    "location": "Home office",
    "reason": "Working from home",
    "approved_by": "Manager Name"
  }
}
```

**Response with Leave (200):**

```json
{
  "message": "Employee is on approved leave today",
  "data": {
    "id": 123,
    "employee_id": 1,
    "date": "2025-06-04",
    "type": "full_day",
    "checkin_time": null,
    "checkout_time": null,
    "total_work_hours": null,
    "status": "on_leave",
    "description": "On approved leave: sick"
  },
  "leave_info": {
    "leave_type": "sick",
    "reason": "Medical appointment",
    "approved_by": "Manager Name"
  }
}
```

**Error Responses:**

**Already Checked In (400):**

```json
{
  "message": "Already checked in for today"
}
```

**Validation Error (422):**

```json
{
  "message": "The given data was invalid.",
  "errors": {
    "employee_id": ["The employee id field is required."],
    "type": ["The type field must be one of: full_day, half_day."]
  }
}
```

---

### 2. Check Out

**Endpoint:** `POST /attendance/checkout`

**Description:** Records employee check-out and calculates total work hours.

**Request Body:**

```json
{
  "employee_id": 1
}
```

**Request Parameters:**
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| employee_id | integer | Yes | ID of the employee |

**Success Response (200):**

```json
{
  "message": "Check-out successful",
  "data": {
    "id": 123,
    "employee_id": 1,
    "date": "2025-06-04",
    "type": "full_day",
    "checkin_time": "2025-06-04T08:30:00.000000Z",
    "checkout_time": "2025-06-04T17:30:00.000000Z",
    "total_work_hours": 8.5,
    "status": "completed",
    "description": null,
    "branch_name": null
  }
}
```

**Error Responses:**

**No Check-in Found (404):**

```json
{
  "message": "No check-in record found for today"
}
```

**Already Checked Out (400):**

```json
{
  "message": "Already checked out for today"
}
```

**Cannot Checkout on Leave (400):**

```json
{
  "message": "Cannot checkout when on leave"
}
```

---

### 3. Get Today's Attendance

**Endpoint:** `GET /attendance/{employee_id}/today`

**Description:** Retrieves today's attendance record for a specific employee.

**URL Parameters:**
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| employee_id | integer | Yes | ID of the employee |

**Success Response (200):**

```json
{
  "data": {
    "id": 123,
    "employee_id": 1,
    "date": "2025-06-04",
    "type": "full_day",
    "checkin_time": "2025-06-04T08:30:00.000000Z",
    "checkout_time": "2025-06-04T17:30:00.000000Z",
    "total_work_hours": 8.5,
    "status": "completed",
    "description": null,
    "branch_name": null
  }
}
```

**Response with Leave Info:**

```json
{
  "data": {
    "id": 123,
    "employee_id": 1,
    "date": "2025-06-04",
    "type": "full_day",
    "status": "remote_work",
    "description": "Remote work: Working from home"
  },
  "leave_info": {
    "leave_type": "remote_work",
    "request_type": "remote_work",
    "reason": "Working from home",
    "remote_work_details": {
      "location": "Home office",
      "equipment_needed": ["Laptop", "VPN access"]
    }
  }
}
```

**No Attendance but on Leave (200):**

```json
{
  "message": "Employee is on leave today",
  "leave_info": {
    "leave_type": "sick",
    "request_type": "absence",
    "reason": "Medical appointment",
    "start_date": "2025-06-04",
    "end_date": "2025-06-04"
  }
}
```

**No Record Found (404):**

```json
{
  "message": "No attendance record found for today"
}
```

---

## Admin Endpoints

### 4. Get Attendance Report

**Endpoint:** `GET /admin/attendances`

**Description:** Retrieves attendance report for a specific date with filtering options. Admin access required.

**Query Parameters:**
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| date | string (Y-m-d) | Yes | Date to get report for |
| type | string | No | Filter by attendance type (full_day, half_day) |
| branch_name | string | No | Filter by branch name |
| include_leave | boolean | No | Include employees on leave (accepts: true, false, 1, 0) |

**Example Request:**

```
GET /admin/attendances?date=2025-06-04&include_leave=true&type=full_day
```

**Success Response (200):**

```json
[
  {
    "employee_id": 1,
    "employee_name": "John Doe",
    "checkin_time": "08:30",
    "checkout_time": "17:30",
    "total_work_hours": 8.5,
    "status": "completed",
    "status_display": "Completed",
    "branch_name": "Main Office"
  },
  {
    "employee_id": 2,
    "employee_name": "Jane Smith",
    "checkin_time": "09:00",
    "checkout_time": null,
    "total_work_hours": null,
    "status": "remote_work",
    "status_display": "Remote Work",
    "branch_name": null,
    "leave_info": {
      "leave_type": "remote_work",
      "request_type": "remote_work",
      "reason": "Working from home"
    }
  },
  {
    "employee_id": 3,
    "employee_name": "Bob Johnson",
    "checkin_time": null,
    "checkout_time": null,
    "total_work_hours": null,
    "status": "on_leave",
    "status_display": "On Leave",
    "branch_name": null,
    "leave_info": {
      "leave_type": "sick",
      "request_type": "absence",
      "reason": "Medical appointment"
    }
  }
]
```

---

### 5. Add Custom Attendance

**Endpoint:** `POST /admin/attendances/custom`

**Description:** Allows admin to manually add attendance records. Admin access required.

**Request Body:**

```json
{
  "employee_id": 1,
  "date": "2025-06-03",
  "type": "full_day",
  "checkin_time": "2025-06-03 08:30:00",
  "checkout_time": "2025-06-03 17:30:00",
  "branch_name": "Main Office",
  "description": "Manual entry for missed punch"
}
```

**Request Parameters:**
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| employee_id | integer | Yes | ID of the employee |
| date | string (Y-m-d) | Yes | Date for the attendance record |
| type | string | Yes | "full_day" or "half_day" |
| checkin_time | string (Y-m-d H:i:s) | Yes | Check-in timestamp |
| checkout_time | string (Y-m-d H:i:s) | Yes | Check-out timestamp |
| branch_name | string | Yes | Branch name |
| description | string | No | Additional description |

**Success Response (201):**

```json
{
  "message": "Custom attendance record added successfully",
  "data": {
    "id": 124,
    "employee_id": 1,
    "date": "2025-06-03",
    "type": "full_day",
    "checkin_time": "2025-06-03T08:30:00.000000Z",
    "checkout_time": "2025-06-03T17:30:00.000000Z",
    "total_work_hours": 8.5,
    "status": "completed",
    "branch_name": "Main Office",
    "description": "Manual entry for missed punch"
  }
}
```

**Success with Leave Warning (201):**

```json
{
  "message": "Custom attendance record added successfully",
  "data": {
    "id": 124,
    "employee_id": 1,
    "date": "2025-06-03",
    "type": "full_day",
    "checkin_time": "2025-06-03T08:30:00.000000Z",
    "checkout_time": "2025-06-03T17:30:00.000000Z",
    "total_work_hours": 8.5,
    "status": "completed",
    "branch_name": "Main Office"
  },
  "warning": "Employee had approved leave on this date",
  "leave_info": {
    "leave_type": "sick",
    "request_type": "absence",
    "reason": "Medical appointment"
  }
}
```

**Error Responses:**

**Attendance Already Exists (400):**

```json
{
  "message": "Attendance record already exists for this date"
}
```

---

### 6. Update Custom Attendance

**Endpoint:** `PUT /admin/attendances/custom/{id}`

**Description:** Allows admin to update existing attendance records. Admin access required.

**URL Parameters:**
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| id | integer | Yes | ID of the attendance record |

**Request Body:**

```json
{
  "type": "half_day",
  "checkin_time": "2025-06-03 09:00:00",
  "checkout_time": "2025-06-03 13:00:00",
  "branch_name": "Branch Office",
  "description": "Updated timing"
}
```

**Request Parameters (all optional):**
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| type | string | No | "full_day" or "half_day" |
| checkin_time | string (Y-m-d H:i:s) | No | Updated check-in timestamp |
| checkout_time | string (Y-m-d H:i:s) | No | Updated check-out timestamp |
| branch_name | string | No | Updated branch name |
| description | string | No | Updated description |

**Success Response (200):**

```json
{
  "message": "Attendance record updated successfully",
  "data": {
    "id": 124,
    "employee_id": 1,
    "date": "2025-06-03",
    "type": "half_day",
    "checkin_time": "2025-06-03T09:00:00.000000Z",
    "checkout_time": "2025-06-03T13:00:00.000000Z",
    "total_work_hours": 4,
    "status": "completed",
    "branch_name": "Branch Office",
    "description": "Updated timing"
  }
}
```

**Record Not Found (404):**

```json
{
  "message": "Attendance record not found"
}
```

---

## Status Display Values

The system provides user-friendly status display values:

| Status      | Display Text  | Color Code |
| ----------- | ------------- | ---------- |
| completed   | "Completed"   | Green      |
| incomplete  | "In Progress" | Orange     |
| on_leave    | "On Leave"    | Blue       |
| remote_work | "Remote Work" | Purple     |

---

## Integration Notes

### Leave System Integration

The attendance system automatically integrates with the leave management system:

1. **Automatic Leave Detection**: When checking in, the system checks for approved leave and sets status accordingly
2. **Leave Status**: Attendance records show `on_leave` or `remote_work` status when applicable
3. **Leave Information**: API responses include leave details when employee is on leave

### Work Hour Calculation

- **Standard Hours**: Based on employee's `standard_work_hours_per_day` (default: 8 hours)
- **Overtime**: Hours worked beyond standard hours are tracked
- **Saturday Work**: Automatically detected for bonus calculations
- **Holiday Work**: Integrated with public holiday system

### Branch Tracking

- Branch information is optional for regular check-ins
- Required for admin-created custom attendance records
- Used for location-based reporting

---

## Frontend Implementation Examples

### Check-in Flow

```javascript
// Check-in function
async function checkIn(employeeId, type = "full_day") {
  try {
    const response = await fetch("/api/attendance/checkin", {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
        Authorization: `Bearer ${token}`,
      },
      body: JSON.stringify({
        employee_id: employeeId,
        type: type,
      }),
    });

    const data = await response.json();

    if (response.ok) {
      // Handle successful check-in
      if (data.remote_work_info) {
        // Show remote work notification
        showNotification("Checked in for remote work", "info");
      } else if (data.leave_info) {
        // Show leave notification
        showNotification("You are on approved leave today", "info");
      } else {
        // Normal check-in
        showNotification("Check-in successful", "success");
      }

      updateAttendanceDisplay(data.data);
    } else {
      // Handle errors
      showNotification(data.message, "error");
    }
  } catch (error) {
    console.error("Check-in failed:", error);
    showNotification("Check-in failed", "error");
  }
}
```

### Get Today's Status

```javascript
// Get current attendance status
async function getTodayAttendance(employeeId) {
  try {
    const response = await fetch(`/api/attendance/${employeeId}/today`, {
      headers: {
        Authorization: `Bearer ${token}`,
      },
    });

    const data = await response.json();

    if (response.ok) {
      if (data.data) {
        // Has attendance record
        updateAttendanceUI(data.data);

        if (data.leave_info) {
          // Show leave information
          showLeaveInfo(data.leave_info);
        }
      } else if (data.leave_info) {
        // On leave but no attendance record
        showLeaveStatus(data.leave_info);
      }
    } else if (response.status === 404) {
      // No record found - show check-in option
      showCheckInButton();
    }
  } catch (error) {
    console.error("Failed to get attendance:", error);
  }
}
```

### Admin Report View

```javascript
// Get attendance report for admin
async function getAttendanceReport(date, filters = {}) {
  const params = new URLSearchParams({
    date: date,
    include_leave: true,
    ...filters,
  });

  try {
    const response = await fetch(`/api/admin/attendances?${params}`, {
      headers: {
        Authorization: `Bearer ${token}`,
      },
    });

    const data = await response.json();

    if (response.ok) {
      renderAttendanceTable(data);
    } else {
      showError("Failed to load attendance report");
    }
  } catch (error) {
    console.error("Failed to get report:", error);
  }
}
```

---

## Error Handling

### Common HTTP Status Codes

- `200` - Success
- `201` - Created (for custom attendance)
- `400` - Bad Request (validation errors, already checked in/out)
- `401` - Unauthorized (invalid token)
- `403` - Forbidden (insufficient permissions)
- `404` - Not Found (no attendance record, employee not found)
- `422` - Unprocessable Entity (validation errors)
- `500` - Internal Server Error

### Recommended Error Handling

```javascript
// Generic error handler
function handleAttendanceError(response, data) {
  switch (response.status) {
    case 400:
      showNotification(data.message, "warning");
      break;
    case 401:
      redirectToLogin();
      break;
    case 403:
      showNotification("Access denied", "error");
      break;
    case 404:
      showNotification("Record not found", "info");
      break;
    case 422:
      showValidationErrors(data.errors);
      break;
    default:
      showNotification("An error occurred", "error");
  }
}
```

---

## Rate Limiting

- Standard rate limits apply to all endpoints
- Check-in/check-out operations are limited to prevent abuse
- Admin endpoints may have higher limits

## Security Considerations

- All endpoints require authentication
- Employee ID validation prevents unauthorized access
- Admin endpoints require appropriate role permissions
- Sensitive employee data is protected

This documentation provides complete guidance for frontend implementation of the attendance system with all necessary endpoints, request/response formats, and integration examples.
