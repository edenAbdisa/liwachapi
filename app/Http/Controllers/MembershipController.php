<?php

namespace App\Http\Controllers;

use App\Models\Membership;
use Illuminate\Http\Request;
use Gate;
use App\Http\Resources\MembershipResource;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
class MembershipController extends Controller
{
    /**
     * @OA\Get(
     *      path="/memberships",
     *      operationId="getMembershipesList",
     *      tags={"Membership"},
     *      summary="Get list of Membership",
     *      description="Returns list of Membership",
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *          @OA\JsonContent(ref="#/components/schemas/MembershipResource")
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
        //abort_if(Gate::denies('membership_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');
        //User::with(['roles'])->get() 
        return (new MembershipResource(Membership::all()))
            ->response()
            ->setStatusCode(Response::HTTP_OK);
    }


    /**
     * @OA\Post(
     *      path="/membership",
     *      operationId="storeMembership",
     *      tags={"Membership"},
     *      summary="Store new Membership",
     *      description="Returns membership data",
     *      @OA\RequestBody(
     *          required=true,
     *          @OA\JsonContent(ref="#/components/schemas/Membership")
     *      ),
     *      @OA\Response(
     *          response=201,
     *          description="Successful operation",
     *          @OA\JsonContent(ref="#/components/schemas/Membership")
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
        $input=$request->all();
        $input['name']=Str::ucfirst($input['name']);
        $membership = Membership::create($input);
        //CHECK IF THE SESSION COOKIE OR THE TOKEN IS RIGH
        //IF IT ISNT RETURN HTTP_FORBIDDEN OR HTTP_BAD_REQUEST
        //dd("line 81"); 
        if($membership->save()){ 
            return (new MembershipResource($membership))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
        }else{ 
            return response()
                   ->json("This resource couldn't be saved due to internal error", Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * @OA\Get(
     *      path="/memberships/{id}",
     *      operationId="getMembershipById",
     *      tags={"Memberships"},
     *      summary="Get membership information",
     *      description="Returns membership data",
     *      @OA\Parameter(
     *          name="id",
     *          description="Membership id",
     *          required=true,
     *          in="path",
     *          @OA\Schema(
     *              type="integer"
     *          )
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *          @OA\JsonContent(ref="#/components/schemas/Membership")
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
        $memberships = Membership::all();  
        $col=DB::getSchemaBuilder()->getColumnListing('memberships'); 
        $requestKeys = collect($request->all())->keys();       
        foreach ($requestKeys as $key) { 
            if(empty($memberships)){
                return response()->json($memberships, 200);
            }
            if(in_array($key,$col)){ 
                if($key=='name'){
                    $input[$key]= Str::ucfirst($input[$key]);
                }
                $memberships = $memberships->where($key,$input[$key]);
            }            
        } 
        return response()->json($memberships, 200); 
    }

    /**
     * @OA\Put(
     *      path="/memberships/{id}",
     *      operationId="updateMembership",
     *      tags={"Memberships"},
     *      summary="Update existing membership",
     *      description="Returns updated membership data",
     *      @OA\Parameter(
     *          name="id",
     *          description="Membership id",
     *          required=true,
     *          in="path",
     *          @OA\Schema(
     *              type="integer"
     *          )
     *      ),
     *      @OA\RequestBody(
     *          required=true,
     *          @OA\JsonContent(ref="#/components/schemas/UpdateMembershipRequest")
     *      ),
     *      @OA\Response(
     *          response=202,
     *          description="Successful operation",
     *          @OA\JsonContent(ref="#/components/schemas/Membership")
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
        $membership_to_be_updated= Membership::where('id',$id)->first();
        if(in_array('name',$input)){
            $membership= Membership::where('name',Str::ucfirst($request->name))->first();
            if($membership){
                return response()->json("A resource exist by this name.", Response::HTTP_CONFLICT);      
            }
            $input['name']=Str::ucfirst($input['name']);
        }
        
        if($membership_to_be_updated->fill($input)->save()){
            return (new MembershipResource($membership_to_be_updated))
                 ->response()
                ->setStatusCode(Response::HTTP_CREATED);
        } 
    }

    /**
     * @OA\Delete(
     *      path="/memberships/{id}",
     *      operationId="deleteMembership",
     *      tags={"Membershipess"},
     *      summary="Delete existing membership",
     *      description="Deletes a record and returns no content",
     *      @OA\Parameter(
     *          name="id",
     *          description="Membership id",
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
        $membership = Membership::findOrFail($id);
        $membership->delete();
        return response(null, Response::HTTP_NO_CONTENT);
    }
}
