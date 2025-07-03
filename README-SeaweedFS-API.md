# SeaweedFS File Management API Documentation

This document provides comprehensive API documentation for the SeaweedFS file management system integrated with Laravel. Use this guide to test the APIs with Postman.

## 🚀 Setup Instructions

### 1. Start SeaweedFS
```bash
docker-compose -f seaweedfs.compose.yml up -d
```

### 2. Add Environment Variables to Laravel
Add these to your `.env` file:
```env
SEAWEEDFS_S3_ACCESS_KEY=seaweedfs_access_key_123
SEAWEEDFS_S3_SECRET_KEY=seaweedfs_secret_key_456
SEAWEEDFS_S3_REGION=us-east-1
SEAWEEDFS_S3_ENDPOINT=http://localhost:8333
SEAWEEDFS_DEFAULT_BUCKET=files
```

### 3. Run Migrations
```bash
php artisan migrate
```

## 🔐 Authentication

All API endpoints require authentication. Include this header in all requests:
```
Authorization: Bearer YOUR_ACCESS_TOKEN
```

To get an access token, use your existing login endpoint:
```
POST /api/login
```

## 📋 API Endpoints

### Base URL: `/api/seaweedfs/files`

---

## 1. Health Check

**Endpoint:** `GET /api/seaweedfs/files/health`

**Description:** Check if SeaweedFS is healthy and the bucket exists.

**Headers:**
```
Authorization: Bearer YOUR_ACCESS_TOKEN
Content-Type: application/json
```

**Input:** None

**Output:**
```json
{
    "success": true,
    "message": "Health check completed",
    "data": {
        "seaweedfs_healthy": true,
        "bucket_exists": true,
        "bucket_name": "files",
        "endpoint": "http://localhost:8333"
    }
}
```

---

## 2. Generate Upload URL

**Endpoint:** `POST /api/seaweedfs/files/upload-url`

**Description:** Generate a presigned URL for uploading a file directly to SeaweedFS.

**Headers:**
```
Authorization: Bearer YOUR_ACCESS_TOKEN
Content-Type: application/json
```

**Input:**
```json
{
    "filename": "document.pdf",
    "mime_type": "application/pdf",
    "visibility": "private",
    "expires_at": "2025-07-10T15:30:00Z",
    "metadata": {
        "category": "document",
        "description": "Important document"
    }
}
```

**Parameters:**
- `filename` (required): Original filename
- `mime_type` (required): MIME type of the file
- `visibility` (optional): "private", "public", or "shared" (default: "private")
- `expires_at` (optional): ISO 8601 datetime when file expires
- `metadata` (optional): Additional metadata object

**Output:**
```json
{
    "success": true,
    "message": "Upload URL generated successfully",
    "data": {
        "upload_url": "http://localhost:8333/files/1/2025/07/03/uuid-here.pdf?X-Amz-Algorithm=...",
        "file_id": 123,
        "key": "files/1/2025/07/03/uuid-here.pdf",
        "expires_at": "2025-07-03T08:15:00Z",
        "headers": {
            "Content-Type": "application/pdf"
        }
    }
}
```

### How to Upload with Postman:
1. Call this endpoint to get the `upload_url`
2. Create a new POST request to the `upload_url`
3. Set method to PUT
4. In Body tab, select "binary" and choose your file
5. Add header: `Content-Type: application/pdf` (or appropriate MIME type)

---

## 3. Complete Upload

**Endpoint:** `POST /api/seaweedfs/files/{fileId}/complete`

**Description:** Mark the upload as complete and update file metadata.

**Headers:**
```
Authorization: Bearer YOUR_ACCESS_TOKEN
Content-Type: application/json
```

**Input:**
```json
{
    "file_size": 1048576
}
```

**Parameters:**
- `file_size` (required): Size of the uploaded file in bytes

**Output:**
```json
{
    "success": true,
    "message": "File upload completed successfully",
    "data": {
        "file": {
            "id": 123,
            "name": "document",
            "original_name": "document.pdf",
            "size": 1048576,
            "human_readable_size": "1.00 MB",
            "mime_type": "application/pdf",
            "visibility": "private",
            "download_count": 0,
            "created_at": "2025-07-03T08:00:00Z",
            "last_accessed_at": null,
            "expires_at": "2025-07-10T15:30:00Z",
            "is_expired": false,
            "metadata": {
                "category": "document",
                "description": "Important document",
                "etag": "\"abc123\"",
                "completed_at": "2025-07-03T08:15:00Z"
            }
        }
    }
}
```

---

## 4. Generate Download URL

**Endpoint:** `POST /api/seaweedfs/files/{fileId}/download-url`

**Description:** Generate a presigned URL for downloading a file.

**Headers:**
```
Authorization: Bearer YOUR_ACCESS_TOKEN
Content-Type: application/json
```

**Input:**
```json
{
    "expires_in_minutes": 60
}
```

