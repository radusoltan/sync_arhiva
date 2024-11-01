<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ImageResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->Id,
            'photographer' => $this->Photographer,
            'description' => $this->Description,
            'source' => $this->Source,
            'fileName' => $this->ImageFileName,
            'is_default' => $this->pivot->is_default === 1 ? true : false,
            'width' => $this->width,
            'height' => $this->height
        ];
    }
}
