<?php

namespace App\Services;

use App\Http\Resources\AuthorResource;
use App\Http\Resources\CategoryResource;
use App\Http\Resources\ImageResource;
use App\Models\Article;
use App\Models\ArticleImage;
use App\Models\Image;
use App\Models\SystemPreference;
use Illuminate\Support\Str;

class ArticleService {

    public function transformArticle(Article $article) {
        // image tag format: <!** Image 1 align="left" alt="FSF" sub="FSF" attr="value">
        $imagePattern = '<!\*\*[\s]*Image[\s]+([\d]+)(([\s]+(align|alt|sub|width|height|ratio|\w+)\s*=\s*("[^"]*"|[^\s]*))*)[\s]*>';
        $content = preg_replace_callback("/$imagePattern/i",
            function ($matches) use ($article) {
                return $this->ProcessImageLink($matches, $article);
            }
            ,$article->fields->FContinut);
        return [
            'article_id' => $article->Number,
            'category' => new CategoryResource($article->category),
            'title' => $article->Name,
            'slug' => Str::slug($article->Name) ,
            'lead' => $article->fields->Flead ?? null,
            'content' => $content ?? null,
            'images' => ImageResource::collection($article->images),
            'language' => $article->language->Code,
            'published_at' => $article->PublishDate && $article->PublishDate !== '0000-00-00 00:00:00'
                ? $article->PublishDate
                : now()->toDateTimeString(),
            'authors' => AuthorResource::collection($article->authors),
        ];
    }
    private function ProcessImageLink(array $p_matches, $article) {

        $imageNumber = $p_matches[1];
        $detailsString = $p_matches[2];
        $detailsArray = [];
        if(trim($detailsString) != '') {
            $imageAttributes = 'align|alt|sub|width|height|ratio|\w+';
            preg_match_all("/[\s]+($imageAttributes)=\"([^\"]+)\"/i", $detailsString, $detailsArray1);
            $detailsArray1[1] = array_map('strtolower', $detailsArray1[1]);
            if (count($detailsArray1[1]) > 0) {
                $detailsArray1 = array_combine($detailsArray1[1], $detailsArray1[2]);
            } else {
                $detailsArray1 = array();
            }
            preg_match_all("/[\s]+($imageAttributes)=([^\"\s]+)/i", $detailsString, $detailsArray2);
            $detailsArray2[1] = array_map('strtolower', $detailsArray2[1]);
            if (count($detailsArray2[1]) > 0) {
                $detailsArray2 = array_combine($detailsArray2[1], $detailsArray2[2]);
            } else {
                $detailsArray2 = array();
            }
            $detailsArray = array_merge($detailsArray1, $detailsArray2);
        }
        $articleImage = ArticleImage::where([
            ['NrArticle',$article->Number],
            ['Number',$imageNumber],
        ])->first();

        $image = Image::find($articleImage->IdImage);

        $imageOptions = '';
        $mediaRichTextCaptions = SystemPreference::where('varname','MediaRichTextCaptions')->first();


        if (array_key_exists('sub', $detailsArray)) {
            if ($mediaRichTextCaptions->value === 'Y') {

                $detailsArray['sub'] = html_entity_decode($detailsArray['sub'], ENT_QUOTES, 'UTF-8');

            } else {
                $detailsArray['sub'] = strip_tags(html_entity_decode($detailsArray['sub'], ENT_QUOTES, 'UTF-8'));
            }
        }

        $defaultOptions = [
            'ratio' => 'EditorImageRatio',
            'width' => 'EditorImageResizeWidth',
            'height' => 'EditorImageResizeHeight',
        ];

        foreach(['ratio','width','height'] as $imageOption){
            $defaultOption = SystemPreference::where('varname',$defaultOptions[$imageOption])->first();
            if (isset($detailsArray[$imageOption]) && $detailsArray[$imageOption] > 0) {
                if (isset($detailsArray['size']) && strpos($detailsArray['size'], 'px') !== false) {
                    $detailsArray['percentage'] = '100%';
                    $imageOptions .= " $imageOption ".rtrim($detailsArray['size'], 'px');
                } else {
                    $imageOptions .= " $imageOption ".(int) $detailsArray[$imageOption];
                }
            } elseif ($imageOption != 'ratio' && (int)$defaultOption->value > 0) {
                $imageOptions .= " $imageOption $defaultOption";
            } elseif ($imageOption === 'ratio' && (int)$defaultOption->value !== 100) {
                $imageOptions .= " $imageOption $defaultOption->value";
            }
        }
        $imageOptions = trim($imageOptions);
        $imgZoomLink = '';
        $editorImageZoom = SystemPreference::where('varname','EditorImageZoom')->first();
        if ($editorImageZoom->value === 'Y' && strlen($imageOptions)>0){
            $imgZoomLink = '/images/'.$image->ImageFileName;
        }
        $html = '';
        if (isset($detailsArray['align']) && $detailsArray['align']) {
            $html .= "<div align='center'>";
        }
        $html .= '<div class="cs_img ' . (isset($detailsArray['align']) && $detailsArray['align'] ? 'cs_fl_' . $detailsArray['align'] : '') . '"';
        if (isset($detailsArray['percentage']) && $detailsArray['percentage']) {
            $html .= ' style="width:' . $detailsArray['percentage'] . ';"';
        } elseif (isset($detailsArray['width'])) {
            $html .= ' style="width:' . $detailsArray['width'] . 'px;"';
        }
        $html .= '>';
        // Link către imagine
        if (!empty($imgZoomLink)) {
            $html .= '<p><a href="' . htmlspecialchars($imgZoomLink) . '" class="photoViewer" title="' . htmlspecialchars($detailsArray['sub'] ?? '') . '">';
        } else {
            $html .= '<p>';
        }
        // Imaginea
        $html .= '<img src="' . htmlspecialchars($imgZoomLink) . '"';
        if (isset($detailsArray['alt'])) {
            $html .= ' alt="' . htmlspecialchars($detailsArray['alt']) . '"';
        }
        if (isset($detailsArray['sub'])) {
            $html .= ' title="' . htmlspecialchars($detailsArray['sub']) . '"';
        }
        $html .= ' border="0"/>';

        if (!empty($imgZoomLink)) {
            $html .= '</a></p>';
        } else {
            $html .= '</p>';
        }
        // Subtitlul imaginii
        if (isset($detailsArray['sub'])) {
            if ($mediaRichTextCaptions->value === 'Y') {
                $html .= '<div class="cs_img_caption">' . htmlspecialchars($detailsArray['sub']) . '</div>';
            } else {
                $html .= '<p class="cs_img_caption">' . htmlspecialchars($detailsArray['sub']) . '</p>';
            }
        }

        $html .= '</div>';

        // Închidem div-ul dacă este necesar
        if (isset($detailsArray['align']) && $detailsArray['align']) {
            $html .= '</div>';
        }
        return $html;
    }
}