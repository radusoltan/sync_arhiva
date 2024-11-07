<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;
use League\HTMLToMarkdown\HtmlConverter;

class ArticleResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $converter = new HtmlConverter();

        return [
            'article_id' => $this->Number,
            'category' => new CategoryResource($this->category),
            'title' => $this->Name,
            'slug' => Str::slug($this->Name) ,
            'lead' => $this->fields->Flead ?? null,
            'content' => $this->fields->FContinut ?? null,
            'images' => ImageResource::collection($this->images),
            'language' => $this->language->Code,
            'published_at' => $this->PublishDate && $this->PublishDate !== '0000-00-00 00:00:00'
                ? $this->PublishDate
                : now()->toDateTimeString(),
            'authors' => AuthorResource::collection($this->authors),
        ];
    }
}