**Parameters:**
- `expires_in_minutes` (optional): URL expiration time in minutes (default: 60)

**Output:**
```json
{
    "success": true,
    "message": "Download URL generated successfully",
    "data": {
        "download_url": "http://localhost:8333/files/1/2025/07/03/uuid-here.pdf?X-Amz-Algorithm=...",
        "expires_in_minutes": 60,
        "file_info": {
            "id": 123,
            "name": "document",
            "original_name": "document.pdf",
            "size": 1048576,
            "human_readable_size": "1.00 MB",
            "mime_type": "application/pdf",
            "visibility": "private",
            "download_count": 1,
            "created_at": "2025-07-03T08:00:00Z",
            "last_accessed_at": "2025-07-03T08:20:00Z",
            "expires_at": "2025-07-10T15:30:00Z",
            "is_expired": false
        }
    }
}
```

---

## 5. Generate Stream URL

**Endpoint:** `POST /api/seaweedfs/files/{fileId}/stream-url`

**Description:** Generate a presigned URL for streaming/viewing a file inline.

**Headers:**
```
Authorization: Bearer YOUR_ACCESS_TOKEN
Content-Type: application/json
```

**Input:**
```json
{
    "expires_in_minutes": 30
}
```

**Parameters:**
- `expires_in_minutes` (optional): URL expiration time in minutes (default: 60)

**Output:**
```json
{
    "success": true,
    "message": "Stream URL generated successfully",
    "data": {
        "stream_url": "http://localhost:8333/files/1/2025/07/03/uuid-here.pdf?response-content-disposition=inline...",
        "expires_in_minutes": 30,
        "file_info": {
            "id": 123,
            "name": "document",
            "original_name": "document.pdf",
            "size": 1048576,
            "mime_type": "application/pdf",
            "visibility": "private"
        }
    }
}
```

---

## 6. List Files

**Endpoint:** `GET /api/seaweedfs/files`

**Description:** List files accessible by the authenticated user with pagination and filtering.

**Headers:**
```
Authorization: Bearer YOUR_ACCESS_TOKEN
```

**Query Parameters:**
- `visibility` (optional): Filter by visibility ("private", "public", "shared")
- `search` (optional): Search in filename
- `per_page` (optional): Items per page (default: 15, max: 100)
- `page` (optional): Page number

**Example:** `GET /api/seaweedfs/files?visibility=private&search=document&per_page=10&page=1`

**Output:**
```json
{
    "success": true,
    "message": "Files retrieved successfully",
    "data": {
        "current_page": 1,
        "data": [
            {
                "id": 123,
                "name": "document",
                "original_name": "document.pdf",
                "size": 1048576,
                "human_readable_size": "1.00 MB",
                "mime_type": "application/pdf",
                "visibility": "private",
                "download_count": 1,
                "created_at": "2025-07-03T08:00:00Z",
                "last_accessed_at": "2025-07-03T08:20:00Z",
                "expires_at": "2025-07-10T15:30:00Z",
                "is_expired": false,
                "metadata": {
                    "category": "document"
                }
            }
        ],
        "first_page_url": "http://localhost/api/seaweedfs/files?page=1",
        "from": 1,
        "last_page": 1,
        "last_page_url": "http://localhost/api/seaweedfs/files?page=1",
        "links": [...],
        "next_page_url": null,
        "path": "http://localhost/api/seaweedfs/files",
        "per_page": 15,
        "prev_page_url": null,
        "to": 1,
        "total": 1
    }
}
```

---

## 7. Get File Information

**Endpoint:** `GET /api/seaweedfs/files/{fileId}`

**Description:** Get detailed information about a specific file.

**Headers:**
```
Authorization: Bearer YOUR_ACCESS_TOKEN
```

**Input:** None (fileId in URL)

**Output:**
```json
{
    "success": true,
    "message": "File information retrieved successfully",
    "data": {
        "file": {
            "id": 123,
            "name": "document",
            "original_name": "document.pdf",
            "size": 1048576,
            "human_readable_size": "1.00 MB",
            "mime_type": "application/pdf",
            "visibility": "private",
            "download_count": 1,
            "created_at": "2025-07-03T08:00:00Z",
            "last_accessed_at": "2025-07-03T08:20:00Z",
            "expires_at": "2025-07-10T15:30:00Z",
            "is_expired": false,
            "metadata": {
                "category": "document",
                "description": "Important document",
                "etag": "\"abc123\"",
                "completed_at": "2025-07-03T08:15:00Z"
            }
        }
    }
}
```

---

## 8. Share File

**Endpoint:** `POST /api/seaweedfs/files/{fileId}/share`

**Description:** Share a file with specific users or roles.

**Headers:**
```
Authorization: Bearer YOUR_ACCESS_TOKEN
Content-Type: application/json
```

