<?php

namespace App\Http\Controllers;
use Exception;
use App\Models\Type;
use App\Models\Category;
use Illuminate\Http\Request;
use Gate;
use App\Http\Resources\TypeResource;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
class TypeController extends Controller
{
    /**
     * @OA\Get(
     *      path="/type",
     *      operationId="getTypeList",
     *      tags={"Type"},
     *      summary="Get list of Type",
     *      description="Returns list of Type",
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *          @OA\JsonContent(ref="#/components/schemas/TypeResource")
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
        //abort_if(Gate::denies('type_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');
        //User::with(['roles'])->get() 
        $type = Type::where('status', '=', 'active')->orWhereNull('status')->get()
            ->each(function ($item, $key) {
                $item->category;
            });
        return (new TypeResource($type))
            ->response()
            ->setStatusCode(Response::HTTP_OK);
    }


    /**
     * @OA\Post(
     *      path="/type",
     *      operationId="storeType",
     *      tags={"Type"},
     *      summary="Store new Type",
     *      description="Returns type data",
     *      @OA\RequestBody(
     *          required=true,
     *          @OA\JsonContent(ref="#/components/schemas/Type")
     *      ),
     *      @OA\Response(
     *          response=201,
     *          description="Successful operation",
     *          @OA\JsonContent(ref="#/components/schemas/Type")
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
                'category_id' => ['required','numeric']
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
        $type = Type::where('name', Str::ucfirst($request->name))
                    ->where('category_id',$request->category_id)
                    ->first();
        if (!$type) {
            $category = Category::where('id', $request->category_id)->first();
            if (!$category) {
                return response()
                ->json([
                    'data' =>$category ,
                    'success' => false,
                    'errors' => [
                        [
                            'status' => Response::HTTP_CONFLICT,
                            'title' => "Category doesn't exist.",
                            'message' => "A category with this ID doesn't exist in the database.Please select the right category."
                        ],
                    ]
                ], Response::HTTP_CONFLICT); 
            }
            $type = new Type($request->all());
            $type->status="active";
            $type->used_for=$category->used_for; 
            //CHECK IF THE SESSION COOKIE OR THE TOKEN IS RIGH
            //IF IT ISNT RETURN HTTP_FORBIDDEN OR HTTP_BAD_REQUEST

                if ($type->save()) {
                    $type->category; 
                        return response()
                    ->json([
                        'data' =>$type,
                        'success' => true,
                        'errors' => [
                            [
                                'status' => Response::HTTP_CREATED,
                                'title' => 'Type created.',
                                'message' => "The type is created sucessfully."
                            ],
                        ]
                    ], Response::HTTP_CREATED); 
                } else {
                    return response()
                        ->json([
                            'data' =>$type ,
                            'success' => false,
                            'errors' => [
                                [
                                    'status' => Response::HTTP_INTERNAL_SERVER_ERROR,
                                    'title' => 'Internal error',
                                    'message' => "This type couldnt be saved."
                                ],
                            ]
                        ], Response::HTTP_INTERNAL_SERVER_ERROR);
                    }
        } else {
                return response()
                ->json([
                    'data' =>$type ,
                    'success' => false,
                    'errors' => [
                        [
                            'status' => Response::HTTP_CONFLICT,
                            'title' => 'Type already exist.',
                            'message' => "This type already exist in the database."
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
     *      path="/type/{id}",
     *      operationId="getTypeById",
     *      tags={"Type"},
     *      summary="Get type information",
     *      description="Returns type data",
     *      @OA\Parameter(
     *          name="id",
     *          description="Type id",
     *          required=true,
     *          in="path",
     *          @OA\Schema(
     *              type="integer"
     *          )
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *          @OA\JsonContent(ref="#/components/schemas/Type")
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
        $types = Type::all();
        $col = DB::getSchemaBuilder()->getColumnListing('types');
        $requestKeys = collect($request->all())->keys();
        foreach ($requestKeys as $key) {
            if (empty($types)) {
                return response()->json($types, 200);
            }
            if (in_array($key, $col)) {
                $types = $types->where($key, $input[$key])->values();
            }
        }
        $types->each(function ($item, $key) {
            $item->category;
            $item->service;
            $item->item;
        });
        return response()->json($types, 200);
    }

    /**
     * @OA\Put(
     *      path="/type/{id}",
     *      operationId="updateType",
     *      tags={"Type"},
     *      summary="Update existing type",
     *      description="Returns updated type data",
     *      @OA\Parameter(
     *          name="id",
     *          description="Type id",
     *          required=true,
     *          in="path",
     *          @OA\Schema(
     *              type="integer"
     *          )
     *      ),
     *      @OA\RequestBody(
     *          required=true,
     *          @OA\JsonContent(ref="#/components/schemas/UpdateTypeRequest")
     *      ),
     *      @OA\Response(
     *          response=202,
     *          description="Successful operation",
     *          @OA\JsonContent(ref="#/components/schemas/Type")
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
                'category_id' => ['numeric']
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
        $type_to_be_updated = Type::where('id', $id)->first();
        if(!$type_to_be_updated){
            return response()
            ->json([
                'data' =>null ,
                'success' => false,
                'errors' => [
                    [
                        'status' => Response::HTTP_CONFLICT,
                        'title' => 'Type doesnt exist.',
                        'message' => "This type doesnt exist in the database."
                    ],
                ]
            ], Response::HTTP_CONFLICT); 
        }
        if ($request->name) {
            $type = Type::where('name', Str::ucfirst($request->name))->first();
            if($type && $request->used_for){
                $used_for_is_same= strcmp($type->used_for,$request->used_for)==0?true:false;            
            if ($used_for_is_same) {
                return response()
                ->json([
                    'data' =>$type ,
                    'success' => false,
                    'errors' => [
                        [
                            'status' => Response::HTTP_CONFLICT,
                            'title' => 'Type already exist.',
                            'message' => "This type already exist in the database."
                        ],
                    ]
                ], Response::HTTP_CONFLICT); 
            }
            $input['name'] = Str::ucfirst($input['name']);
        }}
        if ($request->category_id) {
            $category = Category::where('id', $request->category_id)->first();
            if (!$category) {
                return response()
                ->json([
                    'data' =>$category ,
                    'success' => false,
                    'errors' => [
                        [
                            'status' => Response::HTTP_CONFLICT,
                            'title' => "Category doesn't exist.",
                            'message' => "A category with this ID doesn't exist in the database.Please select the right category."
                        ],
                    ]
                ], Response::HTTP_CONFLICT); 
            }
        }
        if ($type_to_be_updated->fill($input)->save()) {
            $type_to_be_updated->type;
            return response()
                    ->json([
                        'data' =>$type_to_be_updated,
                        'success' => true,
                        'errors' => [
                            [
                                'status' => Response::HTTP_CREATED,
                                'title' => 'Type updated.',
                                'message' => "The type is updated sucessfully."
                            ],
                        ]
                    ], Response::HTTP_CREATED);
                } else {
                    return response()
                        ->json([
                            'data' =>$type ,
                            'success' => false,
                            'errors' => [
                                [
                                    'status' => Response::HTTP_INTERNAL_SERVER_ERROR,
                                    'title' => 'Internal error',
                                    'message' => "This type couldnt be saved."
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
     *      path="/type/{id}",
     *      operationId="deleteType",
     *      tags={"Type"},
     *      summary="Delete existing type",
     *      description="Deletes a record and returns no content",
     *      @OA\Parameter(
     *          name="id",
     *          description="Type id",
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
        $type = Type::find($id);
        if (!$type) {
            return response()
                ->json("Resource Not Found", Response::HTTP_NOT_FOUND);
        }
        $type->status='deleted';
        $type->save();
        return response(null, Response::HTTP_NO_CONTENT);
    }
}
