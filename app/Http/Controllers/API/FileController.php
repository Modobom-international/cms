<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Requests\FileRequest;
use App\Repositories\FileRepository;
use Illuminate\Support\Facades\Storage;

class FileController extends Controller
{
    protected $fileRepository;

    public function __construct(FileRepository $fileRepository)
    {
        $this->fileRepository = $fileRepository;
    }

    public function upload(FileRequest $request)
    {
        try {
            $file = $request->file('file');
            $path = $request->input('path');
            $fileName = time() . '_' . $file->getClientOriginalName();
            $fullPath = "/files/{$path}/{$fileName}";

            Storage::put($fullPath, file_get_contents($file));

            $data = $this->fileRepository->create([
                'name' => $fileName,
                'path' => $fullPath,
                'type' => 'file',
                'size' => $file->getSize(),
            ]);

            return response()->json([
                'success' => true,
                'data' => $data,
                'message' => 'Tải file lên thành công',
                'type' => 'upload_file_success',
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Tải file lên không thành công',
                'type' => 'upload_file_fail',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function download($id)
    {
        try {
            $file = $this->fileRepository->getById($id);

            if (!$file and !Storage::exists($file->path)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không tìm thấy file',
                    'type' => 'download_file_fail',
                ], 500);
            }

            return response()->json([
                'success' => true,
                'data' => $file,
                'message' => 'Lấy file thành công',
                'type' => 'download_file_success',
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lấy danh sách team không thành công',
                'type' => 'download_file_success',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function list(Request $request)
    {
        try {
            $path = $request->query('path', '');
            $basePath = "/files/{$path}";
            $files = Storage::allFiles($basePath);
            $directories = Storage::directories($basePath);

            $fileList = [];
            foreach ($directories as $dir) {
                $fileList[] = [
                    'name' => basename($dir),
                    'type' => 'folder',
                    'path' => $dir,
                ];
            }

            foreach ($files as $file) {
                $fileRecord = $this->fileRepository->getByPath($file);
                if ($fileRecord) {
                    $fileList[] = [
                        'id' => $fileRecord->id,
                        'name' => $fileRecord->name,
                        'type' => 'file',
                        'path' => $fileRecord->path,
                        'size' => $fileRecord->size,
                    ];
                }
            }
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Lấy danh sách team không thành công',
                'type' => 'download_file_success',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
