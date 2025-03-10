<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Log;
use Str;

class UploadController extends Controller
{
    public function upload(Request $request)
    {
        $request->validate([
            'file' => 'file|max:200'
        ]);

        $file = $request->file('file');
        if ($file == null) {
            return response()->json([
                'message' => 'Error uploading file. File not found'
            ], 500);
        }

        $extension = $file->extension();

        $storedName = Str::uuid() . '.' . $extension;

        $stored = $file->storeAs('', $storedName, 'public');

        if ($stored == false) {
            return response()->json([
                'message' => 'Error uploading file'
            ], 500);
        }

        return response()->json([
            'file_url' => asset('/storage/' . $storedName)
        ]);
    }
}
