<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;
use Gate;
use App\Http\Resources\CategoryResource;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class CategoryController extends Controller
{
    /**
     * @OA\Get(
     *      path="/categories",
     *      operationId="getAddressesList",
     *      tags={"Category"},
     *      summary="Get list of Category",
     *      description="Returns list of Category",
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *          @OA\JsonContent(ref="#/components/schemas/AddressResource")
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
            $categories = Category::where('status', '!=', 'deleted')->orWhereNull('status')->get()
                ->each(function ($item, $key) {
                    $item->type;
                });
            return response()
                ->json(
                    HelperClass::responeObject($categories, true, Response::HTTP_OK, 'Successfully fetched.', "Category is fetched sucessfully.", ""),
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

    /**
     * @OA\Post(
     *      path="/category",
     *      operationId="storeAddress",
     *      tags={"Category"},
     *      summary="Store new Category",
     *      description="Returns category data",
     *      @OA\RequestBody(
     *          required=true,
     *          @OA\JsonContent(ref="#/components/schemas/Category")
     *      ),
     *      @OA\Response(
     *          response=201,
     *          description="Successful operation",
     *          @OA\JsonContent(ref="#/components/schemas/Category")
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
                'name' => ['required', 'max:30'],
                'used_for' => ['required', 'max:50']
            ]);
            if ($validatedData->fails()) {
                return response()
                    ->json([
                        'data' => null,
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
            $category = Category::where('name', Str::ucfirst($request->name))->first();
            if (!$category) {
                $input = $request->all();
                $input['name'] = Str::ucfirst($input['name']);
                $category = new Category($input);
                $category->status = "active";
                if ($category->save()) {
                    return response()
                        ->json([
                            'data' => $category,
                            'success' => true,
                            'errors' => [
                                [
                                    'status' => Response::HTTP_CREATED,
                                    'title' => 'Category created.',
                                    'message' => "The category is created sucessfully."
                                ],
                            ]
                        ], Response::HTTP_CREATED);
                } else {
                    return response()
                        ->json([
                            'data' => $category,
                            'success' => false,
                            'errors' => [
                                [
                                    'status' => Response::HTTP_INTERNAL_SERVER_ERROR,
                                    'title' => 'Internal error',
                                    'message' => "This category couldnt be saved."
                                ],
                            ]
                        ], Response::HTTP_INTERNAL_SERVER_ERROR);
                }
            } else {
                return response()
                    ->json([
                        'data' => $category,
                        'success' => false,
                        'errors' => [
                            [
                                'status' => Response::HTTP_CONFLICT,
                                'title' => 'Category already exist.',
                                'message' => "This category already exist in the database."
                            ],
                        ]
                    ], Response::HTTP_CONFLICT);
            }
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
     * @OA\Get(
     *      path="/categories/{id}",
     *      operationId="getAddressById",
     *      tags={"Categories"},
     *      summary="Get category information",
     *      description="Returns category data",
     *      @OA\Parameter(
     *          name="id",
     *          description="Category id",
     *          required=true,
     *          in="path",
     *          @OA\Schema(
     *              type="integer"
     *          )
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *          @OA\JsonContent(ref="#/components/schemas/Category")
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
                'name' => ['max:30'],
                'used_for' => ['max:70']
            ]);
            if ($validatedData->fails()) {
                return response()
                    ->json(
                        HelperClass::responeObject(null, false, Response::HTTP_BAD_REQUEST, "Validation failed check JSON request", "", $validatedData->errors()),
                        Response::HTTP_BAD_REQUEST
                    );
            }
            $input = $request->all();
            $categories = Category::all();
            $col = DB::getSchemaBuilder()->getColumnListing('categories');
            $requestKeys = collect($request->all())->keys();
            foreach ($requestKeys as $key) {
                if (empty($categories)) {
                    return response()->json($categories, 200);
                }
                if (in_array($key, $col)) {
                    if ($key == 'name') {
                        $input[$key] = Str::ucfirst($input[$key]);
                    }
                    $categories = $categories->where($key, $input[$key])->values();
                }
            }
            $categories->each(function ($item, $key) {
                $item->type;
            });
            return response()->json($categories, Response::HTTP_OK);
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
     *      path="/categories/{id}",
     *      operationId="updateAddress",
     *      tags={"Addresss"},
     *      summary="Update existing category",
     *      description="Returns updated category data",
     *      @OA\Parameter(
     *          name="id",
     *          description="Category id",
     *          required=true,
     *          in="path",
     *          @OA\Schema(
     *              type="integer"
     *          )
     *      ),
     *      @OA\RequestBody(
     *          required=true,
     *          @OA\JsonContent(ref="#/components/schemas/UpdateAddressRequest")
     *      ),
     *      @OA\Response(
     *          response=202,
     *          description="Successful operation",
     *          @OA\JsonContent(ref="#/components/schemas/Category")
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
        try {
            $validatedData = Validator::make($request->all(), [
                'name' => ['max:30'],
                'used_for' => ['max:70']
            ]);
            if ($validatedData->fails()) {
                return response()
                    ->json(
                        HelperClass::responeObject(null, false, Response::HTTP_BAD_REQUEST, "Validation failed check JSON request", "", $validatedData->errors()),
                        Response::HTTP_BAD_REQUEST
                    );
            }
            $category_to_be_updated = Category::where('id', $id)->first();
            if (!$category_to_be_updated) {
                return response()
                    ->json(
                        HelperClass::responeObject(null, false, Response::HTTP_NOT_FOUND, 'Category doesnt exist.', "This category doesnt exist in the database.", ""),
                        Response::HTTP_OK
                    );
            }
            if ($request->name) {
                $category = Category::where('name', Str::ucfirst($request->name))->first();
                if ($category && $request->used_for) {
                    $used_for_is_same = strcmp($category->used_for, $request->used_for) == 0 ? true : false;
                    if ($used_for_is_same) {
                        $category->type;
                        return response()
                            ->json(
                                HelperClass::responeObject($category, false, Response::HTTP_CONFLICT, 'Category already exist.', "This category already exist in the database.", ""),
                                Response::HTTP_CONFLICT
                            );
                    }
                }


                $input['name'] = Str::ucfirst($input['name']);
            }
            if ($category_to_be_updated->fill($input)->save()) {
                $category_to_be_updated->type;
                return response()
                    ->json(
                        HelperClass::responeObject($category_to_be_updated, true, Response::HTTP_CREATED, 'Category updated.', "The category is updated sucessfully.", ""),
                        Response::HTTP_CREATED
                    );
            } else {
                return response()
                    ->json(
                        HelperClass::responeObject($category_to_be_updated, false, Response::HTTP_INTERNAL_SERVER_ERROR, 'Internal error', "", "This category couldnt be updated."),
                        Response::HTTP_INTERNAL_SERVER_ERROR
                    );
            }
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

    /**
     * @OA\Delete(
     *      path="/categories/{id}",
     *      operationId="deleteAddress",
     *      tags={"Addressess"},
     *      summary="Delete existing category",
     *      description="Deletes a record and returns no content",
     *      @OA\Parameter(
     *          name="id",
     *          description="Category id",
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
        try {
            $category = Category::find($id);
            if (!$category) {
                response()
                    ->json(
                        HelperClass::responeObject(null, false, Response::HTTP_NOT_FOUND, "Resource Not Found", '', "Category by this id doesnt exist."),
                        Response::HTTP_NOT_FOUND
                    );
            }
            $category->status = 'deleted';
            $category->save();
            return response()
                ->json(
                    HelperClass::responeObject(null, true, Response::HTTP_NO_CONTENT, 'Successfully deleted.', "Category is deleted sucessfully.", ""),
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
