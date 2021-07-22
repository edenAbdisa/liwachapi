<?php

namespace App\Http\Controllers;

use Exception;
use App\Models\RequestOrder;
use Illuminate\Http\Request;
use Gate;
use App\Http\Resources\RequestResource;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

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
        $requestOrder = RequestOrder::all()
            ->each(function ($item, $key) {
                $item->requester;
                $item->requested_item;
                $item->requester_item;
            });
        return (new RequestResource($requestOrder))
            ->response()
            ->setStatusCode(Response::HTTP_OK);
    }
    public function statusCountRequest($status,$type)
    {
        //abort_if(Gate::denies('request_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');
        //User::with(['roles'])->get() 
        //$wordCount = Wordlist::where('id', '<=', $correctedComparisons)->count();
        $requestOrder = RequestOrder::where('status', '=', $status)
                        ->where('type', '=', $type)->count();
        return response()
        ->json($requestOrder,Response::HTTP_OK);
    } 
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
        $request = RequestOrder::create($request->all());
        //CHECK IF THE SESSION COOKIE OR THE TOKEN IS RIGH
        //IF IT ISNT RETURN HTTP_FORBIDDEN OR HTTP_BAD_REQUEST
        //dd("line 81"); 
        if ($request->save()) {
            $request->requester;
            $request->requested_item;
            $request->requester_item;
            return (new RequestResource($request))
                ->response()
                ->setStatusCode(Response::HTTP_CREATED);
        } else {
            return response()
                ->json("This resource couldn't be saved due to internal error", Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    public function requestCountByDate($attribute)
    {
        try{
        $items = Request::orderBy($attribute)->get()->groupBy(function($item) {
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
                $requests = $requests->where($key, $input[$key]);
            }
        }
        $requests->each(function ($item, $key) {
            $item->requester;
            $item->requested_item;
            $item->requester_item;
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
