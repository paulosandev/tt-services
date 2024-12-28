<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Cloudinary;

class UploadController extends Controller
{
    public function uploadImage(Request $request)
    {
        // Validar que venga un archivo de imagen
        $request->validate([
            'image' => 'required|image|mimes:jpg,jpeg,png,gif,svg,webp|max:4096'
        ]);

        // Subir directamente a Cloudinary
        $uploadedFileUrl = cloudinary()->upload(
            $request->file('image')->getRealPath(),
            [
                'folder' => 'tochpan_articles', // Opcional: carpeta en Cloudinary
            ]
        )->getSecurePath();

        // Devolver la URL de la imagen recién subida
        return response()->json([
            'url' => $uploadedFileUrl
        ], 200);
    }
}
