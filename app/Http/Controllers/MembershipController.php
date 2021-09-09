<?php

namespace App\Http\Controllers;
use Exception;
use App\Models\Membership;
use Illuminate\Http\Request;
use Gate;
use App\Http\Resources\MembershipResource;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
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
        $membership = Membership::where('status', '=', 'active')->orWhereNull('status')->get();

        return (new MembershipResource($membership))
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
        try{ 
            $validatedData = Validator::make($request->all(),[ 
                'name' => ['required','max:30'],
                'limit_of_post' => ['required','numeric','min:0','not_in:0'],
                'transaction_limit' => ['required','numeric','min:0','not_in:0']
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
        $membership = Membership::where('name', Str::ucfirst($request->name))->first();
        if(!$membership){
        $input = $request->all();
        $input['name'] = Str::ucfirst($input['name']);
        $membership = new Membership($input);
        $membership->status="active"; 
        //CHECK IF THE SESSION COOKIE OR THE TOKEN IS RIGH
        //IF IT ISNT RETURN HTTP_FORBIDDEN OR HTTP_BAD_REQUEST
        //dd("line 81"); 
        if ($membership->save()) {
            return response()
                ->json([
                    'data' =>$membership,
                    'success' => true,
                    'errors' => [
                        [
                            'status' => Response::HTTP_CREATED,
                            'title' => 'Membership created.',
                            'message' => "The membership is created sucessfully."
                        ],
                    ]
                ], Response::HTTP_CREATED);  
        } else {
            return response()
                    ->json([
                        'data' =>$membership ,
                        'success' => false,
                        'errors' => [
                            [
                                'status' => Response::HTTP_INTERNAL_SERVER_ERROR,
                                'title' => 'Internal error',
                                'message' => "This membership couldnt be saved."
                            ],
                        ]
                    ], Response::HTTP_INTERNAL_SERVER_ERROR);
        
        }
    } else {
        return response()
                ->json([
                    'data' =>$membership ,
                    'success' => false,
                    'errors' => [
                        [
                            'status' => Response::HTTP_CONFLICT,
                            'title' => 'Membership already exist.',
                            'message' => "This membership already exist in the database."
                        ],
                    ]
                ], Response::HTTP_CONFLICT);    
    }
}catch (ModelNotFoundException $ex) { // User not found
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
        $col = DB::getSchemaBuilder()->getColumnListing('memberships');
        $requestKeys = collect($request->all())->keys();
        foreach ($requestKeys as $key) {
            if (empty($memberships)) {
                return response()->json($memberships, 200);
            }
            if (in_array($key, $col)) {
                if ($key == 'name') {
                    $input[$key] = Str::ucfirst($input[$key]);
                }
                $memberships = $memberships->where($key, $input[$key])->values();
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
        try{ 
            $validatedData = Validator::make($request->all(),[ 
                'name' => ['max:30'],
                'limit_of_post' => ['numeric','min:0','not_in:0'],
                'transaction_limit' => ['numeric','min:0','not_in:0']
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
        $input = $request->all();
        $membership_to_be_updated = Membership::where('id', $id)->first(); 
        if(!$membership_to_be_updated){
            return response()
            ->json([
                'data' =>null ,
                'success' => false,
                'errors' => [
                    [
                        'status' => Response::HTTP_CONFLICT,
                        'title' => 'Membership doesnt exist.',
                        'message' => "This membership doesnt exist in the database."
                    ],
                ]
            ], Response::HTTP_CONFLICT); 
        }
        if (in_array('name', $input)) {
            $membership = Membership::where('name', Str::ucfirst($request->name))->first();
            if ($membership) {
                return response()
                ->json([
                    'data' =>$membership ,
                    'success' => false,
                    'errors' => [
                        [
                            'status' => Response::HTTP_CONFLICT,
                            'title' => 'Membership already exist.',
                            'message' => "This membership already exist in the database."
                        ],
                    ]
                ], Response::HTTP_CONFLICT);       }
            $input['name'] = Str::ucfirst($input['name']);
        }

        if ($membership_to_be_updated->fill($input)->save()) {
            return response()
                ->json([
                    'data' =>$membership_to_be_updated,
                    'success' => true,
                    'errors' => [
                        [
                            'status' => Response::HTTP_CREATED,
                            'title' => 'Membership updated.',
                            'message' => "The membership is updated sucessfully."
                        ],
                    ]
                ], Response::HTTP_CREATED);
        }else {
            return response()
                    ->json([
                        'data' =>$membership ,
                        'success' => false,
                        'errors' => [
                            [
                                'status' => Response::HTTP_INTERNAL_SERVER_ERROR,
                                'title' => 'Internal error',
                                'message' => "This membership couldnt be saved."
                            ],
                        ]
                    ], Response::HTTP_INTERNAL_SERVER_ERROR);
        
        }
    }catch (ModelNotFoundException $ex) { // User not found
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
        $membership = Membership::find($id);
        if (!$membership) {
            return response()
                ->json("Resource Not Found", Response::HTTP_NOT_FOUND);
        }
        $membership->status='deleted';
        $membership->save(); 
        return response(null, Response::HTTP_NO_CONTENT);
    }
}
