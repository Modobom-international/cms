# SeaweedFS Frontend Integration Guide

This guide shows how to integrate with the Laravel SeaweedFS backend API from your frontend application.

## API Endpoints Overview

All endpoints require authentication via `Authorization: Bearer {token}` header.

### Base URL: `/api/seaweedfs/files`

- **Health Check**: `GET /health`
- **Generate Upload URL**: `POST /upload-url`
- **Complete Upload**: `POST /{fileId}/complete`
- **Generate Download URL**: `POST /{fileId}/download-url`
- **Generate Stream URL**: `POST /{fileId}/stream-url`
- **List Files**: `GET /`
- **Share File**: `POST /{fileId}/share`
- **Delete File**: `DELETE /{fileId}`

## JavaScript Integration Examples

### 1. Upload File Flow

```javascript
class SeaweedFSClient {
    constructor(baseUrl, token) {
        this.baseUrl = baseUrl;
        this.token = token;
    }

    async uploadFile(file, options = {}) {
        try {
            // Step 1: Get presigned upload URL
            const uploadResponse = await this.generateUploadUrl({
                filename: file.name,
                mime_type: file.type,
                visibility: options.visibility || 'private',
                expires_at: options.expiresAt,
                metadata: options.metadata || {}
            });

            if (!uploadResponse.success) {
                throw new Error(uploadResponse.message);
            }

            const { upload_url, file_id, headers } = uploadResponse.data;

            // Step 2: Upload file directly to SeaweedFS
            const uploadResult = await fetch(upload_url, {
                method: 'PUT',
                body: file,
                headers: {
                    'Content-Type': file.type,
                    ...headers
                }
            });

            if (!uploadResult.ok) {
                throw new Error('Failed to upload file to SeaweedFS');
            }

            // Step 3: Complete upload and update metadata
            const completeResponse = await this.completeUpload(file_id, file.size);

            return {
                success: true,
                file: completeResponse.data.file
            };

        } catch (error) {
            console.error('Upload failed:', error);
            return {
                success: false,
                error: error.message
            };
        }
    }

    async generateUploadUrl(params) {
        const response = await fetch(`${this.baseUrl}/seaweedfs/files/upload-url`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${this.token}`
            },
            body: JSON.stringify(params)
        });

        return await response.json();
    }

    async completeUpload(fileId, fileSize) {
        const response = await fetch(`${this.baseUrl}/seaweedfs/files/${fileId}/complete`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${this.token}`
            },
            body: JSON.stringify({ file_size: fileSize })
        });

        return await response.json();
    }
}
```

### 2. Download File Flow

```javascript
class SeaweedFSClient {
    // ... previous methods ...

    async downloadFile(fileId, options = {}) {
        try {
            // Get presigned download URL
            const response = await fetch(`${this.baseUrl}/seaweedfs/files/${fileId}/download-url`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': `Bearer ${this.token}`
                },
                body: JSON.stringify({
                    expires_in_minutes: options.expiresInMinutes || 60
                })
            });

            const result = await response.json();

            if (!result.success) {
                throw new Error(result.message);
            }

            // Option 1: Direct download (triggers browser download)
            window.location.href = result.data.download_url;

            // Option 2: Fetch and process the file
            // const fileResponse = await fetch(result.data.download_url);
            // const blob = await fileResponse.blob();
            // return blob;

            return result.data;

        } catch (error) {
            console.error('Download failed:', error);
            throw error;
        }
    }
}
```

## React Component Example

```jsx
import React, { useState } from 'react';

const FileUpload = ({ onUploadComplete, token, apiBaseUrl }) => {
    const [uploading, setUploading] = useState(false);

    const client = new SeaweedFSClient(apiBaseUrl, token);

    const handleFileUpload = async (event) => {
        const file = event.target.files[0];
        if (!file) return;

        setUploading(true);

        try {
            const result = await client.uploadFile(file, {
                visibility: 'private',
                metadata: {
                    uploadedBy: 'user',
                    category: 'document'
                }
            });

            if (result.success) {
                onUploadComplete(result.file);
                alert('File uploaded successfully!');
            } else {
                throw new Error(result.error);
            }
        } catch (error) {
            alert(`Upload failed: ${error.message}`);
        } finally {
            setUploading(false);
        }
    };

    return (
        <div className="file-upload">
            <input
                type="file"
                onChange={handleFileUpload}
                disabled={uploading}
                accept="*/*"
            />
            {uploading && <div>Uploading...</div>}
        </div>
    );
};
```

## Setup Instructions

1. **Start SeaweedFS**: `docker-compose -f seaweedfs.compose.yml up -d`
2. **Set Environment Variables**: Copy values from `seaweedfs.env` to your `.env`
3. **Run Migrations**: `php artisan migrate`
4. **Test Health Check**: `GET /api/seaweedfs/files/health`

## Environment Variables

Add these to your `.env` file:

```env
# SeaweedFS Configuration
SEAWEEDFS_S3_ACCESS_KEY=seaweedfs_access_key_123
SEAWEEDFS_S3_SECRET_KEY=seaweedfs_secret_key_456
SEAWEEDFS_S3_REGION=us-east-1
SEAWEEDFS_S3_ENDPOINT=http://localhost:8333
SEAWEEDFS_DEFAULT_BUCKET=files
```

This setup provides secure file storage with access control, where all uploads/downloads go through presigned URLs generated by your Laravel backend, ensuring proper authentication and authorization while allowing direct client-to-SeaweedFS transfers for optimal performance. 