**Input:**
```json
{
    "user_ids": [2, 3, 4],
    "roles": ["admin", "editor"],
    "expires_at": "2025-07-15T15:30:00Z"
}
```

**Parameters:**
- `user_ids` (optional): Array of user IDs to share with
- `roles` (optional): Array of role names to share with
- `expires_at` (optional): ISO 8601 datetime when sharing expires

**Output:**
```json
{
    "success": true,
    "message": "File shared successfully",
    "data": {
        "file": {
            "id": 123,
            "name": "document",
            "original_name": "document.pdf",
            "visibility": "shared",
            "expires_at": "2025-07-15T15:30:00Z"
        }
    }
}
```

---

## 9. Delete File

**Endpoint:** `DELETE /api/seaweedfs/files/{fileId}`

**Description:** Delete a file from both SeaweedFS and the database.

**Headers:**
```
Authorization: Bearer YOUR_ACCESS_TOKEN
```

**Input:** None (fileId in URL)

**Output:**
```json
{
    "success": true,
    "message": "File deleted successfully"
}
```

---

## 10. Get User Statistics

**Endpoint:** `GET /api/seaweedfs/files/stats`

**Description:** Get file statistics for the authenticated user.

**Headers:**
```
Authorization: Bearer YOUR_ACCESS_TOKEN
```

**Input:** None

**Output:**
```json
{
    "success": true,
    "message": "File statistics retrieved successfully",
    "data": {
        "total_files": 15,
        "total_size": 52428800,
        "total_downloads": 42,
        "by_visibility": {
            "private": 10,
            "public": 3,
            "shared": 2
        },
        "recent_uploads": [
            {
                "id": 123,
                "name": "document",
                "original_name": "document.pdf",
                "created_at": "2025-07-03T08:00:00Z"
            }
        ]
    }
}
```

---

## 🧪 Complete Upload Flow Example

### Step 1: Generate Upload URL
```
POST /api/seaweedfs/files/upload-url
Authorization: Bearer YOUR_TOKEN
Content-Type: application/json

{
    "filename": "test-document.pdf",
    "mime_type": "application/pdf",
    "visibility": "private"
}
```

**Response:**
```json
{
    "success": true,
    "data": {
        "upload_url": "http://localhost:8333/files/1/2025/07/03/uuid.pdf?...",
        "file_id": 123,
        "headers": {
            "Content-Type": "application/pdf"
        }
    }
}
```

### Step 2: Upload File to SeaweedFS
```
PUT http://localhost:8333/files/1/2025/07/03/uuid.pdf?...
Content-Type: application/pdf
Body: [Binary file data]
```

### Step 3: Complete Upload
```
POST /api/seaweedfs/files/123/complete
Authorization: Bearer YOUR_TOKEN
Content-Type: application/json

{
    "file_size": 1048576
}
```

### Step 4: Generate Download URL
```
POST /api/seaweedfs/files/123/download-url
Authorization: Bearer YOUR_TOKEN
Content-Type: application/json

{
    "expires_in_minutes": 60
}
```

---

## ❌ Error Responses

All endpoints return consistent error responses:

### Validation Error (422)
```json
{
    "success": false,
    "message": "Validation failed",
    "errors": {
        "filename": ["The filename field is required."],
        "mime_type": ["The mime type field is required."]
    }
}
```

### Access Denied (403)
```json
{
    "success": false,
    "message": "Access denied to this file",
    "error": "You don't have permission to access this file"
}
```

### File Not Found (404)
```json
{
    "success": false,
    "message": "File not found",
    "error": "The requested file does not exist"
}
```

### Server Error (500)
```json
{
    "success": false,
    "message": "Failed to generate upload URL",
    "error": "SeaweedFS connection failed"
}
```

---

## 🔧 Postman Collection

### Environment Variables
Create a Postman environment with these variables:
- `base_url`: `http://localhost:8000/api`
- `auth_token`: `YOUR_ACCESS_TOKEN`

### Pre-request Script for Authentication
Add this to your collection or individual requests:
```javascript
pm.request.headers.add({
    key: 'Authorization',
    value: 'Bearer ' + pm.environment.get('auth_token')
});
```

---

## 🚨 Important Notes

1. **File Upload is Two-Step Process:**
   - First, get presigned URL from Laravel
   - Then, upload file directly to SeaweedFS using PUT method

2. **Authentication Required:**
   - All endpoints require valid Bearer token
   - Use your existing login system to get tokens

3. **CORS Configuration:**
   - Ensure your frontend domain is allowed in Laravel CORS config

4. **File Size Limits:**
   - Configure appropriate limits in Laravel and SeaweedFS

5. **Security:**
   - Presigned URLs expire automatically
   - Access control is enforced at Laravel level
   - Files can be private, public, or shared with specific users/roles

This API provides secure, scalable file management with direct client-to-storage uploads while maintaining proper access control through your Laravel backend. 