<?php

namespace App\Http\Controllers;

use App\Http\Resources\ArticleResource;
use App\Models\Article;
use Elastic\Elasticsearch\Client;
use Illuminate\Http\Request;
use League\Csv\Reader;

class ImportController extends Controller
{
    private $client;

    public function __construct(Client $client){
        $this->client = $client;
    }
    public function import(){

//        phpinfo();
        return view('csv');
    }

    public function importCsv(Request $request){

        $file = $request->file('file');
        $csv = Reader::createFromPath($file->getRealPath(), 'r');
        $csv->setHeaderOffset(0);

        $records = $csv->getRecords();

        return view('csv', ['records' => $records]);
    }

    public function getImage(Request $request){
//        $imageId = $request->get('ImageId');
        $imageId = $request->get('ImageId');
        $articleNr = $request->get('NrArticle', null);
        $imageNr = $request->get('NrImage', null);
        $imageRatio = $request->get('ImageRatio', null);
        $imageResizeWidth = $request->get('ImageWidth', null);
        $imageResizeHeight = $request->get('ImageHeight', null);
        $imageCrop = $request->get('ImageForcecrop', null);
        $resizeCrop = $request->get('ImageCrop', null);

        dump($request->all());

    }
}
