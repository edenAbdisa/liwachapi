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
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
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
        $service = Service::where('status','!=','deleted')
            ->orWhereNull('status')->get()
            ->each(function ($item, $key) {
                $item->media;
                $item->bartering_location;
                $item->type;
                $item->user;
                $item->request;
                $item->serviceSwapType->each(function ($type, $key) {
                    $type->type;
                });
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
            $address = new Address($address);             
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
                        $service->request;
                        $service->serviceSwapType->each(function ($type, $key) {
                            $type->type;
                        });
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
        try {            
            $validatedData = Validator::make($request->all(),[ 
                'latitude' => ['required','numeric'],
                'longitude' => ['required','numeric']
            ]);
            if ($validatedData->fails()) {
                return response()
                ->json([
                    'data' =>null,
                    'success' => false,
                    'errors' => [
                        [
                            'status' => Response::HTTP_BAD_REQUEST,
                            'title' => "Validation failed check JSON request",
                            'message' => $validatedData->errors()
                        ],
                    ]
                ], Response::HTTP_BAD_REQUEST);
            }
        $addresses = Address::where('latitude', $input['latitude'])
                            ->where('longitude', $input['longitude'])
                            ->where('type', 'service')->get();
        if( $addresses->count() <= 0){
            return response()
                   ->json([
                          'data' => $addresses,
                          'success' =>  false,
                           'errors' => [
                                [
                                   'status' => Response::HTTP_NO_CONTENT,
                                    'title' => 'Address doesnt exist',
                                   'message' => "An address by the given inputs doesnt exist."
                                    ],
                                ]
                            ], Response::HTTP_OK);
        }
        
        $addresses->each(function ($address, $key) { 
            $address->service->user;
            $address->service->bartering_location;
            $address->service->media;
            $address->service->serviceSwapType->each(function ($type, $key) {
                $type->type;
            });
        });
        return response()
                ->json([
                    'data' =>$addresses,
                    'success' => true,
                    'errors' => [
                        [
                            'status' => Response::HTTP_OK,
                            'title' => 'List of address with their item.',
                            'message' => "These are the list of items near the address you choose."
                        ],
                    ]
                ], Response::HTTP_OK); 
        } catch (ModelNotFoundException $ex) { // User not found
            return response()
                    ->json([
                        'success' => false,
                        'errors' => [
                            [
                                'status' => RESPONSE::HTTP_UNPROCESSABLE_ENTITY,
                                'title' => 'The model doesnt exist.',
                                'message' => $ex->getMessage()
                            ],
                        ]
                    ], Response::HTTP_UNPROCESSABLE_ENTITY); 
        } catch (Exception $ex) { // Anything that went wrong
            return response()
                    ->json([
                        'success' => false,
                        'errors' => [
                            [
                                'status' => 500,
                                'title' => 'Internal server error',
                                'message' => $ex->getMessage()
                            ],
                        ]
                    ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
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
            $item->request;
            $item->serviceSwapType->each(function ($type, $key) {
                $type->type;
            });
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
        if ($request->address) {
            $address_to_be_updated=$request->address;
            $address = Address::where('id', $service->bartering_location_id)->first(); 
            $address->city=$address_to_be_updated['city'];  
            $address->country=$address_to_be_updated['country']; 
            $address->latitude=(float)$address_to_be_updated['latitude'];  
            $address->longitude=(float)$address_to_be_updated['longitude'];        
            $address->save(); 
       }
        if ($request->media) {
            $itemMedia = $request->media;
            foreach ($itemMedia as $m) {
                $mediaOld = Media::where('id', $m['id'])->first();
                $mediaOld->url=$m['url'];
                $mediaOld->save();
            }             
        }
        /* if (in_array('type_name', $input)) {
            $type = Type::where('name', $request->type_name)->first();
            $input['type_id'] = $type->id;
        } */
        //swap update isnt done
        if ($request->swap_type) {            
            $toBeRemoved=$request->swap_type["removed"];
            $newToBeSaved=$request->swap_type["added"];
            $oldSwap = ServiceSwapType::where('service_id', $service->id)
                                     ->where('type_id', $toBeRemoved)->get();
            ServiceSwapType::destroy($oldSwap);
            foreach ($newToBeSaved as $t) {
                 //check if the sent type id is in there 
                 $swap = new ServiceSwapType();
                 $swap->type_id = $t;
                 $swap->service_id = $service->id;
                 if (!$swap->save()) {
                     return response()
                         ->json("The swap type $swap resource couldn't be saved due to internal error", Response::HTTP_INTERNAL_SERVER_ERROR);
                 }
             }
         }
        if ($service->fill($input)->save()) {
            $service->media;
            $service->bartering_location;
            $service->type;
            $service->user;
            $service->request;
            $service->serviceSwapType->each(function ($type, $key) {
                $type->type;
            });
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
