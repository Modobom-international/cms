# Activity Log API Documentation

## Overview

The Activity Log API provides comprehensive functionality for viewing, filtering, exporting, and analyzing user activity logs within the system. This documentation covers all available endpoints, parameters, response formats, and implementation examples.

**Base URL**: `/api/activity-log`

## Authentication

All endpoints require authentication via Bearer token in the Authorization header:

```
Authorization: Bearer {your_token}
```

## Endpoints

### 1. List Activity Logs

**Endpoint**: `GET /api/activity-log/`

Retrieves a paginated list of activity logs with comprehensive filtering options.

#### Query Parameters

| Parameter        | Type          | Required | Default    | Description                                    |
| ---------------- | ------------- | -------- | ---------- | ---------------------------------------------- |
| `date`           | string        | No       | -          | Single date filter (YYYY-MM-DD format)         |
| `date_from`      | string        | No       | -          | Start date for date range filter (YYYY-MM-DD)  |
| `date_to`        | string        | No       | -          | End date for date range filter (YYYY-MM-DD)    |
| `user_id`        | integer/array | No       | -          | Filter by specific user ID(s)                  |
| `action`         | string/array  | No       | -          | Filter by specific action(s)                   |
| `group_action`   | string/array  | No       | -          | Filter by action group(s)                      |
| `search`         | string        | No       | -          | Search in description, action, user email/name |
| `page`           | integer       | No       | 1          | Page number                                    |
| `pageSize`       | integer       | No       | 20         | Records per page (max: 100)                    |
| `sort_field`     | string        | No       | created_at | Sort field (created_at, action, user_id)       |
| `sort_direction` | string        | No       | desc       | Sort direction (asc, desc)                     |

#### Available Action Groups

- `site_management` - Site management operations
- `page_management` - Page management operations
- `attendance_management` - Attendance operations
- `attendance_complaints` - Attendance complaint operations
- `board_management` - Board management operations
- `cloudflare_operations` - Cloudflare operations
- `domain_operations` - Domain operations
- `general_operations` - General system operations

#### Example Requests

**Basic request:**

```javascript
fetch("/api/activity-log/", {
  headers: {
    Authorization: "Bearer " + token,
    Accept: "application/json",
  },
});
```

**Filtered request:**

```javascript
const params = new URLSearchParams({
  date_from: "2024-01-01",
  date_to: "2024-01-31",
  group_action: "attendance_management",
  pageSize: 50,
  sort_field: "created_at",
  sort_direction: "desc",
});

fetch(`/api/activity-log/?${params}`, {
  headers: {
    Authorization: "Bearer " + token,
    Accept: "application/json",
  },
});
```

**Search request:**

```javascript
const params = new URLSearchParams({
  search: "john@example.com",
  pageSize: 20,
});

fetch(`/api/activity-log/?${params}`, {
  headers: {
    Authorization: "Bearer " + token,
    Accept: "application/json",
  },
});
```

#### Success Response (200)

```json
{
  "success": true,
  "data": {
    "activities": [
      {
        "id": 1,
        "action": "create_site",
        "action_label": "Tạo site",
        "group_action": "site_management",
        "description": "Tạo site mới với domain example.com",
        "user_id": 123,
        "user_email": "user@example.com",
        "user_name": "John Doe",
        "formatted_created_at": "2024-01-15 10:30:00",
        "formatted_details": {
          "site_id": 456,
          "domain": "example.com"
        }
      }
    ],
    "pagination": {
      "current_page": 1,
      "per_page": 20,
      "total": 150,
      "last_page": 8,
      "from": 1,
      "to": 20,
      "has_more_pages": true,
      "on_first_page": true
    },
    "filters_applied": {
      "date_from": "2024-01-01",
      "date_to": "2024-01-31",
      "group_action": "attendance_management"
    },
    "sort": {
      "field": "created_at",
      "direction": "desc"
    }
  },
  "message": "Lấy danh sách activity log thành công",
  "type": "list_activity_log_success"
}
```

#### Error Responses

**Validation Error (422):**

