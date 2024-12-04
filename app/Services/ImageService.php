<?php

namespace App\Services;

use App\Models\Image;
use Illuminate\Support\Facades\Storage;
use Intervention\Gif\Exceptions\DecoderException;
use Intervention\Image\Laravel\Facades\Image as ImageManager;

class ImageService {

    public function uploadFromUrl($url, $name){
        $arrContextOptions=array(
            "ssl"=>array(
                "verify_peer"=>false,
                "verify_peer_name"=>false,
            ),
        );

        $fallbackUrl = 'https://dummyimage.com/1200x630/4b5563/e5e5e5&text=No+image+';

        $response = @file_get_contents($url, false, stream_context_create($arrContextOptions));

        if(!$response) {
            $response = file_get_contents($fallbackUrl, false, stream_context_create($arrContextOptions));
        }

        $img = ImageManager::read($response);

        $destinationPath = storage_path('app/public/images/'.$name);
        $img->save($destinationPath,quality: 80, progressive: true);
        return $img;
    }

    public function getOptimizedImageWidth($imageFileName) {
        $image = ImageManager::read(public_path('images/alpha'.$imageFileName));
        return $image->width();
    }

    public function getOptimizedImageHeight($imageFileName) {
        $image = ImageManager::read(public_path('images/alpha/'.$imageFileName));
        return $image->height();
    }

    public function OptimizeImage(Image $articleImage)
    {
        try {
            $file = Storage::disk('alpha')->get($articleImage->ImageFileName);

            // Verificăm dacă fișierul este o imagine validă
            $tempPath = storage_path('app/temp/' . $articleImage->ImageFileName);
            Storage::disk('local')->put('temp/' . $articleImage->ImageFileName, $file);
            if (!@getimagesize($tempPath)) {
                Storage::disk('local')->delete('temp/' . $articleImage->ImageFileName);
                throw new \Exception('Fișierul nu este o imagine validă: ' . $articleImage->ImageFileName);
            }

            // Procesăm imaginea
            $image = ImageManager::read($file);
            $image->scaleDown(1000);
            $image->save(storage_path('app/public/images/alpha/' . $articleImage->ImageFileName));

            $optimizedImage = Storage::disk('public')->get('images/alpha/' . $articleImage->ImageFileName);
            $sshDisk = Storage::disk('sftp');
            $sshDisk->put($articleImage->ImageFileName, $optimizedImage);

            // Curățăm fișierele temporare
            Storage::disk('local')->delete('temp/' . $articleImage->ImageFileName);

            return [
                'width' => $image->width(),
                'height' => $image->height(),
                'fileName' => $articleImage->ImageFileName,
                'url' => env('APP_URL') . '/images/' . $articleImage->ImageFileName,
            ];
        } catch (\Exception $exception) {
            dump($exception->getMessage());
            dump($exception->getFile());
        }


    }

}
