<?php

namespace App\Http\Controllers;

use Exception;
use App\Models\ServiceSwapType;
use App\Models\Service;
use App\Models\Address;
use App\Models\Type;
use App\Models\Media;
use Illuminate\Http\Request;
use Gate;
use App\Http\Resources\ServiceResource;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class ServiceController extends Controller
{
    
    /**
     * @OA\Get(
     *      path="/service",
     *      operationId="getServiceList",
     *      tags={"Service"},
     *      summary="Get list of Service",
     *      description="Returns list of Service",
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *          @OA\JsonContent(ref="#/components/schemas/ServiceResource")
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
        //abort_if(Gate::denies('service_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');
        //User::with(['roles'])->get()
        $service = Service::all()
            ->each(function ($item, $key) {
                $item->media;
                $item->bartering_location;
                $item->type;
                $item->user;
                $item->serviceSwapType;
            });
        return (new ServiceResource($service))
            ->response()
            ->setStatusCode(Response::HTTP_OK);
    }

    public function countByStatus()
    {
        //abort_if(Gate::denies('request_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');
        //User::with(['roles'])->get() 
        //$wordCount = Wordlist::where('id', '<=', $correctedComparisons)->count();
        $serviceGrouped = Service::all()->groupBy(function($item) {
            return $item->status;
        });
        foreach($serviceGrouped as $key => $service){
            $serviceGrouped[$key]=$service->count();
           }          
        return response()
        ->json($serviceGrouped,Response::HTTP_OK);
    }
    /**
     * @OA\Post(
     *      path="/service",
     *      operationId="storeService",
     *      tags={"Service"},
     *      summary="Store new Service",
     *      description="Returns service data",
     *      @OA\RequestBody(
     *          required=true,
     *          @OA\JsonContent(ref="#/components/schemas/Service")
     *      ),
     *      @OA\Response(
     *          response=201,
     *          description="Successful operation",
     *          @OA\JsonContent(ref="#/components/schemas/Service")
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
        //$file = $request->file('picture');
        //if ($file) {
            //$filename = time() . '_' . $file->getClientOriginalName();
            //if (!HelperClass::uploadFile($file, $filename, 'files/services')) {
                // return response()
                //            ->json("The picture couldn't be uploaded", Response::HTTP_INTERNAL_SERVER_ERROR);
            //}
            $input = $request->all();
            $address = $request->address; 
            $address = Address::create($address);            
            $address->type = 'service';
            if ($address->save()) {
                //$type = Type::where('name', $request->type_name)->first();
                //if ($type) {
                    $input['status'] = 'open';
                    $input['number_of_flag'] = 0;
                    $input['number_of_request'] = 0;
                    $input['bartering_location_id'] = $address->id;
                    //$input['type_id'] = $type->id;
                    //$input['picture'] = $filename;
                    $service = Service::create($input);
                    //CHECK IF THE SESSION COOKIE OR THE TOKEN IS RIGH
                    //IF IT ISNT RETURN HTTP_FORBIDDEN OR HTTP_BAD_REQUEST                
                    if ($service->save()) {
                        $serviceSwapType = $request->swap_type;
                        foreach ($serviceSwapType as $t) {
                            //check if the sent type id is in there  
                            $swap = new ServiceSwapType();
                            $swap->type_id = $t;
                            $swap->service_id = $service->id;
                            if (!$swap->save()) {
                                return response()
                                    ->json("The swap type $swap resource couldn't be saved due to internal error", Response::HTTP_INTERNAL_SERVER_ERROR);
                            }
                        }
                        $serviceMedia = $request->media;
                        foreach ($serviceMedia as $m) {
                            //check if the sent type id is in there 
                            $media = new Media();
                            $media->type = 'service';
                            $media->url = $m;
                            $media->item_id = $service->id;
                            if (!$media->save()) {
                                return response()
                                    ->json("The media $media resource couldn't be saved due to internal error", Response::HTTP_INTERNAL_SERVER_ERROR);
                            }
                        }
                        $service->media;
                        $service->bartering_location;
                        $service->type;
                        $service->user;
                        $service->serviceSwapType;
                        return (new ServiceResource($service))
                            ->response()
                            ->setStatusCode(Response::HTTP_CREATED);
                    } else {
                        return response()
                            ->json("This resource couldn't be saved due to internal error", Response::HTTP_INTERNAL_SERVER_ERROR);
                    }
                //}
            }
        //}
    }
    public function serviceCountByDate($attribute,$start,$end)
    {
        try{
        $items = Service::orderBy($attribute)->whereBetween($attribute, [$start,$end])->get()->groupBy(function($item) {
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
    public function serviceByLocation(Request $request)
    {
        $input = $request->all();
        $addresses = Address::where('city', $input['city'])->where('type', 'service')->get();
        $addresses->each(function ($address, $key) {
            $address->service->serviceSwapType;
            $address->service->user;
            $address->service->bartering_location;
            $address->service->media;
            $address->service->serviceSwapType->each(function ($type, $key) {
                $type->type;
            });
        });
        return response()->json($addresses, 200);
    }
    /**
     * @OA\Get(
     *      path="/service/{id}",
     *      operationId="getServiceById",
     *      tags={"Service"},
     *      summary="Get service information",
     *      description="Returns service data",
     *      @OA\Parameter(
     *          name="id",
     *          description="Service id",
     *          required=true,
     *          in="path",
     *          @OA\Schema(
     *              service="integer"
     *          )
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *          @OA\JsonContent(ref="#/components/schemas/Service")
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
        $services = Service::all();
        $col = DB::getSchemaBuilder()->getColumnListing('services');
        $requestKeys = collect($request->all())->keys();
        foreach ($requestKeys as $key) {
            if (empty($services)) {
                return response()->json($services, 200);
            }
            if (in_array($key, $col)) {
                $services = $services->where($key, $input[$key])->values();
            }
        }
        $services->each(function ($item, $key) {
            $item->media;
            $item->bartering_location;
            $item->type;
            $item->user;
            $item->serviceSwapType;
        });

        return response()->json($services, 200);
    }

    /**
     * @OA\Put(
     *      path="/service/{id}",
     *      operationId="updateService",
     *      tags={"Service"},
     *      summary="Update existing service",
     *      description="Returns updated service data",
     *      @OA\Parameter(
     *          name="id",
     *          description="Service id",
     *          required=true,
     *          in="path",
     *          @OA\Schema(
     *              service="integer"
     *          )
     *      ),
     *      @OA\RequestBody(
     *          required=true,
     *          @OA\JsonContent(ref="#/components/schemas/UpdateServiceRequest")
     *      ),
     *      @OA\Response(
     *          response=202,
     *          description="Successful operation",
     *          @OA\JsonContent(ref="#/components/schemas/Service")
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
        $service = Service::where('id', $id)->first();
        if (in_array('address', $input)) {
            $address = Address::where('id', $service->bartering_location_id)->first();
            $address_to_be_updated=$input['address'];
            $address->fill($address_to_be_updated)->save();
        }
        /* if (in_array('type_name', $input)) {
            $type = Type::where('name', $request->type_name)->first();
            $input['type_id'] = $type->id;
        } */
        //swap update isnt done
        if ($service->fill($input)->save()) {
            $service->media;
            $service->bartering_location;
            $service->type;
            $service->user;
            $service->itemSwapType;
            return (new ServiceResource($service))
                ->response()
                ->setStatusCode(Response::HTTP_CREATED);
        }
    }

    /**
     * @OA\Delete(
     *      path="/service/{id}",
     *      operationId="deleteService",
     *      tags={"Service"},
     *      summary="Delete existing service",
     *      description="Deletes a record and returns no content",
     *      @OA\Parameter(
     *          name="id",
     *          description="Service id",
     *          required=true,
     *          in="path",
     *          @OA\Schema(
     *              service="integer"
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
        $service = Service::find($id);
        if (!$service) {
            return response()
                ->json("Resource Not Found", Response::HTTP_NOT_FOUND);
        }
        $service->status='deleted';
        $service->save();
        return response(null, Response::HTTP_NO_CONTENT);
    }
}
