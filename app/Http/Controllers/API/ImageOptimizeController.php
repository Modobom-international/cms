<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Spatie\Image\Image;
use Spatie\ImageOptimizer\OptimizerChainFactory;
use Illuminate\Support\Str;

class ImageOptimizeController extends Controller
{
    public function optimize(Request $request)
    {
        $request->validate([
            'image' => 'required|image',
            'format' => 'nullable|in:jpg,jpeg,png,webp,avif',
            'quality' => 'nullable|integer|min:10|max:100',
            'width' => 'nullable|integer|min:1',
            'height' => 'nullable|integer|min:1',
        ]);

        $file = $request->file('image');

        $originalPath = storage_path('app/tmp/' . Str::random(10) . '.' . $file->getClientOriginalExtension());
        $file->move(dirname($originalPath), basename($originalPath));

        $outputExtension = $request->input('format', $file->getClientOriginalExtension());
        $outputPath = str_replace('.' . $file->getClientOriginalExtension(), '.' . $outputExtension, $originalPath);

        $image = Image::load($originalPath)
            ->format($outputExtension)
            ->quality($request->input('quality', 80));

        if ($request->filled('width') || $request->filled('height')) {
            $image->width($request->input('width', null))
                ->height($request->input('height', null));
        }

        $image->save($outputPath);
        $optimizerChain = OptimizerChainFactory::create();
        $optimizerChain->optimize($outputPath);

        $mime = mime_content_type($outputPath);
        $contents = file_get_contents($outputPath);

        unlink($originalPath);
        unlink($outputPath);

        return response($contents)
            ->header('Content-Type', $mime)
            ->header('Content-Disposition', 'inline; filename=optimized.' . $outputExtension);
    }
}
