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
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

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
        //abort_if(Gate::denies('flag_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');
        //User::with(['roles'])->get() 
        $flag = Flag::all();
        foreach ($flag as $f) {
            $f->reason;
            $f->flagged_by;
            $f->flagged_item;
        }
        return (new FlagResource($flag))
            ->response()
            ->setStatusCode(Response::HTTP_OK);
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
        $input = $request->all();
        $reason = ReportType::where('report_detail', $input['reason'])->first();
        if (!$reason) {
            return response()
                ->json("No such kind of report type", Response::HTTP_INTERNAL_SERVER_ERROR);
        }
        $input['reason_id'] = $reason->id;
        $flag = Flag::create($input);
        //CHECK IF THE SESSION COOKIE OR THE TOKEN IS RIGH
        //IF IT ISNT RETURN HTTP_FORBIDDEN OR HTTP_BAD_REQUEST
        //dd("line 81"); 
        if ($flag->save()) {
            if ($flag->type==='item') {
                $flagged_item=Item::where('id',$flag->flagged_item_id);
                $flagged_item->number_of_flag=$flagged_item->number_of_flag+1;
                $flagged_item->save();
            }else{
                $flagged_service=Service::where('id',$flag->flagged_item_id);
                $flagged_service->number_of_flag=$flagged_service->number_of_flag+1;
                $flagged_service->save();
            }  
            return (new FlagResource($flag))
                ->response()
                ->setStatusCode(Response::HTTP_CREATED);
        } else {
            return response()
                ->json("This resource couldn't be saved due to internal error", Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    public function flaggedProductCountByDate($attribute,$start,$end)
    {
        try{
        $items = Flag::orderBy($attribute)->whereBetween($attribute, [$start,$end])->get()->groupBy(function($item) {
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
        $input = $request->all();
        $flags = Flag::all();
        $col = DB::getSchemaBuilder()->getColumnListing('flags');
        $requestKeys = collect($request->all())->keys();
        foreach ($requestKeys as $key) {
            if (empty($flags)) {
                return response()->json($flags, 200);
            }
            if (in_array($key, $col)) {
                $flags = $flags->where($key, $input[$key])->values(); 
            }
        }
        $flags->each(function ($flag, $key) { 
            $flag->reason;
            $flag->flagged_by;
            $flag->flagged_item;
        });
        return response()->json($flags, 200);
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
        $input = $request->all();
        $flag = Flag::where('id', $id)->first();
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
        $flag = Flag::find($id);
        if (!$flag) {
            return response()
                ->json("Resource Not Found", Response::HTTP_NOT_FOUND);
        }
        $flag->delete();
        return response(null, Response::HTTP_NO_CONTENT);
    }
}
