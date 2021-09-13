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
        try {
            $service = Service::where('status', '!=', 'deleted')
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
            return response()
                ->json(
                    HelperClass::responeObject($service, true, Response::HTTP_OK, 'Successfully fetched.', "Item are fetched sucessfully.", ""),
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

    public function countByStatus()
    {
        //abort_if(Gate::denies('request_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');
        //User::with(['roles'])->get() 
        //$wordCount = Wordlist::where('id', '<=', $correctedComparisons)->count();
        $serviceGrouped = Service::all()->groupBy(function ($item) {
            return $item->status;
        });
        foreach ($serviceGrouped as $key => $service) {
            $serviceGrouped[$key] = $service->count();
        }
        return response()
            ->json($serviceGrouped, Response::HTTP_OK);
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
        try {
            $validatedData = Validator::make($request->all(), [
                'name' => ['required', 'max:50'],
                'description' => ['required', 'max:255'],
                'type_id' => ['required', 'numeric'],
                'address.latitude' => ['required', 'numeric'],
                'address.longitude' => ['required', 'numeric'],
                'address.country' => ['required', 'max:50'],
                'address.city' => ['required', 'max:50'],
                'address.type' => ['required', 'max:10', Rule::in(['service'])]

            ]);
            if ($validatedData->fails()) {
                return response()
                    ->json(
                        HelperClass::responeObject(null, false, Response::HTTP_BAD_REQUEST, "Validation failed check JSON request", "", $validatedData->errors()),
                        Response::HTTP_BAD_REQUEST
                    );
            }
            $input = $request->all();
            $user = $request->user();
            $address = $request->address;
            $address = new Address($address);
            $address->type = 'service';
            if ($address->save()) {
                $type = Type::where('id', $request->type_id)->where('status', '!=', 'deleted')->first();
                if (!$type) {
                    return response()
                        ->json(
                            HelperClass::responeObject(null, false, Response::HTTP_BAD_REQUEST, "Not valid type id passed", "", "A type doesnt exist by this id."),
                            Response::HTTP_BAD_REQUEST
                        );
                }
                $input['status'] = 'open';
                $input['number_of_flag'] = 0;
                $input['number_of_request'] = 0;
                $input['bartering_location_id'] = $address->id;
                $service = new Service($input);
                $service->user_id = $user->id;
                if ($service->save()) {
                    $serviceSwapType = $request->swap_type;
                    foreach ($serviceSwapType as $t) {
                        $type = Type::where('id', $t)->where('used_for','service')->where('status', '!=', 'deleted')->first();
                        if (!$type) {
                            return response()
                                ->json(
                                    HelperClass::responeObject(null, false, Response::HTTP_BAD_REQUEST, "Can't do a swap due to selected type error.", "", "A type doesnt exist by the id $t that is used for service."),
                                    Response::HTTP_BAD_REQUEST
                                );
                        }
                        $swap = new ServiceSwapType();
                        $swap->type_id = $t;
                        $swap->service_id = $service->id;
                        if (!$swap->save()) {
                            return  response()
                                ->json(
                                    HelperClass::responeObject(null, false, Response::HTTP_INTERNAL_SERVER_ERROR, "Inernal error", "", "The swap type $swap resource couldn't be saved due to internal error"),
                                    Response::HTTP_INTERNAL_SERVER_ERROR
                                );
                        }
                    }
                    /* $serviceMedia = $request->media;
                foreach ($serviceMedia as $m) {
                    //check if the sent type id is in there 
                    $media = new Media();
                    $media->type = 'service';
                    $media->url = $m;
                    $media->item_id = $service->id;
                    if (!$media->save()) {
                        return  response()
                        ->json(
                            HelperClass::responeObject(null, false, Response::HTTP_INTERNAL_SERVER_ERROR, "Internal error", "", "The media $media resource couldn't be saved due to internal error"),
                            Response::HTTP_INTERNAL_SERVER_ERROR
                        );
                     }
                }
                $service->media; */
                    $service->bartering_location;
                    $service->type;
                    $service->user;
                    $service->request;
                    $service->serviceSwapType->each(function ($type, $key) {
                        $type->type;
                    });
                    return  response()
                        ->json(
                            HelperClass::responeObject($service, true, Response::HTTP_CREATED, "Item created.", "The item has been created.", ""),
                            Response::HTTP_CREATED
                        );
                } else {
                    return  response()
                        ->json(
                            HelperClass::responeObject(null, false, Response::HTTP_INTERNAL_SERVER_ERROR, "Item couldn't be saved.", "", "Item couldn't be saved"),
                            Response::HTTP_INTERNAL_SERVER_ERROR
                        );
                }
            } else {
                return  response()
                    ->json(
                        HelperClass::responeObject(null, false, Response::HTTP_INTERNAL_SERVER_ERROR, "Address couldn't be saved.", "",  "Address couldn't be saved"),
                        Response::HTTP_INTERNAL_SERVER_ERROR
                    );
            }
        } catch (ModelNotFoundException $ex) { // User not found
            return response()
                ->json(
                    HelperClass::responeObject(null, false, RESPONSE::HTTP_UNPROCESSABLE_ENTITY, 'The model doesnt exist.', "", $ex->getMessage()),
                    Response::HTTP_UNPROCESSABLE_ENTITY
                );
        } catch (Exception $ex) { 
            return response()
                ->json(
                    HelperClass::responeObject(null, false, RESPONSE::HTTP_UNPROCESSABLE_ENTITY, 'Internal server error.', "", $ex->getMessage()),
                    Response::HTTP_UNPROCESSABLE_ENTITY
                );
        }
    }
    public function serviceCountByDate($attribute, $start, $end)
    {
        try {
            $items = Service::orderBy($attribute)->whereBetween($attribute, [$start, $end])->get()->groupBy(function ($item) {
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
    public function serviceByLocation(Request $request)
    {
        $input = $request->all();
        try {
            $validatedData = Validator::make($request->all(), [
                'latitude' => ['required', 'numeric'],
                'longitude' => ['required', 'numeric']
            ]);
            if ($validatedData->fails()) {
                return response()
                    ->json(
                        HelperClass::responeObject(null, false, Response::HTTP_BAD_REQUEST, "Validation failed check JSON request", "", $validatedData->errors()),
                        Response::HTTP_BAD_REQUEST
                    );
            }
            $addresses = DB::table("addresses")
            ->select(
                "*",
                DB::raw("6371 * acos(cos(radians(" . $request->latitude . ")) * cos(radians(latitude)) 
                        * cos(radians(longitude) - radians(" . $request->longitude . ")) 
                        + sin(radians(" . $request->latitude . ")) 
                        * sin(latitude))) AS distance")
            )
            ->having("distance", "<", 20)
            ->orderBy('distance', 'asc')
            ->where('type', 'service')->get();
            if ($addresses->count() <= 0) {
                return response()
                    ->json(
                        HelperClass::responeObject($addresses, true, Response::HTTP_OK, 'Service doesnt exist', "Service doesn't exist within your location.", ""),
                        Response::HTTP_OK
                    );
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
                ->json(
                    HelperClass::responeObject($addresses, true, Response::HTTP_OK, 'List of address with their service.', "These are the list of services near the address you choose.", ""),
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
                    HelperClass::responeObject(null, false, RESPONSE::HTTP_UNPROCESSABLE_ENTITY, 'Internal server error.', "", $ex->getMessage()),
                    Response::HTTP_UNPROCESSABLE_ENTITY
                );
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
        try {
            $validatedData = Validator::make($request->all(), [
                'name' => ['max:50'],
                'description' => ['max:255'],
                'status' => ['max:15'],
                'number_of_flag' => ['numeric'],
                'number_of_request' => ['numeric'],
                'bartering_location_id' => ['numeric'],
                'type_id' => ['numeric'],
                'user_id' => ['numeric']
            ]);
            if ($validatedData->fails()) {
                return response()
                    ->json(
                        HelperClass::responeObject(null, false, Response::HTTP_BAD_REQUEST, "Validation failed check JSON request", "", $validatedData->errors()),
                        Response::HTTP_BAD_REQUEST
                    );
            }
            $services = Service::all();
            if ($services->count() <= 0) {
                return response()
                    ->json(
                        HelperClass::responeObject($services, true, Response::HTTP_OK, 'List of services.', "There is no service.", ""),
                        Response::HTTP_OK
                    );
            }
            $col = DB::getSchemaBuilder()->getColumnListing('services');
            $user = $request->user(); 
            if($user){ 
                $request->request->add(['user_id' => $user->id]);
            }  
            $requestKeys = collect($request->all())->keys();
            
            $input = $request->all();
            foreach ($requestKeys as $key) {
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
            return response()
                ->json(
                    HelperClass::responeObject($services, true, Response::HTTP_OK, 'List of items.', "These are the list of items based on your search.", ""),
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
        try {
            $validatedData = Validator::make($request->all(), [
                'name' => ['max:50'],
                'description' => ['max:255'],
                'status' => ['max:15'],
                'number_of_flag' => ['numeric'],
                'number_of_request' => ['numeric'],
                'bartering_location_id' => ['numeric'],
                'type_id' => ['numeric']
            ]);
            if ($validatedData->fails()) {
                return response()
                    ->json(
                        HelperClass::responeObject(null, false, Response::HTTP_BAD_REQUEST, "Validation failed check JSON request", "", $validatedData->errors()),
                        Response::HTTP_BAD_REQUEST
                    );
            }
            $input = $request->all();
            $service = Service::where('id', $id)->first();
            if ($request->address) {
                $address_to_be_updated = $request->address;
                $address = Address::where('id', $service->bartering_location_id)->first();
                $address->city = $address_to_be_updated['city'];
                $address->country = $address_to_be_updated['country'];
                $address->latitude = (float)$address_to_be_updated['latitude'];
                $address->longitude = (float)$address_to_be_updated['longitude'];
                $address->save();
            }
            if ($request->media) {
                $itemMedia = $request->media;
                foreach ($itemMedia as $m) {
                    $mediaOld = Media::where('id', $m['id'])->first();
                    $mediaOld->url = $m['url'];
                    $mediaOld->save();
                }
            }           
            if ($service->fill($input)->save()) {
                if ($request->swap_type) {
                    $toBeRemoved = $request->swap_type["removed"];
                    $newToBeSaved = $request->swap_type["added"];
                    $oldSwap = ServiceSwapType::where('service_id', $service->id)
                        ->where('type_id', $toBeRemoved)->get();
                    ServiceSwapType::destroy($oldSwap);
                    foreach ($newToBeSaved as $t) {
                        $type = Type::where('id', $t)->where('used_for','service')->where('status', '!=', 'deleted')->first();
                            if (!$type) {
                                return response()
                                    ->json(
                                        HelperClass::responeObject(null, false, Response::HTTP_BAD_REQUEST, "Can't do a swap due to selected type error.", "", "A type doesnt exist by the id $t that is used for service."),
                                        Response::HTTP_BAD_REQUEST
                                    );
                        } 
                        $swap = new ServiceSwapType();
                        $swap->type_id = $t;
                        $swap->service_id = $service->id;
                        if (!$swap->save()) {
                            return  response()
                                    ->json(
                                        HelperClass::responeObject(null, false, Response::HTTP_INTERNAL_SERVER_ERROR, "Inernal error", "", "The swap type $swap resource couldn't be saved due to internal error"),
                                        Response::HTTP_INTERNAL_SERVER_ERROR
                                    );
                         }
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
                return response()
                ->json(
                    HelperClass::responeObject($service, true, Response::HTTP_OK, 'Service detail.', "Service updated successfully.", ""),
                    Response::HTTP_OK
                );
            }
        } catch (ModelNotFoundException $ex) { 
            return response()
                ->json(
                    HelperClass::responeObject(null, false, RESPONSE::HTTP_UNPROCESSABLE_ENTITY, 'The model doesnt exist.', "", $ex->getMessage()),
                    Response::HTTP_UNPROCESSABLE_ENTITY
                );
        } catch (Exception $ex) { 
            return response()
                ->json(
                    HelperClass::responeObject(null, false, RESPONSE::HTTP_UNPROCESSABLE_ENTITY, 'Internal server error.', "", $ex->getMessage()),
                    Response::HTTP_UNPROCESSABLE_ENTITY
                );
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
        try {
            $service = Service::find($id);
            if (!$service) {
                response()
                    ->json(
                        HelperClass::responeObject(null, false, Response::HTTP_NOT_FOUND, "Resource Not Found", '', "Service by this id doesnt exist."),
                        Response::HTTP_NOT_FOUND
                    );
            }
            $service->status = 'deleted';
            $service->save();
            return response()
                ->json(
                    HelperClass::responeObject(null, true, Response::HTTP_NO_CONTENT, 'Successfully deleted.', "Service is deleted sucessfully.", ""),
                    Response::HTTP_NO_CONTENT
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
