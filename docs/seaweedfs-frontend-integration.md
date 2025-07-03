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

    async streamFile(fileId, options = {}) {
        try {
            const response = await fetch(`${this.baseUrl}/seaweedfs/files/${fileId}/stream-url`, {
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

            return result.data.stream_url;

        } catch (error) {
            console.error('Stream URL generation failed:', error);
            throw error;
        }
    }
}
```

### 3. File Management

```javascript
class SeaweedFSClient {
    // ... previous methods ...

    async listFiles(options = {}) {
        const params = new URLSearchParams();
        
        if (options.visibility) params.append('visibility', options.visibility);
        if (options.search) params.append('search', options.search);
        if (options.perPage) params.append('per_page', options.perPage);

        const response = await fetch(`${this.baseUrl}/seaweedfs/files?${params}`, {
            headers: {
                'Authorization': `Bearer ${this.token}`
            }
        });

        return await response.json();
    }

    async getFileInfo(fileId) {
        const response = await fetch(`${this.baseUrl}/seaweedfs/files/${fileId}`, {
            headers: {
                'Authorization': `Bearer ${this.token}`
            }
        });

        return await response.json();
    }

    async shareFile(fileId, options) {
        const response = await fetch(`${this.baseUrl}/seaweedfs/files/${fileId}/share`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${this.token}`
            },
            body: JSON.stringify({
                user_ids: options.userIds || [],
                roles: options.roles || [],
                expires_at: options.expiresAt
            })
        });

        return await response.json();
    }

    async deleteFile(fileId) {
        const response = await fetch(`${this.baseUrl}/seaweedfs/files/${fileId}`, {
            method: 'DELETE',
            headers: {
                'Authorization': `Bearer ${this.token}`
            }
        });

        return await response.json();
    }

    async getStats() {
        const response = await fetch(`${this.baseUrl}/seaweedfs/files/stats`, {
            headers: {
                'Authorization': `Bearer ${this.token}`
            }
        });

        return await response.json();
    }

    async healthCheck() {
        const response = await fetch(`${this.baseUrl}/seaweedfs/files/health`, {
            headers: {
                'Authorization': `Bearer ${this.token}`
            }
        });

        return await response.json();
    }
}
```

## React Component Examples

### File Upload Component

```jsx
import React, { useState } from 'react';

const FileUpload = ({ onUploadComplete, token, apiBaseUrl }) => {
    const [uploading, setUploading] = useState(false);
    const [progress, setProgress] = useState(0);

    const client = new SeaweedFSClient(apiBaseUrl, token);

    const handleFileUpload = async (event) => {
        const file = event.target.files[0];
        if (!file) return;

        setUploading(true);
        setProgress(0);

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
            setProgress(0);
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
            {uploading && (
                <div className="upload-progress">
                    <div>Uploading... {progress}%</div>
                    <div className="progress-bar">
                        <div 
                            className="progress-fill" 
                            style={{ width: `${progress}%` }}
                        />
                    </div>
                </div>
            )}
        </div>
    );
};
```

### File List Component

```jsx
import React, { useState, useEffect } from 'react';

const FileList = ({ token, apiBaseUrl }) => {
    const [files, setFiles] = useState([]);
    const [loading, setLoading] = useState(true);
    const [search, setSearch] = useState('');

    const client = new SeaweedFSClient(apiBaseUrl, token);

    useEffect(() => {
        loadFiles();
    }, [search]);

    const loadFiles = async () => {
        setLoading(true);
        try {
            const result = await client.listFiles({ search });
            if (result.success) {
                setFiles(result.data.data);
            }
        } catch (error) {
            console.error('Failed to load files:', error);
        } finally {
            setLoading(false);
        }
    };

    const handleDownload = async (fileId) => {
        try {
            await client.downloadFile(fileId);
        } catch (error) {
            alert(`Download failed: ${error.message}`);
        }
    };

    const handleDelete = async (fileId) => {
        if (!confirm('Are you sure you want to delete this file?')) return;

        try {
            const result = await client.deleteFile(fileId);
            if (result.success) {
                setFiles(files.filter(f => f.id !== fileId));
                alert('File deleted successfully!');
            }
        } catch (error) {
            alert(`Delete failed: ${error.message}`);
        }
    };

    const handleShare = async (fileId) => {
        const userIds = prompt('Enter user IDs to share with (comma-separated):');
        if (!userIds) return;

        try {
            const result = await client.shareFile(fileId, {
                userIds: userIds.split(',').map(id => parseInt(id.trim()))
            });
            if (result.success) {
                alert('File shared successfully!');
            }
        } catch (error) {
            alert(`Share failed: ${error.message}`);
        }
    };

    return (
        <div className="file-list">
            <div className="search-bar">
                <input
                    type="text"
                    placeholder="Search files..."
                    value={search}
                    onChange={(e) => setSearch(e.target.value)}
                />
            </div>

            {loading ? (
                <div>Loading files...</div>
            ) : (
                <div className="files">
                    {files.map(file => (
                        <div key={file.id} className="file-item">
                            <div className="file-info">
                                <h4>{file.original_name}</h4>
                                <p>Size: {file.human_readable_size}</p>
                                <p>Uploaded: {new Date(file.created_at).toLocaleDateString()}</p>
                                <p>Downloads: {file.download_count}</p>
                            </div>
                            <div className="file-actions">
                                <button onClick={() => handleDownload(file.id)}>
                                    Download
                                </button>
                                <button onClick={() => handleShare(file.id)}>
                                    Share
                                </button>
                                <button 
                                    onClick={() => handleDelete(file.id)}
                                    className="delete-btn"
                                >
                                    Delete
                                </button>
                            </div>
                        </div>
                    ))}
                </div>
            )}
        </div>
    );
};
```

## Vue.js Example

```vue
<template>
  <div class="file-manager">
    <div class="upload-section">
      <input
        type="file"
        @change="handleFileUpload"
        :disabled="uploading"
        ref="fileInput"
      />
      <div v-if="uploading" class="upload-status">
        Uploading... Please wait.
      </div>
    </div>

    <div class="file-list">
      <div v-for="file in files" :key="file.id" class="file-item">
        <span>{{ file.original_name }}</span>
        <div class="file-actions">
          <button @click="downloadFile(file.id)">Download</button>
          <button @click="deleteFile(file.id)">Delete</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import SeaweedFSClient from './seaweedfs-client.js';

export default {
  data() {
    return {
      files: [],
      uploading: false,
      client: null
    };
  },
  created() {
    this.client = new SeaweedFSClient(this.$config.apiBaseUrl, this.$auth.token);
    this.loadFiles();
  },
  methods: {
    async handleFileUpload(event) {
      const file = event.target.files[0];
      if (!file) return;

      this.uploading = true;
      try {
        const result = await this.client.uploadFile(file);
        if (result.success) {
          this.files.unshift(result.file);
          this.$refs.fileInput.value = '';
        }
      } catch (error) {
        alert(`Upload failed: ${error.message}`);
      } finally {
        this.uploading = false;
      }
    },

    async loadFiles() {
      try {
        const result = await this.client.listFiles();
        if (result.success) {
          this.files = result.data.data;
        }
      } catch (error) {
        console.error('Failed to load files:', error);
      }
    },

    async downloadFile(fileId) {
      try {
        await this.client.downloadFile(fileId);
      } catch (error) {
        alert(`Download failed: ${error.message}`);
      }
    },

    async deleteFile(fileId) {
      if (!confirm('Delete this file?')) return;
      
      try {
        const result = await this.client.deleteFile(fileId);
        if (result.success) {
          this.files = this.files.filter(f => f.id !== fileId);
        }
      } catch (error) {
        alert(`Delete failed: ${error.message}`);
      }
    }
  }
};
</script>
```

## Security Notes

1. **Always validate files on the frontend** before uploading
2. **Set appropriate file size limits** in your upload logic
3. **Implement proper error handling** for network failures
4. **Use HTTPS** for all API communications
5. **Handle token expiration** gracefully
6. **Validate file types** before upload to prevent malicious files

## Error Handling

```javascript
const handleApiError = (error, context) => {
    console.error(`${context} error:`, error);
    
    if (error.status === 401) {
        // Token expired, redirect to login
        window.location.href = '/login';
    } else if (error.status === 403) {
        alert('Access denied. You don\'t have permission to perform this action.');
    } else if (error.status === 413) {
        alert('File too large. Please choose a smaller file.');
    } else if (error.status >= 500) {
        alert('Server error. Please try again later.');
    } else {
        alert(`Error: ${error.message || 'Unknown error occurred'}`);
    }
};
```

This integration provides a complete file management system with secure presigned URLs, ensuring that files are uploaded directly to SeaweedFS while maintaining proper access control through your Laravel backend. 