<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Repositories\FileRepository;
use Illuminate\Support\Facades\Storage;

class FileController extends Controller
{
    protected $fileRepository;

    public function __construct(FileRepository $fileRepository)
    {
        $this->fileRepository = $fileRepository;
    }

    public function upload(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:pdf,jpg,png|max:10240',
            'path' => 'required|string',
        ]);

        $file = $request->file('file');
        $path = $request->input('path');
        $fileName = time() . '_' . $file->getClientOriginalName();
        $fullPath = "private/files/{$path}/{$fileName}";

        Storage::put($fullPath, file_get_contents($file));

        $fileRecord = $this->fileRepository->create([
            'name' => $fileName,
            'path' => $fullPath,
            'type' => 'file',
            'size' => $file->getSize(),
        ]);

        return response()->json(['message' => 'File uploaded successfully', 'file' => $fileRecord], 201);
    }

    public function download($id)
    {
        $file = $this->fileRepository->getById($id);

        if (!$file and !Storage::exists($file->path)) {
            abort(404, 'File not found');
        }

        return Storage::download($file->path, $file->name);
    }

    public function list(Request $request)
    {
        $path = $request->query('path', '');
        $basePath = "private/files/{$path}";
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
            $fileRecord = File::where('path', $file)->first();
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

        return response()->json($fileList);
    }
}