```json
{
  "success": false,
  "message": "Dữ liệu đầu vào không hợp lệ",
  "type": "list_activity_log_validation_error",
  "errors": {
    "date_from": ["The date from field must be a valid date."],
    "pageSize": ["The page size may not be greater than 100."]
  }
}
```

**Server Error (500):**

```json
{
  "success": false,
  "message": "Lấy danh sách activity log không thành công",
  "type": "list_activity_log_fail",
  "error": "Internal server error message"
}
```

---

### 2. Get Activity Statistics

**Endpoint**: `GET /api/activity-log/stats`

Retrieves statistical information about activity logs.

#### Query Parameters

| Parameter   | Type          | Required | Default | Description                            |
| ----------- | ------------- | -------- | ------- | -------------------------------------- |
| `date`      | string        | No       | -       | Single date filter (YYYY-MM-DD format) |
| `date_from` | string        | No       | -       | Start date for date range filter       |
| `date_to`   | string        | No       | -       | End date for date range filter         |
| `user_id`   | integer/array | No       | -       | Filter by specific user ID(s)          |

#### Example Request

```javascript
const params = new URLSearchParams({
  date_from: "2024-01-01",
  date_to: "2024-01-31",
});

fetch(`/api/activity-log/stats?${params}`, {
  headers: {
    Authorization: "Bearer " + token,
    Accept: "application/json",
  },
});
```

#### Success Response (200)

```json
{
  "success": true,
  "data": {
    "total_activities": 1250,
    "actions_by_group": {
      "site_management": 45,
      "page_management": 320,
      "attendance_management": 580,
      "general_operations": 305
    },
    "top_users": [
      {
        "user_id": 123,
        "email": "user1@example.com",
        "name": "John Doe",
        "count": 85
      },
      {
        "user_id": 456,
        "email": "user2@example.com",
        "name": "Jane Smith",
        "count": 72
      }
    ],
    "daily_activities": [
      {
        "date": "2024-01-31",
        "count": 45
      },
      {
        "date": "2024-01-30",
        "count": 52
      }
    ],
    "filters_applied": {
      "date_from": "2024-01-01",
      "date_to": "2024-01-31"
    }
  },
  "message": "Lấy thống kê activity log thành công",
  "type": "get_activity_stats_success"
}
```

---

### 3. Get Available Filters

**Endpoint**: `GET /api/activity-log/filters`

Retrieves all available filter options for the front-end to build filter interfaces.

#### Example Request

```javascript
fetch("/api/activity-log/filters", {
  headers: {
    Authorization: "Bearer " + token,
    Accept: "application/json",
  },
});
```

#### Success Response (200)

```json
{
  "success": true,
  "data": {
    "group_actions": [
      {
        "value": "site_management",
        "label": "Quản lý Site",
        "description": "Các hoạt động liên quan đến quản lý site"
      },
      {
        "value": "page_management",
        "label": "Quản lý Trang",
        "description": "Các hoạt động liên quan đến quản lý trang"
      }
    ],
    "date_filters": [
      {
        "value": "today",
        "label": "Hôm nay",
        "date": "2024-01-15"
      },
      {
        "value": "this_week",
        "label": "Tuần này",
        "date_from": "2024-01-08",
        "date_to": "2024-01-14"
      }
    ],
    "sort_options": [
      {
        "value": "created_at_desc",
        "label": "Thời gian tạo (Mới nhất)",
        "field": "created_at",
        "direction": "desc"
      },
      {
        "value": "action_asc",
        "label": "Hành động (A-Z)",
        "field": "action",
        "direction": "asc"
      }
    ],
    "filter_options": {
      "page_sizes": [10, 20, 50, 100],
      "max_page_size": 100,
      "default_page_size": 20
    }
  },
  "message": "Lấy danh sách bộ lọc thành công",
  "type": "get_available_filters_success"
}
```

---

### 4. Export Activity Logs

**Endpoint**: `GET /api/activity-log/export`

Exports activity logs in JSON or CSV format.

#### Query Parameters

All filtering parameters from the list endpoint plus:

