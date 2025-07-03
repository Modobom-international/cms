<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\File;
use App\Services\FileService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use Exception;

class FileSeaweedFSController extends Controller
{
    protected FileService $fileService;

    public function __construct(FileService $fileService)
    {
        $this->fileService = $fileService;
    }

    /**
     * Generate a presigned URL for file upload.
     */
    public function generateUploadUrl(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'filename' => 'required|string|max:255',
            'mime_type' => 'required|string|max:100',
            'metadata' => 'sometimes|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $user = Auth::user();

            // Set default expires_at to 3600 seconds (1 hour) from now
            $expiresAt = now()->addSeconds(3600);

            // Always set visibility to 'private' - not dependent on client input
            $visibility = 'private';

            $result = $this->fileService->generateUploadPresignedUrl(
                $request->filename,
                $request->mime_type,
                $user,
                $visibility,
                $expiresAt,
                $request->get('metadata', [])
            );

            return response()->json([
                'success' => true,
                'message' => 'Upload URL generated successfully',
                'data' => $result
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate upload URL',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Complete file upload and update metadata.
     */
    public function completeUpload(Request $request, int $fileId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'file_size' => 'required|integer|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $file = File::findOrFail($fileId);
            $user = Auth::user();

            // Check if user owns the file
            if ($file->user_id !== $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied'
                ], 403);
            }

            $updatedFile = $this->fileService->completeUpload($file, $request->file_size);

            return response()->json([
                'success' => true,
                'message' => 'File upload completed successfully',
                'data' => [
                    'file' => $this->fileService->getFileInfo($updatedFile, $user)
                ]
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to complete upload',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate a presigned URL for file download.
     */
    public function generateDownloadUrl(int $fileId, Request $request): JsonResponse
    {
        try {
            $file = File::findOrFail($fileId);
            $user = Auth::user();
            $expiresInMinutes = $request->get('expires_in_minutes', 60);

            $downloadUrl = $this->fileService->generateDownloadPresignedUrl(
                $file,
                $user,
                $expiresInMinutes
            );

            return response()->json([
                'success' => true,
                'message' => 'Download URL generated successfully',
                'data' => [
                    'download_url' => $downloadUrl,
                    'expires_in_minutes' => $expiresInMinutes,
                    'file_info' => $this->fileService->getFileInfo($file, $user)
                ]
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate download URL',
                'error' => $e->getMessage()
            ], 403);
        }
    }

    /**
     * Generate a presigned URL for file streaming (inline viewing).
     */
    public function generateStreamUrl(int $fileId, Request $request): JsonResponse
    {
        try {
            $file = File::findOrFail($fileId);
            $user = Auth::user();
            $expiresInMinutes = $request->get('expires_in_minutes', 60);

            $streamUrl = $this->fileService->generateStreamPresignedUrl(
                $file,
                $user,
                $expiresInMinutes
            );

            return response()->json([
                'success' => true,
                'message' => 'Stream URL generated successfully',
                'data' => [
                    'stream_url' => $streamUrl,
                    'expires_in_minutes' => $expiresInMinutes,
                    'file_info' => $this->fileService->getFileInfo($file, $user)
                ]
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate stream URL',
                'error' => $e->getMessage()
            ], 403);
        }
    }

    /**
     * Share a file with other users or roles.
     */
    public function shareFile(int $fileId, Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'user_ids' => 'sometimes|array',
            'user_ids.*' => 'exists:users,id',
            'roles' => 'sometimes|array',
            'roles.*' => 'string',
            'expires_at' => 'sometimes|date|after:now',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $file = File::findOrFail($fileId);
            $user = Auth::user();
            $expiresAt = $request->expires_at ? Carbon::parse($request->expires_at) : null;

            $updatedFile = $this->fileService->shareFile(
                $file,
                $user,
                $request->get('user_ids', []),
                $request->get('roles', []),
                $expiresAt
            );

            return response()->json([
                'success' => true,
                'message' => 'File shared successfully',
                'data' => [
                    'file' => $this->fileService->getFileInfo($updatedFile, $user)
                ]
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to share file',
                'error' => $e->getMessage()
            ], 403);
        }
    }

    /**
     * Delete a file.
     */
    public function deleteFile(int $fileId): JsonResponse
    {
        try {
            $file = File::findOrFail($fileId);
            $user = Auth::user();

            $this->fileService->deleteFile($file, $user);

            return response()->json([
                'success' => true,
                'message' => 'File deleted successfully'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete file',
                'error' => $e->getMessage()
            ], 403);
        }
    }

    /**
     * Get file information.
     */
    public function getFileInfo(int $fileId): JsonResponse
    {
        try {
            $file = File::findOrFail($fileId);
            $user = Auth::user();

            $fileInfo = $this->fileService->getFileInfo($file, $user);

            return response()->json([
                'success' => true,
                'message' => 'File information retrieved successfully',
                'data' => ['file' => $fileInfo]
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get file information',
                'error' => $e->getMessage()
            ], 403);
        }
    }

    /**
     * List user's files with pagination and filtering.
     */
    public function listFiles(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'visibility' => 'sometimes|in:private,public,shared',
            'per_page' => 'sometimes|integer|min:1|max:100',
            'search' => 'sometimes|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $user = Auth::user();
            $perPage = $request->get('per_page', 15);
            $search = $request->get('search');
            $visibility = $request->get('visibility');

            $query = File::accessibleBy($user);

            if ($visibility) {
                $query->byVisibility($visibility);
            }

            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('original_name', 'like', "%{$search}%");
                });
            }

            $files = $query->latest()->paginate($perPage);

            // Transform the files data
            $files->getCollection()->transform(function ($file) use ($user) {
                return $this->fileService->getFileInfo($file, $user);
            });

            return response()->json([
                'success' => true,
                'message' => 'Files retrieved successfully',
                'data' => $files
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve files',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get user's file statistics.
     */
    public function getStats(): JsonResponse
    {
        try {
            $user = Auth::user();
            $stats = $this->fileService->getUserFileStats($user);

            return response()->json([
                'success' => true,
                'message' => 'File statistics retrieved successfully',
                'data' => $stats
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve statistics',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Health check for SeaweedFS connection.
     */
    public function healthCheck(): JsonResponse
    {
        try {
            $isHealthy = $this->fileService->healthCheck();
            $bucketExists = $this->fileService->ensureBucketExists();

            return response()->json([
                'success' => true,
                'message' => 'Health check completed',
                'data' => [
                    'seaweedfs_healthy' => $isHealthy,
                    'bucket_exists' => $bucketExists,
                    'bucket_name' => config('filesystems.disks.seaweedfs.bucket'),
                    'endpoint' => config('filesystems.disks.seaweedfs.endpoint'),
                ]
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Health check failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
