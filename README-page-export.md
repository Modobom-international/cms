# Page Export API Documentation

This document provides information about the Page Export API feature in the CMS platform.

## Features

The Page Export API allows:

- Frontend to submit requests to export pages by their slugs
- Backend to queue export jobs for processing
- Only one export request is active at a time

## Export Workflow

1. Frontend submits a request to export specific pages by their slugs
2. Backend clears any existing export requests
3. Backend validates and stores the new export request
4. Backend dispatches a job to the queue for processing
5. The job runs the exporter, which fetches the latest export request and processes it

## Automatic Export Process

When a new export request is created, the system:

1. Truncates the page_exports table to ensure only one export is active at a time
2. Validates the request and creates an export record in the database
3. Dispatches a job to the queue that will run the exporter
4. The exporter handles the process of retrieving the pages and generating the exported content

## Single Export Model

The system is designed to handle one export at a time. When a new export request is submitted:

- All previous export requests are removed from the database
- Only the most recent export request is stored and processed
- This ensures that the exporter always processes the most recent request

## Queue-based Processing

The export process uses Laravel's queue system for improved reliability:

- Export jobs run in the background without blocking the web request
- Jobs can be retried automatically if they fail
- Job status and logs are available in the Laravel log files
- The queue can be monitored and managed through Laravel's queue commands

## API Endpoints

### 1. Get Single Page by Slug

**Endpoint:** `GET /api/page/{slug}`

**Description:** Retrieves a single page by its slug.

**Parameters:**

- `slug` (path parameter): The unique slug identifier for the page

**Response:**

```json
{
  "success": true,
  "message": "Page found",
  "data": {
    "id": 1,
    "site_id": 1,
    "name": "Homepage",
    "slug": "home",
    "content": "...",
    "provider": 1,
    "created_at": "2023-01-01T00:00:00.000000Z",
    "updated_at": "2023-01-01T00:00:00.000000Z"
  }
}
```

**Error Response (404):**

```json
{
  "success": false,
  "message": "Page not found"
}
```

### 2. Request Page Export

**Endpoint:** `POST /api/export-pages`

**Description:** Creates a new request to export pages by their slugs and queues the export job.

**Request Body:**

```json
{
  "slugs": ["home", "about", "contact"]
}
```

**Parameters:**

- `slugs` (array): An array of page slugs to export

**Response:**

```json
{
  "success": true,
  "message": "Export process queued",
  "data": {
    "export_id": 1,
    "requested_slugs": ["home", "about", "contact"]
  }
}
```

**Error Response (404):**

```json
{
  "success": false,
  "message": "No pages found with the provided slugs"
}
```

**Validation Error Response (422):**

```json
{
  "message": "The slugs field is required.",
  "errors": {
    "slugs": ["The slugs field is required."]
  }
}
```

### 3. Get Latest Export (For Exporter Service)

**Endpoint:** `GET /api/pending-exports`

**Description:** Retrieves the latest export request for the exporter service to process.

**Response:**

```json
{
  "success": true,
  "message": "Latest export retrieved",
  "data": {
    "id": 1,
    "requested_slugs": ["home", "about", "contact"],
    "created_at": "2023-01-01T11:55:00.000000Z",
    "updated_at": "2023-01-01T11:55:00.000000Z"
  }
}
```

### 4. Cancel Export

**Endpoint:** `POST /api/cancel-export`

**Description:** Cancels any pending exports by clearing the export database.

**Response:**

```json
{
  "success": true,
  "message": "Export cancelled, any running jobs will complete but future ones are cancelled"
}
```

## Exporter Implementation Requirements

The exporter code in the `/exporter` directory should:

1. Fetch the latest export request from the API
2. Fetch the pages to export from the API
3. Generate the exported files

## Usage Examples

### Frontend Usage (JavaScript)

```javascript
// Request a page export (automatically queues the export job)
async function requestPageExport(slugs) {
  const response = await fetch("/api/export-pages", {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
    },
    body: JSON.stringify({ slugs }),
  });
  const data = await response.json();
  if (data.success) {
    return data.data;
  }
  throw new Error(data.message);
}

// Cancel an export
async function cancelExport() {
  const response = await fetch("/api/cancel-export", {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
    },
  });
  const data = await response.json();
  if (data.success) {
    return true;
  }
  throw new Error(data.message);
}
```

## Database Schema

The page export requests are stored in the `page_exports` table with the following structure:

- `id`: Auto-incrementing primary key
- `slugs`: JSON array of page slugs to export
- `created_at`: When the export request was created
- `updated_at`: When the export request was last updated

Since only one export request is active at a time, status tracking fields have been removed for simplicity.

## Queue Configuration

To ensure the export queue is running, you need to:

1. Set up Laravel queue worker:

   ```
   php artisan queue:work
   ```

2. For production, set up a supervisor configuration to keep the queue worker running:
   ```
   [program:laravel-queue]
   process_name=%(program_name)s_%(process_num)02d
   command=php /path/to/your/project/artisan queue:work --sleep=3 --tries=3
   autostart=true
   autorestart=true
   user=www-data
   numprocs=2
   redirect_stderr=true
   stdout_logfile=/path/to/your/project/storage/logs/worker.log
   ```