| Parameter | Type    | Required | Default | Description                       |
| --------- | ------- | -------- | ------- | --------------------------------- |
| `format`  | string  | No       | json    | Export format (json, csv)         |
| `limit`   | integer | No       | 1000    | Max records to export (max: 5000) |

#### Example Requests

**JSON Export:**

```javascript
const params = new URLSearchParams({
  date_from: "2024-01-01",
  date_to: "2024-01-31",
  group_action: "site_management",
  format: "json",
  limit: 500,
});

fetch(`/api/activity-log/export?${params}`, {
  headers: {
    Authorization: "Bearer " + token,
    Accept: "application/json",
  },
});
```

**CSV Export:**

```javascript
const params = new URLSearchParams({
  date_from: "2024-01-01",
  date_to: "2024-01-31",
  format: "csv",
  limit: 1000,
});

fetch(`/api/activity-log/export?${params}`, {
  headers: {
    Authorization: "Bearer " + token,
  },
})
  .then((response) => response.blob())
  .then((blob) => {
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement("a");
    a.href = url;
    a.download = "activity_log.csv";
    a.click();
  });
```

#### Success Responses

**JSON Export (200):**

```json
{
  "success": true,
  "data": {
    "export_data": [
      {
        "id": 1,
        "action": "create_site",
        "action_label": "Tạo site",
        "group_action": "site_management",
        "description": "Tạo site mới",
        "user_id": 123,
        "user_email": "user@example.com",
        "user_name": "John Doe",
        "created_at": "2024-01-15 10:30:00",
        "details": {
          "site_id": 456
        }
      }
    ],
    "total_records": 250,
    "export_format": "json",
    "export_limit": 500,
    "generated_at": "2024-01-15 15:30:00",
    "filters_applied": {
      "date_from": "2024-01-01",
      "date_to": "2024-01-31"
    }
  },
  "message": "Xuất activity log thành công",
  "type": "export_activity_log_success"
}
```

**CSV Export (200):**

```
Content-Type: text/csv
Content-Disposition: attachment; filename="activity_log_2024-01-15_15-30-00.csv"

ID,Hành động,Nhãn hành động,Nhóm hành động,Mô tả,ID người dùng,Email người dùng,Tên người dùng,Thời gian tạo,Chi tiết
1,"create_site","Tạo site","site_management","Tạo site mới",123,"user@example.com","John Doe","2024-01-15 10:30:00","{\"site_id\":456}"
```

## Implementation Examples

### React Hook for Activity Logs

```javascript
import { useState, useEffect } from "react";

const useActivityLogs = () => {
  const [logs, setLogs] = useState([]);
  const [loading, setLoading] = useState(false);
  const [pagination, setPagination] = useState({});
  const [filters, setFilters] = useState({});

  const fetchLogs = async (newFilters = {}) => {
    setLoading(true);
    try {
      const params = new URLSearchParams({
        ...filters,
        ...newFilters,
      });

      const response = await fetch(`/api/activity-log/?${params}`, {
        headers: {
          Authorization: `Bearer ${localStorage.getItem("token")}`,
          Accept: "application/json",
        },
      });

      const data = await response.json();

      if (data.success) {
        setLogs(data.data.activities);
        setPagination(data.data.pagination);
        setFilters(data.data.filters_applied);
      }
    } catch (error) {
      console.error("Error fetching activity logs:", error);
    } finally {
      setLoading(false);
    }
  };

  const exportLogs = async (format = "csv") => {
    try {
      const params = new URLSearchParams({
        ...filters,
        format,
        limit: 5000,
      });

      const response = await fetch(`/api/activity-log/export?${params}`, {
        headers: {
          Authorization: `Bearer ${localStorage.getItem("token")}`,
        },
      });

      if (format === "csv") {
        const blob = await response.blob();
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement("a");
        a.href = url;
        a.download = `activity_log_${
          new Date().toISOString().split("T")[0]
        }.csv`;
        a.click();
      } else {
        const data = await response.json();
        return data.data.export_data;
      }
    } catch (error) {
      console.error("Error exporting logs:", error);
    }
  };

  return {
    logs,
    loading,
    pagination,
    filters,
    fetchLogs,
    exportLogs,
  };
};

export default useActivityLogs;
```

