<?php

namespace App\Http\Controllers;

use Exception;
use App\Models\RequestOrder;
use App\Models\Item;
use App\Models\Service;
use Illuminate\Http\Request;
use Gate;
use App\Http\Resources\RequestResource;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class RequestController extends Controller
{
    /**
     * @OA\Get(
     *      path="/request",
     *      operationId="getRequestList",
     *      tags={"Request"},
     *      summary="Get list of Request",
     *      description="Returns list of Request",
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *          @OA\JsonContent(ref="#/components/schemas/RequestResource")
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
            $requestOrder = RequestOrder::where('status', '!=', 'deleted')
                ->orWhereNull('status')->get()
                ->each(function ($item, $key) {
                    $item->requester;
                    $item->requested_item;
                    $item->requester_item;
                    $item->requested_item->type;
                    $item->requester_item->type;
                });
            return response()
                ->json(
                    HelperClass::responeObject(
                        $requestOrder,
                        true,
                        Response::HTTP_OK,
                        'Successfully fetched.',
                        "Request Order are fetched sucessfully.",
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
                    Response::HTTP_INTERNAL_SERVER_ERROR
                );
        }
    }
    /*  public function statusCountRequest($status,$type)
    {
        //abort_if(Gate::denies('request_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');
        //User::with(['roles'])->get() 
        //$wordCount = Wordlist::where('id', '<=', $correctedComparisons)->count();
        $requestOrder = RequestOrder::where('status', '=', $status)
                        ->where('type', '=', $type)->count();
        return response()
        ->json($requestOrder,Response::HTTP_OK);
    }  */
    public function requestCount($type)
    {
        //abort_if(Gate::denies('request_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');
        //User::with(['roles'])->get() 
        //$wordCount = Wordlist::where('id', '<=', $correctedComparisons)->count();
        $userGrouped = RequestOrder::where('type', '=', $type)->get()->groupBy(function ($item) {
            return $item->status;
        });
        foreach ($userGrouped as $key => $user) {
            $day = $key;
            $totalCount = $user->count();
            $userGrouped[$key] = $totalCount;
        }
        return response()
            ->json($userGrouped, Response::HTTP_OK);
    }
    /**
     * @OA\Post(
     *      path="/request",
     *      operationId="storeRequest",
     *      tags={"Request"},
     *      summary="Store new Request",
     *      description="Returns request data",
     *      @OA\RequestBody(
     *          required=true,
     *          @OA\JsonContent(ref="#/components/schemas/Request")
     *      ),
     *      @OA\Response(
     *          response=201,
     *          description="Successful operation",
     *          @OA\JsonContent(ref="#/components/schemas/Request")
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
                'requester_id' => ['numeric'],
                'requested_item_id' => ['required','numeric'],
                'requester_item_id' => ['required','numeric'],
                'rating' => ['required','numeric'],
                'type' => ['required','max:10'],
                'status' => ['max:15']
            ]);
            if ($validatedData->fails()) {
                return response()
                    ->json(
                        HelperClass::responeObject(null, false, Response::HTTP_BAD_REQUEST, "Validation failed check JSON request", "", $validatedData->errors()),
                        Response::HTTP_BAD_REQUEST
                    );
            }
            $user =$request->user();
            $request_already_exist = RequestOrder::where('requester_id',  $user->id)
                ->where('status', '!=', 'expired')
                ->where('requested_item_id', $request->requested_item_id)->first();
            if ($request_already_exist) { 
                return response()
                    ->json(
                        HelperClass::responeObject($request_already_exist, false, Response::HTTP_CONFLICT, 'Request already sent.', "",  "Request was already sent for this item."),
                        Response::HTTP_CONFLICT
                    );
            }
            $requestOrder = new RequestOrder($request->all()); 
            $requestOrder->status = "pending";
            $requestOrder->requester_id = $user->id;
            $requestOrder->token = Hash::make(Str::random());
            if ($requestOrder->save()) {
                $requestOrder->requester;
                $requestOrder->requested_item;
                $requestOrder->requester_item;
                if (strcmp($requestOrder->type,'item')==0) {
                    $requested_data = Item::where('id', $request->requested_item_id)->first();
                }else if(strcmp($requestOrder->type,'service')==0){
                    $requested_data = Service::where('id', $request->requested_item_id)->first();
                }
                if(!$requested_data){
                    return response()
                    ->json(
                        HelperClass::responeObject(null, false, Response::HTTP_BAD_REQUEST, "Not valid $requestOrder->type id passed", "", "$requestOrder->type doesnt exist by this id."),
                        Response::HTTP_BAD_REQUEST
                    ); 
                }
                $requested_data->number_of_request = (int)$requested_data->number_of_request + 1;
                if (!$requested_data->save()) {
                    return response()
                    ->json(
                        HelperClass::responeObject(null, false, Response::HTTP_INTERNAL_SERVER_ERROR, "Internal error", "", "The number of request on $requestOrder->type couldnt be updated."),
                        Response::HTTP_INTERNAL_SERVER_ERROR
                    );
                }
                //Make a count down to deduct from the limit of post of the user
                return response()
                ->json(
                    HelperClass::responeObject($requestOrder, true, Response::HTTP_CREATED, "Request saved.", "", "Request has been sent for this item."),
                    Response::HTTP_CREATED
                ); 
            } else {
                return response()
                ->json(
                    HelperClass::responeObject(null, false, Response::HTTP_INTERNAL_SERVER_ERROR, "This request couldnt be saved.", "", "This request couldnt be saved due to internal error."),
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
    public function requestCountByDate($attribute, $start, $end)
    {
        try {
            $items = RequestOrder::orderBy($attribute)->whereBetween($attribute, [$start, $end])->get()->groupBy(function ($item) {
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
     *      path="/request/{id}",
     *      operationId="getRequestById",
     *      tags={"Request"},
     *      summary="Get request information",
     *      description="Returns request data",
     *      @OA\Parameter(
     *          name="id",
     *          description="Request id",
     *          required=true,
     *          in="path",
     *          @OA\Schema(
     *              type="integer"
     *          )
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *          @OA\JsonContent(ref="#/components/schemas/Request")
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
            $user = $request->user();
            $validatedData = Validator::make($request->all(), [
                'requester_id' => ['numeric'],
                'requested_item_id' => ['numeric'],
                'requester_item_id' => ['numeric'],
                'rating' => ['numeric'],
                'type' => ['max:10'],
                'status' => ['max:15']
            ]);
            if ($validatedData->fails()) {
                return response()
                    ->json(
                        HelperClass::responeObject(null, false, Response::HTTP_BAD_REQUEST, "Validation failed check JSON request", "", $validatedData->errors()),
                        Response::HTTP_BAD_REQUEST
                    );
            } 
            $requests = RequestOrder::all();
            if ($requests->count() <= 0) {
                return response()
                    ->json(
                        HelperClass::responeObject($requests, true, Response::HTTP_OK, 'List of request.', "There is no request.", ""),
                        Response::HTTP_OK
                    );
            }
            $col = DB::getSchemaBuilder()->getColumnListing('requests');
            $user = $request->user();
            if($user){
                $request->request->add(['requester_id' => $user->id]);
            } 
            $input = $request->all(); 
            $requestKeys = collect($request->all())->keys();
            foreach ($requestKeys as $key) {
                if (in_array($key, $col)) {
                    $requests = $requests->where($key, $input[$key])->values();
                }
            }
            $requests->each(function ($item, $key) {
                $item->requester;
                $item->requested_item;
                $item->requester_item;
                $item->requested_item->type;
                $item->requester_item->type;
            });
            return response()->json($requests, 200);
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
     *      path="/request/{id}",
     *      operationId="updateRequest",
     *      tags={"Request"},
     *      summary="Update existing request",
     *      description="Returns updated request data",
     *      @OA\Parameter(
     *          name="id",
     *          description="Request id",
     *          required=true,
     *          in="path",
     *          @OA\Schema(
     *              type="integer"
     *          )
     *      ),
     *      @OA\RequestBody(
     *          required=true,
     *          @OA\JsonContent(ref="#/components/schemas/UpdateRequestRequest")
     *      ),
     *      @OA\Response(
     *          response=202,
     *          description="Successful operation",
     *          @OA\JsonContent(ref="#/components/schemas/Request")
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
                'requester_id' => ['numeric'],
                'requested_item_id' => ['numeric'],
                'requester_item_id' => ['numeric'],
                'rating' => ['numeric'],
                'type' => ['max:10'],
                'status' => ['max:15']
            ]);
            if ($validatedData->fails()) {
                return response()
                    ->json(
                        HelperClass::responeObject(null, false, Response::HTTP_BAD_REQUEST, "Validation failed check JSON request", "", $validatedData->errors()),
                        Response::HTTP_BAD_REQUEST
                    );
            }
            $input = $request->all();
            $requestOrder = RequestOrder::where('id', $id)->where('status', '!=', 'declined')->first();
            if(!$requestOrder){
                return response()
                ->json(
                    HelperClass::responeObject(null, false, Response::HTTP_BAD_REQUEST, "Request doesnt exist", "", "A request doesn't exist by this id."),
                    Response::HTTP_BAD_REQUEST
                ); 
            }
            if ($request->status) {
                if (strcmp($request->status, 'bartered')==0) {
                    if (strcmp($requestOrder->type,'item')==0) {
                        $requested_data = Item::where('id', $request->requested_item_id)->first();
                        $requester_data = Item::where('id', $request->requester_item_id)->first();
                    }else if(strcmp($requestOrder->type,'service')==0){
                        $requested_data = Service::where('id', $request->requested_item_id)->first();
                        $requester_data = Service::where('id', $request->requester_item_id)->first();
                    }
                    if(!$requested_data){
                        return response()
                        ->json(
                            HelperClass::responeObject(null, false, Response::HTTP_BAD_REQUEST, "Not valid $requestOrder->type id passed", "", "$requestOrder->type doesnt exist by this id."),
                            Response::HTTP_BAD_REQUEST
                        ); 
                    }
                    $requested_data->status = 'bartered';
                    $requested_data->save();
                    $requester_data->status = 'bartered';
                    $requester_data->save();
                }
            } 
            if ($requestOrder->fill($input)->save()) {
                $requestOrder->requester;
                $requestOrder->requested_item;
                $requestOrder->requester_item;
                return response()
                ->json(
                    HelperClass::responeObject($requestOrder, true, Response::HTTP_CREATED, "Request updated", "", "This request is updated sucessfully."),
                    Response::HTTP_CREATED
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
     * @OA\Delete(
     *      path="/request/{id}",
     *      operationId="deleteRequest",
     *      tags={"Request"},
     *      summary="Delete existing request",
     *      description="Deletes a record and returns no content",
     *      @OA\Parameter(
     *          name="id",
     *          description="Request id",
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
        //decrese the number of request in both item and service
        try {
            $request = RequestOrder::find($id);
            if (!$request) {
                response()
                    ->json(
                        HelperClass::responeObject(null, false, Response::HTTP_NOT_FOUND, "Resource Not Found", '', "Request by this id doesnt exist."),
                        Response::HTTP_NOT_FOUND
                    );
            }
            $request->delete();
            return response()
                ->json(
                    HelperClass::responeObject(null, true, Response::HTTP_OK, 'Successfully deleted.', "Request is deleted sucessfully.", ""),
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
