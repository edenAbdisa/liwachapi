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
        try {
            $medias = Media::all();
            return response()
                ->json(
                    HelperClass::responeObject(
                        $medias,
                        true,
                        Response::HTTP_OK,
                        'Successfully fetched.',
                        "Medias are fetched sucessfully.",
                        ""
                    ),
                    Response::HTTP_OK
                );
        } catch (ModelNotFoundException $ex) { // User not found
            return response()
                ->json(
                    HelperClass::responeObject(null, false, RESPONSE::HTTP_UNPROCESSABLE_ENTITY, 'The model doesnt exist.', "", $ex->getMessage()),
                    Response::HTTP_UNPROCESSABLE_ENTITY
                );
        } catch (Exception $ex) { // Anything that went wrong
            return response()
                ->json(
                    HelperClass::responeObject(null, false, RESPONSE::HTTP_UNPROCESSABLE_ENTITY, 'Internal server error.', "", $ex->getMessage()),
                    Response::HTTP_UNPROCESSABLE_ENTITY
                );
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        try {
            $validatedData = Validator::make($request->all(), [
                'item_id' => ['required','numeric'],
                'type' => ['required','max:10', Rule::in(['item', 'service', 'user'])]
            ]);
            if ($validatedData->fails()) {
                return response()
                    ->json(
                        HelperClass::responeObject(null, false, Response::HTTP_BAD_REQUEST, "Validation failed check JSON request", "", $validatedData->errors()),
                        Response::HTTP_BAD_REQUEST
                    );
            }
            $mediaUrl = $request->url;
            foreach ($mediaUrl as $m) {
                $media = new Media();
                $media->type = $request->type;
                $media->url = $m;
                $media->item_id = $request->item_id;
                if (!$media->save()) {
                    return response()
                        ->json(
                            HelperClass::responeObject(null, false, Response::HTTP_INTERNAL_SERVER_ERROR, "Media couldnt be saved.", "", "The media $media resource couldn't be saved due to internal error"),
                            Response::HTTP_INTERNAL_SERVER_ERROR
                        );
                }
            }
            return response()
                ->json(
                    HelperClass::responeObject(Media::where('item_id', $request->item_id)->get(), true, Response::HTTP_CREATED, "Media created.", "The media is saved", ""),
                    Response::HTTP_CREATED
                );
        } catch (ModelNotFoundException $ex) { // User not found
            return response()
                ->json(
                    HelperClass::responeObject(null, false, RESPONSE::HTTP_UNPROCESSABLE_ENTITY, 'The model doesnt exist.', "", $ex->getMessage()),
                    Response::HTTP_UNPROCESSABLE_ENTITY
                );
        } catch (Exception $ex) { // Anything that went wrong
            return response()
                ->json(
                    HelperClass::responeObject(null, false, RESPONSE::HTTP_UNPROCESSABLE_ENTITY, 'Internal server error.', "", $ex->getMessage()),
                    Response::HTTP_UNPROCESSABLE_ENTITY
                );
        }
    }
    public function search(Request $request)
    {
        try {
            $validatedData = Validator::make($request->all(), [
                'item_id' => ['numeric'],
                'type' => ['max:10']
            ]);
            if ($validatedData->fails()) {
                return response()
                    ->json(
                        HelperClass::responeObject(null, false, Response::HTTP_BAD_REQUEST, "Validation failed check JSON request", "", $validatedData->errors()),
                        Response::HTTP_BAD_REQUEST
                    );
            }
            $input = $request->all();
            $medias = Media::all();
            if ($medias->count() <= 0) {
                return response()
                    ->json(
                        HelperClass::responeObject($medias, true, Response::HTTP_OK, 'List of medias.', "There is no media.", ""),
                        Response::HTTP_OK
                    );
            }
            $col = DB::getSchemaBuilder()->getColumnListing('medias');
            
            
            $requestKeys = collect($request->all())->keys();
            foreach ($requestKeys as $key) {
                if (in_array($key, $col)) {
                    $medias = $medias->where($key, $input[$key])->values();
                }
            }
            $medias->each(function ($flag, $key) {
                $flag->reason;
                $flag->flagged_by;
                $flag->flagged_item;
            });
            return response()
                ->json(
                    HelperClass::responeObject($medias, true, Response::HTTP_CREATED, "List of Medias.", "This is the list of media based on your search", ""),
                    Response::HTTP_CREATED
                );
        } catch (ModelNotFoundException $ex) { // User not found
            return response()
                ->json(
                    HelperClass::responeObject(null, false, RESPONSE::HTTP_UNPROCESSABLE_ENTITY, 'The model doesnt exist.', "", $ex->getMessage()),
                    Response::HTTP_UNPROCESSABLE_ENTITY
                );
        } catch (Exception $ex) { // Anything that went wrong
            return response()
                ->json(
                    HelperClass::responeObject(null, false, RESPONSE::HTTP_UNPROCESSABLE_ENTITY, 'Internal error occured.', "", $ex->getMessage()),
                    Response::HTTP_INTERNAL_SERVER_ERROR
                );
        }
    }
    public function updateAllMedia(Request $request)
    {
        try {
            $validatedData = Validator::make($request->all(), [
                'item_id' => ['required', 'numeric'],
                'type' => ['required', 'max:10', Rule::in(['item', 'service', 'user'])]
            ]);
            if ($validatedData->fails()) {
                return response()
                    ->json(
                        HelperClass::responeObject(null, false, Response::HTTP_BAD_REQUEST, "Validation failed check JSON request", "", $validatedData->errors()),
                        Response::HTTP_BAD_REQUEST
                    );
            }
            $input = $request->all();
            if ($request->media["removed"] && $request->media["added"]) {
                $toBeRemoved = $request->media["removed"];
                $newToBeSaved = $request->media["added"];
                $oldMedia = Media::where('item_id', $request->item_id)
                    ->where('id', $toBeRemoved)->get();
                Media::destroy($oldMedia);
                foreach ($newToBeSaved as $m) {
                    $media = new Media();
                    $media->type = $request->type;
                    $media->url = $m;
                    $media->item_id = $request->item_id;
                    if (!$media->save()) {
                        return response()
                            ->json(
                                HelperClass::responeObject(null, false, Response::HTTP_INTERNAL_SERVER_ERROR, "Media couldnt be saved.", "", "The media $media resource couldn't be saved due to internal error"),
                                Response::HTTP_INTERNAL_SERVER_ERROR
                            );
                    }
                }
            } else {
                return response()
                    ->json(
                        HelperClass::responeObject(null, false, Response::HTTP_INTERNAL_SERVER_ERROR, 'Medias arent updated.', "", "The removed and added medias arent properly sent."),
                        Response::HTTP_INTERNAL_SERVER_ERROR
                    );
            }
            return response()
                ->json(
                    HelperClass::responeObject($media, true, Response::HTTP_OK, 'Medias are updated.', "The medias are updated successfully.", ""),
                    Response::HTTP_OK
                );
        } catch (ModelNotFoundException $ex) { // User not found
            return response()
                ->json(
                    HelperClass::responeObject(null, false, RESPONSE::HTTP_UNPROCESSABLE_ENTITY, 'The model doesnt exist.', "", $ex->getMessage()),
                    Response::HTTP_UNPROCESSABLE_ENTITY
                );
        } catch (Exception $ex) { // Anything that went wrong
            return response()
                ->json(
                    HelperClass::responeObject(null, false, RESPONSE::HTTP_UNPROCESSABLE_ENTITY, 'Internal server error.', "", $ex->getMessage()),
                    Response::HTTP_UNPROCESSABLE_ENTITY
                );
        }
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
        try {
            $validatedData = Validator::make($request->all(), [
                'item_id' => ['numeric'],
                'type' => ['max:10', Rule::in(['item', 'service', 'user'])]
            ]);
            if ($validatedData->fails()) {
                return response()
                    ->json(
                        HelperClass::responeObject(null, false, Response::HTTP_BAD_REQUEST, "Validation failed check JSON request", "", $validatedData->errors()),
                        Response::HTTP_BAD_REQUEST
                    );
            }
            $input = $request->all();
            $media = Media::where('id', $id)->first();
            if (!$media) {
                return response()
                    ->json(
                        HelperClass::responeObject(null, false, Response::HTTP_NOT_FOUND, "Media isn't found.", "", "A media by this id doesnt exist."),
                        Response::HTTP_NOT_FOUND
                    );
            }
            if ($media->fill($input)->save()) {
                return response()
                    ->json(
                        HelperClass::responeObject($media, true, Response::HTTP_OK, 'Media updated.', "The media is updated successfully.", ""),
                        Response::HTTP_OK
                    );
            } else {
                return response()
                    ->json(
                        HelperClass::responeObject(null, false, Response::HTTP_INTERNAL_SERVER_ERROR, 'Media update failed.', "The media couldn't be updated.", ""),
                        Response::HTTP_INTERNAL_SERVER_ERROR
                    );
            }
        } catch (ModelNotFoundException $ex) { // User not found
            return response()
                ->json(
                    HelperClass::responeObject(null, false, RESPONSE::HTTP_UNPROCESSABLE_ENTITY, 'The model doesnt exist.', "", $ex->getMessage()),
                    Response::HTTP_UNPROCESSABLE_ENTITY
                );
        } catch (Exception $ex) { // Anything that went wrong
            return response()
                ->json(
                    HelperClass::responeObject(null, false, RESPONSE::HTTP_UNPROCESSABLE_ENTITY, 'Internal server error.', "", $ex->getMessage()),
                    Response::HTTP_UNPROCESSABLE_ENTITY
                );
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
        try {
            $media = Media::find($id);
            if (!$media) {
                response()
                    ->json(
                        HelperClass::responeObject(null, false, Response::HTTP_NOT_FOUND, "Resource Not Found", '', "Media by this id doesnt exist."),
                        Response::HTTP_NOT_FOUND
                    );
            }
            $media->delete();
            return response()
                ->json(
                    HelperClass::responeObject(null, true, Response::HTTP_OK, 'Successfully deleted.', "Media is deleted sucessfully.", ""),
                    Response::HTTP_OK
                );
        } catch (ModelNotFoundException $ex) {
            return response()
                ->json(
                    HelperClass::responeObject(null, false, RESPONSE::HTTP_UNPROCESSABLE_ENTITY, 'The model doesnt exist.', "", $ex->getMessage()),
                    Response::HTTP_UNPROCESSABLE_ENTITY
                );
        } catch (Exception $ex) { // Anything that went wrong
            return response()
                ->json(
                    HelperClass::responeObject(null, false, RESPONSE::HTTP_UNPROCESSABLE_ENTITY, 'Internal error occured.', "", $ex->getMessage()),
                    Response::HTTP_INTERNAL_SERVER_ERROR
                );
        }
    }
}