### Vue.js Composition API Example

```javascript
import { ref, reactive } from "vue";

export function useActivityLogs() {
  const logs = ref([]);
  const loading = ref(false);
  const pagination = reactive({});
  const filters = reactive({});

  const fetchLogs = async (newFilters = {}) => {
    loading.value = true;
    try {
      const params = new URLSearchParams({
        ...filters,
        ...newFilters,
      });

      const response = await fetch(`/api/activity-log/?${params}`, {
        headers: {
          Authorization: `Bearer ${localStorage.getItem("token")}`,
          Accept: "application/json",
        },
      });

      const data = await response.json();

      if (data.success) {
        logs.value = data.data.activities;
        Object.assign(pagination, data.data.pagination);
        Object.assign(filters, data.data.filters_applied);
      }
    } catch (error) {
      console.error("Error fetching activity logs:", error);
    } finally {
      loading.value = false;
    }
  };

  return {
    logs,
    loading,
    pagination,
    filters,
    fetchLogs,
  };
}
```

## Best Practices

### 1. Error Handling

Always handle both validation errors (422) and server errors (500):

```javascript
const handleApiResponse = async (response) => {
  const data = await response.json();

  if (!response.ok) {
    if (response.status === 422) {
      // Handle validation errors
      console.error("Validation errors:", data.errors);
      // Show field-specific error messages
    } else if (response.status === 500) {
      // Handle server errors
      console.error("Server error:", data.error);
      // Show generic error message
    }
    throw new Error(data.message);
  }

  return data;
};
```

### 2. Pagination

Implement proper pagination controls:

```javascript
const PaginationControls = ({ pagination, onPageChange }) => {
  return (
    <div className="pagination">
      <button
        disabled={pagination.on_first_page}
        onClick={() => onPageChange(pagination.current_page - 1)}
      >
        Previous
      </button>

      <span>
        Page {pagination.current_page} of {pagination.last_page}(
        {pagination.from}-{pagination.to} of {pagination.total})
      </span>

      <button
        disabled={!pagination.has_more_pages}
        onClick={() => onPageChange(pagination.current_page + 1)}
      >
        Next
      </button>
    </div>
  );
};
```

### 3. Date Range Filtering

Use proper date validation and formatting:

```javascript
const DateRangeFilter = ({ onFilterChange }) => {
  const [dateFrom, setDateFrom] = useState("");
  const [dateTo, setDateTo] = useState("");

  const handleApplyFilter = () => {
    if (dateFrom && dateTo && dateFrom <= dateTo) {
      onFilterChange({
        date_from: dateFrom,
        date_to: dateTo,
      });
    }
  };

  return (
    <div className="date-filter">
      <input
        type="date"
        value={dateFrom}
        onChange={(e) => setDateFrom(e.target.value)}
        max={dateTo || undefined}
      />
      <input
        type="date"
        value={dateTo}
        onChange={(e) => setDateTo(e.target.value)}
        min={dateFrom || undefined}
      />
      <button onClick={handleApplyFilter}>Apply</button>
    </div>
  );
};
```

### 4. Debounced Search

Implement debounced search for better performance:

```javascript
import { useDebouncedCallback } from "use-debounce";

const SearchFilter = ({ onFilterChange }) => {
  const [searchTerm, setSearchTerm] = useState("");

  const debouncedSearch = useDebouncedCallback((term) => {
    onFilterChange({ search: term });
  }, 500);

  useEffect(() => {
    debouncedSearch(searchTerm);
  }, [searchTerm, debouncedSearch]);

  return (
    <input
      type="text"
      placeholder="Search logs..."
      value={searchTerm}
      onChange={(e) => setSearchTerm(e.target.value)}
    />
  );
};
```

## Rate Limiting

Be mindful of API rate limits:

- Implement debouncing for search inputs
- Cache results when appropriate
- Use pagination instead of loading all data at once
- Limit export sizes for performance

## Support

For technical questions or issues with the Activity Log API, please contact the backend development team or create an issue in the project repository.
