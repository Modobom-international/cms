# Attendance Complaints API Documentation

## Overview

The Attendance Complaints feature allows employees to file complaints about their attendance records and enables administrators to review and respond to these complaints.

## Employee Endpoints

### 1. Create a Complaint

**POST** `/api/attendance-complaints`

Create a new complaint for an attendance record.

**Request Body:**

```json
{
  "attendance_id": 123,
  "complaint_type": "incorrect_time",
  "description": "The check-in time is wrong, I actually arrived at 8:00 AM",
  "proposed_changes": {
    "checkin_time": "2025-06-04 08:00:00",
    "reason": "GPS tracking issue"
  }
}
```

**Complaint Types:**

- `incorrect_time` - Wrong check-in/check-out time
- `missing_record` - Missing attendance record
- `technical_issue` - System or technical problems
- `other` - Other issues

### 2. Get Own Complaints

**GET** `/api/attendance-complaints`

Get a list of own complaints with optional filtering.

**Query Parameters:**

- `status` (optional): `pending`, `under_review`, `resolved`, `rejected`
- `per_page` (optional): Number of items per page (1-100, default: 15)

### 3. Get Specific Complaint

**GET** `/api/attendance-complaints/{id}`

Get details of a specific complaint (only own complaints).

### 4. Update Complaint

**PUT** `/api/attendance-complaints/{id}`

Update a complaint (only if status is `pending`).

**Request Body:**

```json
{
  "complaint_type": "technical_issue",
  "description": "Updated description",
  "proposed_changes": {
    "checkout_time": "2025-06-04 17:30:00"
  }
}
```

## Admin Endpoints

### 1. Get All Complaints

**GET** `/api/admin/attendance-complaints`

Get all complaints with filtering options.

**Query Parameters:**

- `status` (optional): Filter by status
- `employee_id` (optional): Filter by employee
- `complaint_type` (optional): Filter by complaint type
- `per_page` (optional): Items per page

### 2. Get Complaint Statistics

**GET** `/api/admin/attendance-complaints/statistics`

Get complaint statistics and counts by status and type.

**Response:**

```json
{
  "total": 50,
  "pending": 10,
  "under_review": 5,
  "resolved": 30,
  "rejected": 5,
  "by_type": {
    "incorrect_time": 25,
    "missing_record": 10,
    "technical_issue": 8,
    "other": 7
  }
}
```

### 3. Get Specific Complaint

**GET** `/api/admin/attendance-complaints/{id}`

Get details of any complaint.

### 4. Update Complaint Status

**PUT** `/api/admin/attendance-complaints/{id}/status`

Update the status of a complaint.

**Request Body:**

```json
{
  "status": "resolved",
  "admin_response": "The attendance record has been corrected as requested."
}
```

**Available Status Updates:**

- `under_review` - Mark as under review
- `resolved` - Mark as resolved (requires admin_response)
- `rejected` - Mark as rejected (requires admin_response)

## Status Flow

1. **pending** - Initial status when complaint is created
2. **under_review** - Admin has started reviewing
3. **resolved** - Admin has resolved the complaint
4. **rejected** - Admin has rejected the complaint

## Response Format

All endpoints return JSON responses with the following structure:

**Success Response:**

```json
{
  "message": "Success message",
  "data": {
    /* response data */
  }
}
```

**Error Response:**

```json
{
  "message": "Error message"
}
```

**Paginated Response:**

```json
{
  "current_page": 1,
  "data": [
    /* array of items */
  ],
  "total": 50,
  "per_page": 15,
  "last_page": 4
}
```

## Authentication

All endpoints require authentication using Laravel Sanctum tokens. Include the token in the Authorization header:

```
Authorization: Bearer your-auth-token
```

## Examples

### Create a Complaint

```bash
curl -X POST /api/attendance-complaints \
  -H "Authorization: Bearer your-token" \
  -H "Content-Type: application/json" \
  -d '{
    "attendance_id": 123,
    "complaint_type": "incorrect_time",
    "description": "Check-in time is incorrect",
    "proposed_changes": {
      "checkin_time": "2025-06-04 08:00:00"
    }
  }'
```

### Admin Review Complaint

```bash
curl -X PUT /api/admin/attendance-complaints/1/status \
  -H "Authorization: Bearer admin-token" \
  -H "Content-Type: application/json" \
  -d '{
    "status": "resolved",
    "admin_response": "Attendance record has been corrected."
  }'
```
