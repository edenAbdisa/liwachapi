<?php

namespace App\Http\Controllers;

use Exception;
use App\Models\ReportType;
use App\Models\Flag;
use App\Models\Item;
use App\Models\Service;
use Illuminate\Http\Request;
use Gate;
use App\Http\Resources\FlagResource;
use App\Models\User;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class FlagController extends Controller
{
    /**
     * @OA\Get(
     *      path="/flag",
     *      operationId="getFlagList",
     *      tags={"Flag"},
     *      summary="Get list of Flag",
     *      description="Returns list of Flag",
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *          @OA\JsonContent(ref="#/components/schemas/FlagResource")
     *       ),
     *      @OA\Response(
     *          response=401,
     *          description="Unauthenticated",
     *      ),
     *      @OA\Response(
     *          response=403,
     *          description="Forbidden"
     *      )
     *     )
     */
    public function index()
    {
        try {
            $flag = Flag::all();
            foreach ($flag as $f) {
                $f->reason;
                $f->flagged_by;
                $f->flagged_item;
            }
            return response()
                ->json(
                    HelperClass::responeObject($flag, true, Response::HTTP_OK, 'Successfully fetched.', "Flag is fetched sucessfully.", ""),
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
     * @OA\Post(
     *      path="/flag",
     *      operationId="storeFlag",
     *      tags={"Flag"},
     *      summary="Store new Flag",
     *      description="Returns flag data",
     *      @OA\RequestBody(
     *          required=true,
     *          @OA\JsonContent(ref="#/components/schemas/Flag")
     *      ),
     *      @OA\Response(
     *          response=201,
     *          description="Successful operation",
     *          @OA\JsonContent(ref="#/components/schemas/Flag")
     *       ),
     *      @OA\Response(
     *          response=400,
     *          description="Bad Request"
     *      ),
     *      @OA\Response(
     *          response=401,
     *          description="Unauthenticated",
     *      ),
     *      @OA\Response(
     *          response=403,
     *          description="Forbidden"
     *      )
     * )
     */
    public function store(Request $request)
    {
        try {
            $validatedData = Validator::make($request->all(), [
                'reason_id' => ['required','numeric'],
                'flagged_item_id' => ['required','numeric'],
                'type' => ['required','max:30',Rule::in(['item', 'service'])]
            ]);
            if ($validatedData->fails()) {
                return response()
                    ->json(
                        HelperClass::responeObject(null, false, Response::HTTP_BAD_REQUEST, "Validation failed check JSON request", "", $validatedData->errors()),
                        Response::HTTP_BAD_REQUEST
                    );
            }
            $input = $request->all();
            $user =$request->user();
            if(strcmp($request->type,'item')==0){
                $flagged_item = Item::where('id', $request->flagged_item_id)->where('status', '!=', 'deleted')->first();
            }elseif(strcmp($request->type,'service')==0){
                $flagged_item = Service::where('id', $request->flagged_item_id)->where('status', '!=', 'deleted')->first();
            }
            if(!$flagged_item){
                return response()
                ->json(
                    HelperClass::responeObject(null, false, Response::HTTP_BAD_REQUEST, "Not valid "+$request->type+" id passed", "", "$request->type doesnt exist by this id."),
                    Response::HTTP_BAD_REQUEST
                ); 
            }
            $reason = ReportType::where('id', $request->reason_id)->where('status', '!=', 'deleted')->first();
            if (!$reason) {
                return response()
                    ->json(
                        HelperClass::responeObject(null, false, Response::HTTP_BAD_REQUEST, "Not valid reason id passed", "", "A reason doesnt exist by this id."),
                        Response::HTTP_BAD_REQUEST
                    ); 
            }
            $previouslyflagged = Flag::where('flagged_item_id', $request->flagged_item_id)->where('flagged_by_id', $user->id)->first();
            if ($previouslyflagged) {
                return response()
                    ->json(
                        HelperClass::responeObject($previouslyflagged, false, Response::HTTP_CONFLICT, "Already flagged", "", "You have previously flagged this $request->type"),
                        Response::HTTP_CONFLICT
                    );
            }
            $flag = new Flag($input);
            $flag->flagged_by_id = $user->id;
            if ($flag->save()) {
                $flagged_item->number_of_flag = (int)$flagged_item->number_of_flag + 1;
                $flagged_item->save();
                return response()
                ->json(
                    HelperClass::responeObject($flag, true, Response::HTTP_CREATED, "$request->type flagged", "", "This $request->type is flagged."),
                    Response::HTTP_CREATED
                ); 
            } else {
                return response()
                ->json(
                    HelperClass::responeObject($flag, false, Response::HTTP_INTERNAL_SERVER_ERROR, "$request->type couldnt be flagged.", "", "This $request->type couldnt be flagged due to internal error."),
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
                    HelperClass::responeObject(null, false, RESPONSE::HTTP_UNPROCESSABLE_ENTITY, 'Internal error occured.', "", $ex->getMessage()),
                    Response::HTTP_INTERNAL_SERVER_ERROR
                );
        }
    }
    public function flaggedProductCountByDate($attribute, $start, $end)
    {
        try {
            $items = Flag::orderBy($attribute)->whereBetween($attribute, [$start, $end])->get()->groupBy(function ($item) {
                return $item->created_at->format('Y-m-d');
            });
        } catch (Exception $e) {
            return response()
                ->json("There is no such attribute.", Response::HTTP_OK);
        }
        foreach ($items as $key => $item) {
            $day = $key;
            $totalCount = $item->count();
            $items[$key] = $totalCount;
        }
        return response()
            ->json($items, Response::HTTP_OK);
    }
    /**
     * @OA\Get(
     *      path="/flag/{id}",
     *      operationId="getFlagById",
     *      tags={"Flag"},
     *      summary="Get flag information",
     *      description="Returns flag data",
     *      @OA\Parameter(
     *          name="id",
     *          description="Flag id",
     *          required=true,
     *          in="path",
     *          @OA\Schema(
     *              type="integer"
     *          )
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *          @OA\JsonContent(ref="#/components/schemas/Flag")
     *       ),
     *      @OA\Response(
     *          response=400,
     *          description="Bad Request"
     *      ),
     *      @OA\Response(
     *          response=401,
     *          description="Unauthenticated",
     *      ),
     *      @OA\Response(
     *          response=403,
     *          description="Forbidden"
     *      )
     * )
     */
    public function search(Request $request)
    {
        try {
            $validatedData = Validator::make($request->all(), [
                'reason_id' => ['numeric'],
                'flagged_item_id' => ['numeric'],
                'type' => ['max:30']
            ]);
            if ($validatedData->fails()) {
                return response()
                    ->json(
                        HelperClass::responeObject(null, false, Response::HTTP_BAD_REQUEST, "Validation failed check JSON request", "", $validatedData->errors()),
                        Response::HTTP_BAD_REQUEST
                    );
            }
            $input = $request->all();
            $flags = Flag::all();
            if ($flags->count() <= 0) {
                return response()
                    ->json(
                        HelperClass::responeObject($flags, true, Response::HTTP_OK, 'List of flags.', "There is no flagged item.", ""),
                        Response::HTTP_OK
                    );
            }
            $col = DB::getSchemaBuilder()->getColumnListing('flags');
            $requestKeys = collect($request->all())->keys();
            foreach ($requestKeys as $key) {
                if (in_array($key, $col)) {
                    $flags = $flags->where($key, $input[$key])->values();
                }
            }
            $flags->each(function ($flag, $key) {
                $flag->reason;
                $flag->flagged_by;
                $flag->flagged_item;
            });
            return response()
                ->json(
                    HelperClass::responeObject($flags, true, Response::HTTP_OK, 'List of flagged items.', "These are the list of flagged items and service based on your search.", ""),
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
                    HelperClass::responeObject(null, false, RESPONSE::HTTP_UNPROCESSABLE_ENTITY, 'Internal error occured.', "", $ex->getMessage()),
                    Response::HTTP_INTERNAL_SERVER_ERROR
                );
        }
    }

    /**
     * @OA\Put(
     *      path="/flag/{id}",
     *      operationId="updateFlag",
     *      tags={"Flag"},
     *      summary="Update existing flag",
     *      description="Returns updated flag data",
     *      @OA\Parameter(
     *          name="id",
     *          description="Flag id",
     *          required=true,
     *          in="path",
     *          @OA\Schema(
     *              type="integer"
     *          )
     *      ),
     *      @OA\RequestBody(
     *          required=true,
     *          @OA\JsonContent(ref="#/components/schemas/UpdateFlagRequest")
     *      ),
     *      @OA\Response(
     *          response=202,
     *          description="Successful operation",
     *          @OA\JsonContent(ref="#/components/schemas/Flag")
     *       ),
     *      @OA\Response(
     *          response=400,
     *          description="Bad Request"
     *      ),
     *      @OA\Response(
     *          response=401,
     *          description="Unauthenticated",
     *      ),
     *      @OA\Response(
     *          response=403,
     *          description="Forbidden"
     *      ),
     *      @OA\Response(
     *          response=404,
     *          description="Resource Not Found"
     *      )
     * )
     */
    public function update(Request $request, $id)
    {
        try {
            $validatedData = Validator::make($request->all(), [
                'reason_id' => ['numeric'],
                'flagged_item_id' => ['numeric'],
                'type' => ['max:30']
            ]);
            if ($validatedData->fails()) {
                return response()
                    ->json(
                        HelperClass::responeObject(null, false, Response::HTTP_BAD_REQUEST, "Validation failed check JSON request", "", $validatedData->errors()),
                        Response::HTTP_BAD_REQUEST
                    );
            }
            $input = $request->all();
            $flag = Flag::where('id', $id)->first();
            $flag->flagged_by_id = $flag->flagged_by_id;
            if ($flag->fill($input)->save()) {
                $flag->reason;
                $flag->flagged_by;
                $flag->flagged_item;

                return (new FlagResource($flag))
                    ->response()
                    ->setStatusCode(Response::HTTP_CREATED);
            } else {
                return response()
                    ->json("This resource couldn't be saved due to internal error", Response::HTTP_INTERNAL_SERVER_ERROR);
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
     * @OA\Delete(
     *      path="/flag/{id}",
     *      operationId="deleteFlag",
     *      tags={"Flag"},
     *      summary="Delete existing flag",
     *      description="Deletes a record and returns no content",
     *      @OA\Parameter(
     *          name="id",
     *          description="Flag id",
     *          required=true,
     *          in="path",
     *          @OA\Schema(
     *              type="integer"
     *          )
     *      ),
     *      @OA\Response(
     *          response=204,
     *          description="Successful operation",
     *          @OA\JsonContent()
     *       ),
     *      @OA\Response(
     *          response=401,
     *          description="Unauthenticated",
     *      ),
     *      @OA\Response(
     *          response=403,
     *          description="Forbidden"
     *      ),
     *      @OA\Response(
     *          response=404,
     *          description="Resource Not Found"
     *      )
     * )
     */
    public function destroy($id)
    {
        try {
            $flag = Flag::find($id);
            if (!$flag) {
                response()
                    ->json(
                        HelperClass::responeObject(null, false, Response::HTTP_NOT_FOUND, "Resource Not Found", '', "Flag by this id doesnt exist."),
                        Response::HTTP_NOT_FOUND
                    );
            }
            $flag->delete();
            return response()
                ->json(
                    HelperClass::responeObject(null, true, Response::HTTP_NO_CONTENT, 'Successfully deleted.', "Flag is deleted sucessfully.", ""),
                    Response::HTTP_NO_CONTENT
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
}
