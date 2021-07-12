<?php

namespace App\Http\Controllers;

use App\Models\ServiceSwapType;
use App\Models\Service;
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
        $service= Service::all()
                         ->each(function($item, $key) {
                            $item->bartering_location;
                            $item->type;
                            $item->serviceSwapType; 
                        }); 
        return (new ServiceResource($service))
            ->response()
            ->setStatusCode(Response::HTTP_OK);
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
        $file=$request->file('picture');
        if($file) {            
            $filename = time().'_'.$file->getClientOriginalName();
            if(!HelperClass::uploadFile($file,$filename, 'files/services')){
                return response()
                            ->json("The picture couldn't be uploaded", Response::HTTP_INTERNAL_SERVER_ERROR);
            }
            $address = $request->address;
            $address=json_decode( $address, true);
            $address['type'] = 'service'; 
            $address = Address::create($address);
            if($address->save()){ 
                $type= Type::where('name',$request->type_name)->first();
                if($type){
                    $input = $request->all();            
                    $input['status']='open';
                    $input['number_of_flag']=0;
                    $input['number_of_request']=0; 
                    $input['bartering_location_id']=$address->id;            
                    $input['type_id']=$type->id;
                    $input['picture']=$filename;
                    $service = Service::create($input);
                    //CHECK IF THE SESSION COOKIE OR THE TOKEN IS RIGH
                    //IF IT ISNT RETURN HTTP_FORBIDDEN OR HTTP_BAD_REQUEST                
                    if($service->save()){ 
                        $serviceSwapType=json_decode($input["swap_type"]);
                        foreach ($serviceSwapType as $t) {
                            //check if the sent type id is in there      
                            if(!ServiceSwapType::create(["type_id" => $t],
                            ["service_id" => $service->id])->save()){
                                return response()
                                ->json("The swap type $swap resource couldn't be saved due to internal error", Response::HTTP_INTERNAL_SERVER_ERROR);                     
                             }
                        }
                        $service->bartering_location;
                        $service->type; 
                        return (new ServiceResource($service))
                        ->response()
                        ->setStatusCode(Response::HTTP_CREATED);
                    }else{ 
                        return response()
                            ->json("This resource couldn't be saved due to internal error", Response::HTTP_INTERNAL_SERVER_ERROR);
                    }
                }
            }
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
        $col=DB::getSchemaBuilder()->getColumnListing('services'); 
        $requestKeys = collect($request->all())->keys();       
        foreach ($requestKeys as $key) { 
            if(empty($services)){
                return response()->json($services, 200);
            }
            if(in_array($key,$col)){ 
                $services = $services->where($key,$input[$key]);
            }            
        } 
        $services->each(function($item, $key) {
                            $item->bartering_location;
                            $item->type; 
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
        $service= Service::where('id',$id)->first();
        if($service->fill($input)->save()){
            $service->bartering_location;
            $service->type;
            return ($service)
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
        if(!$service){
            return response()
                   ->json("Resource Not Found", Response::HTTP_NOT_FOUND);
     
        }
        $service->delete();
        return response(null, Response::HTTP_NO_CONTENT);
    } 
}
