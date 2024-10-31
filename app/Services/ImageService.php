<?php

namespace App\Services;

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

}
