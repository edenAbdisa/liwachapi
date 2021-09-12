<?php

namespace App\Http\Controllers;

use Exception;
use App\Models\Media;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Resources\MediaResource;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class MediaController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $items = Media::all();
        return (new MediaResource($items))
            ->response()
            ->setStatusCode(Response::HTTP_OK);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $serviceMedia = $request->url;
        foreach ($serviceMedia as $m) {
            //check if the sent type id is in there 
            $media = new Media();
            $media->type = $request->type;
            $media->url = $m;
            $media->item_id = $request->item_id;
            if (!$media->save()) {
                return response()
                    ->json("The media $media resource couldn't be saved due to internal error", Response::HTTP_INTERNAL_SERVER_ERROR);
            }
        }
        return (new MediaResource(Media::where('item_id', $request->item_id)->get()))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }
    public function search(Request $request)
    {
        $input = $request->all();
        $medias = Media::all();
        $col = DB::getSchemaBuilder()->getColumnListing('medias');
        $requestKeys = collect($request->all())->keys();
        foreach ($requestKeys as $key) {
            if (empty($medias)) {
                return response()->json($medias, 200);
            }
            if (in_array($key, $col)) {
                $medias = $medias->where($key, $input[$key])->values();
            }
        }
        $medias->each(function ($flag, $key) {
            $flag->reason;
            $flag->flagged_by;
            $flag->flagged_item;
        });
        return response()->json($medias, 200);
    }
    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Media  $media
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $input = $request->all();
        $media = Media::where('id', $id)->first();
        if ($media->fill($input)->save()) {

            return (new MediaResource($media))
                ->response()
                ->setStatusCode(Response::HTTP_CREATED);
        } else {
            return response()
                ->json("This resource couldn't be saved due to internal error", Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Media  $media
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $media = Media::find($id);
        if (!$media) {
            return response()
                ->json("Resource Not Found", Response::HTTP_NOT_FOUND);
        }
        $media->delete();
        return response(null, Response::HTTP_NO_CONTENT);
    }
}
