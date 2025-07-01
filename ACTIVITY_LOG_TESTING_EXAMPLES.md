# Activity Log API Testing Examples

## Quick Test Examples

Use these examples to test the fixed Activity Log API functionality.

### 1. Basic List Request

```bash
curl -X GET "http://your-domain/api/activity-log/" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Accept: application/json"
```

### 2. Filtered List with Date Range

```bash
curl -X GET "http://your-domain/api/activity-log/?date_from=2024-01-01&date_to=2024-01-31&pageSize=20" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Accept: application/json"
```

### 3. Search with Group Filter

```bash
curl -X GET "http://your-domain/api/activity-log/?search=john&group_action=attendance_management&sort_field=created_at&sort_direction=desc" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Accept: application/json"
```

### 4. Get Statistics

```bash
curl -X GET "http://your-domain/api/activity-log/stats?date_from=2024-01-01&date_to=2024-01-31" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Accept: application/json"
```

### 5. Get Available Filters

```bash
curl -X GET "http://your-domain/api/activity-log/filters" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Accept: application/json"
```

### 6. Export as CSV

```bash
curl -X GET "http://your-domain/api/activity-log/export?format=csv&limit=1000&date_from=2024-01-01" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  --output activity_log.csv
```

### 7. Export as JSON

```bash
curl -X GET "http://your-domain/api/activity-log/export?format=json&limit=500" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Accept: application/json"
```

## JavaScript Testing

### Using Fetch API

```javascript
// Test basic listing
const testBasicList = async () => {
  try {
    const response = await fetch("/api/activity-log/", {
      headers: {
        Authorization: "Bearer " + localStorage.getItem("token"),
        Accept: "application/json",
      },
    });

    const data = await response.json();
    console.log("Basic list test:", data);
  } catch (error) {
    console.error("Test failed:", error);
  }
};

// Test with filters
const testFilters = async () => {
  const params = new URLSearchParams({
    date_from: "2024-01-01",
    date_to: "2024-01-31",
    group_action: "site_management",
    pageSize: 10,
    sort_field: "created_at",
    sort_direction: "desc",
  });

  try {
    const response = await fetch(`/api/activity-log/?${params}`, {
      headers: {
        Authorization: "Bearer " + localStorage.getItem("token"),
        Accept: "application/json",
      },
    });

    const data = await response.json();
    console.log("Filtered list test:", data);
  } catch (error) {
    console.error("Filter test failed:", error);
  }
};

// Test statistics
const testStats = async () => {
  try {
    const response = await fetch(
      "/api/activity-log/stats?date_from=2024-01-01&date_to=2024-01-31",
      {
        headers: {
          Authorization: "Bearer " + localStorage.getItem("token"),
          Accept: "application/json",
        },
      }
    );

    const data = await response.json();
    console.log("Stats test:", data);
  } catch (error) {
    console.error("Stats test failed:", error);
  }
};

// Run all tests
testBasicList();
testFilters();
testStats();
```

## Expected Improvements

After the fixes, you should see:

1. ✅ **Better date filtering** - No conflicts between single date and date range
2. ✅ **Input validation** - Proper error messages for invalid parameters
3. ✅ **Improved performance** - Optimized queries and limited page sizes
4. ✅ **Enhanced search** - Search now includes JSON details
5. ✅ **Proper sorting** - Sort by created_at, action, or user_id
6. ✅ **Better pagination** - More pagination info included
7. ✅ **CSV export** - Actual CSV file download functionality
8. ✅ **Error handling** - Structured error responses with validation details
9. ✅ **Complete action mappings** - All ActivityAction constants included

## Common Filter Combinations

### View today's attendance activities

```
?date=2024-01-15&group_action=attendance_management
```

### View last week's site management

```
?date_from=2024-01-08&date_to=2024-01-14&group_action=site_management
```

### Search for specific user activities

```
?search=john@example.com&pageSize=50
```

### View activities by multiple users

```
?user_id=123,456,789&sort_field=created_at&sort_direction=desc
```

### Export filtered data

```
?date_from=2024-01-01&date_to=2024-01-31&group_action=page_management&format=csv&limit=2000
```
