<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Shop;
use Illuminate\Support\Facades\Storage;
use InterventionImage;
use App\Http\Requests\UploadImageRequest;
use App\Services\ImageService;

//use function PHPUnit\Framework\isNull;

class ShopController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:owners');

        $this->middleware(function ($request, $next){
            //dd($request->route()->parameter('shop'));//文字列
            //dd(Auth::id());//数字

            $id = $request->route()->parameter('shop');
            if(!is_Null($id)){
                $shopsOwnerId = Shop::findOrFail($id)->owner->id;
                $shopId = (int)$shopsOwnerId;//cast
                $ownerId = Auth::id();//shopとowneridが一致か確認
                if($shopId !== $ownerId){
                    abort(404);
                }
            }
            return $next($request);
        });
    }

    public function index()
    {
//phpinfo();
        //$ownerId = Auth::id();
        $shops = Shop::where('owner_id', Auth::id())->get();

        return view('owner.shops.index',
        compact('shops'));
    }

    public function edit($id)
    {
        //dd(Shop::findOrFail($id));
        $shop = Shop::findOrFail($id);

        return view('owner.shops.edit', compact('shop'));
    }

    public function update(UploadImageRequest $request, $id)
    {
        $request->validate([
            'name' => 'required|string|max:50',
            'information' => 'required|string|max:1000',
            'is_selling' => 'required',
        ]);


        $imageFile = $request->image;

            if(!is_null($imageFile) && $imageFile->isValid()){
                $fileNameToStore = ImageService::upload($imageFile, 'shops');
                //Storage::putFile('public/shops', $imageFile);
                //$fileName = uniqid(rand().'_');
                //$extension = $imageFile->extension();
                //$fileNameToStore = $fileName. '.' . $extension;
                //$resizedImage = InterventionImage::make($imageFile)->resize(1920, 1080)->encode();

                //Storage::put('public/shops/' . $fileNameToStore, $resizedImage);
            }

        $shop = Shop::findOrFail($id);
        $shop->name =$request->name;
        $shop->information =$request->information;
        $shop->is_selling =$request->is_selling;
        if(!is_null($imageFile) && $imageFile->isValid()){
            $shop->filename = $fileNameToStore;
        }

        $shop->save();

        return redirect()->route('owner.shops.index')
        ->with(['message' => '店舗情報を更新しました。',
        'status' => 'info']);
    }
}
