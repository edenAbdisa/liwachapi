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
        //abort_if(Gate::denies('request_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');
        //User::with(['roles'])->get() 
        $requestOrder = RequestOrder::where('status','!=','deleted')
        ->orWhereNull('status')->get()
            ->each(function ($item, $key) {
                $item->requester;
                $item->requested_item;
                $item->requester_item;                
                $item->requested_item->type;
                $item->requester_item->type;
            });
        return (new RequestResource($requestOrder))
            ->response()
            ->setStatusCode(Response::HTTP_OK);
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
        $userGrouped = RequestOrder::where('type', '=', $type)->get()->groupBy(function($item) {
            return $item->status;
        });
        foreach($userGrouped as $key => $user){
            $day = $key;
            $totalCount = $user->count();
            $userGrouped[$key]=$totalCount;
           }          
        return response()
        ->json($userGrouped,Response::HTTP_OK);
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
        
        //CHECK IF THE SESSION COOKIE OR THE TOKEN IS RIGH
        //IF IT ISNT RETURN HTTP_FORBIDDEN OR HTTP_BAD_REQUEST
        //dd("line 81"); 
        $request_already_exist=RequestOrder::where('requester_id',$request->requester_id)
                               ->where('status','!=','expired')
                               ->where('requested_item_id',$request->requested_item_id)->first();
        if($request_already_exist){
            //Can add set data to send a datamessage fitsums
            return (new RequestResource($request_already_exist))
                ->response()
                ->setStatusCode(Response::HTTP_CREATED);
        }
        $requestOrder = new RequestOrder($request->all());
        $requestOrder->status="pending"; 
        $requestOrder->token=Hash::make(Str::random());
        if ($requestOrder->save()) {
            $requestOrder->requester;
            $requestOrder->requested_item;
            $requestOrder->requester_item;
            if ($requestOrder->type==='item') {
                $requested_item=Item::where('id',$request->requested_item_id)->first();
                $requested_item->number_of_request=(int)$requested_item->number_of_request + 1;
                if(!$requested_item->save()){
                    return response()
                    ->json([
                        'errors' => [
                            [
                                'status' => 500,
                                'title' => 'Internal server error',
                                'message' => 'The number of request couldnt be updated'
                            ],
                        ]
                    ], Response::HTTP_INTERNAL_SERVER_ERROR);        
                }
                // $requester_item=Item::where('id',$request->requester_item_id);
                // $requester_item->number_of_request=$requester_item->number_of_request+1;
                // $requester_item->save();
            }else{
                $requested_service=Service::where('id',$request->requested_item_id)->first();
                $requested_service->number_of_request=(int)$requested_service->number_of_request+1;
                if(!$requested_service->save()){
                    return response()
                    ->json([
                        'errors' => [
                            [
                                'status' => 500,
                                'title' => 'Internal server error',
                                'message' => 'The number of request couldnt be updated'
                            ],
                        ]
                    ], Response::HTTP_INTERNAL_SERVER_ERROR);       
                }
                // $requester_service=Service::where('id',$request->requester_item_id);
                // $requester_service->number_of_request=$requester_service->number_of_request+1;
                // $requester_service->save();
            }
            return (new RequestResource($requestOrder))
                ->response()
                ->setStatusCode(Response::HTTP_CREATED);
        } else {
            return response()
                ->json([
                    'errors' => [
                        [
                            'status' => 500,
                            'title' => 'Internal server error',
                            'message' => 'A more detailed error message to show the end user'
                        ],
                    ]
                ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    public function requestCountByDate($attribute,$start,$end)
    {
        try{
        $items = RequestOrder::orderBy($attribute)->whereBetween($attribute, [$start,$end])->get()->groupBy(function($item) {
             return $item->created_at->format('Y-m-d');
       });
       }catch(Exception $e){
        return response()
        ->json("There is no such attribute.",Response::HTTP_OK);
       }
       foreach($items as $key => $item){
        $day = $key;
        $totalCount = $item->count();
        $items[$key]=$totalCount;
       }
        return response()
            ->json($items,Response::HTTP_OK);
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
        $input = $request->all();
        $requests = RequestOrder::all();
        $col = DB::getSchemaBuilder()->getColumnListing('requests');
        $requestKeys = collect($request->all())->keys();
        foreach ($requestKeys as $key) {
            if (empty($requests)) {
                return response()->json($requests, 200);
            }
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
        $input = $request->all();
        $request = RequestOrder::where('id', $id)->first();
        if (in_array('status', $input)) {
            if($input['status']==='bartered'){
                if ($request->type==='item') {
                    $requested_item=Item::where('id',$request->requested_item_id);
                    $requested_item->status='bartered';
                    $requested_item->save();
                    $requester_item=Item::where('id',$request->requester_item_id);
                    $requester_item->status='bartered';
                    $requester_item->save();
                }else{
                    $requested_service=Service::where('id',$request->requested_item_id);
                    $requested_service->status='bartered';
                    $requested_service->save();
                    $requester_service=Service::where('id',$request->requester_item_id);
                    $requester_service->status='bartered';
                    $requester_service->save();
                }
            }
        }
        if ($request->fill($input)->save()) {
            $request->requester;
            $request->requested_item;
            $request->requester_item;
            return (new RequestResource($request))
                ->response()
                ->setStatusCode(Response::HTTP_CREATED);
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
        $request = RequestOrder::find($id);
        if (!$request) {
            return response()
                ->json("Resource Not Found", Response::HTTP_NOT_FOUND);
        }
        $request->delete();
        return response(null, Response::HTTP_NO_CONTENT);
    }
}
