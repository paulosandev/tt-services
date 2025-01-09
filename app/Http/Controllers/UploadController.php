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
            'image' => 'required|file|mimes:jpg,jpeg,png,gif,svg,webp,heic|max:4096'
        ]);

        try {
            // Subir directamente a Cloudinary con la transformación a JPG
            $uploadedFileUrl = cloudinary()->upload(
                $request->file('image')->getRealPath(),
                [
                    'folder' => 'tochpan_articles', // Carpeta opcional en Cloudinary
                    'format' => 'jpg',             // Forzar a JPG en Cloudinary
                    'resource_type' => 'image',
                ]
            )->getSecurePath();

            // Devolver la URL de la imagen recién subida
            return response()->json([
                'url' => $uploadedFileUrl
            ], 200);
        } catch (\Exception $e) {
            // Manejo de errores
            return response()->json([
                'error' => 'Error al subir la imagen: ' . $e->getMessage()
            ], 500);
        }
    }
}
