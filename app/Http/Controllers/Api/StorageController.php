<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;

class StorageController extends Controller
{
    public function __invoke(string $path)
    {
        if (!Storage::disk('public')->exists($path)) {
            abort(404);
        }

        $file = Storage::disk('public')->get($path);
        $mime = Storage::disk('public')->mimeType($path);

        return response($file, 200, [
            'Content-Type' => $mime,
        ]);
    }
}
