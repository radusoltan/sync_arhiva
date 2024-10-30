<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ArticleResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->Number,
            'category' => $this->category->Name,
            'title' => $this->Name,
            'lead' => $this->fields->Flead,
            'content' => $this->fields->FContinut,
            'images' => ImageResource::collection($this->images()->get()),
            'language' => $this->language->Code,
            'published_at' => $this->PublishDate
        ];
    }
}
