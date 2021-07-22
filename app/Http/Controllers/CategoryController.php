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
        //abort_if(Gate::denies('category_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');
        //User::with(['roles'])->get() 
        $categories = Category::where('status', '=', 'active')->get()
        ->each(function ($item, $key) {
            $item->type;
       });
        return (new CategoryResource($categories))
            ->response()
            ->setStatusCode(Response::HTTP_OK);
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
        //Str::upper(str)
        $category = Category::where('name', $request->name)->first();
        if (!$category) {
            $input = $request->all();
            $input['name'] = Str::ucfirst($input['name']);
            $category = Category::create($input);
            //CHECK IF THE SESSION COOKIE OR THE TOKEN IS RIGH
            //IF IT ISNT RETURN HTTP_FORBIDDEN OR HTTP_BAD_REQUEST            
            if ($category->save()) {
                return (new CategoryResource($category))
                    ->response()
                    ->setStatusCode(Response::HTTP_CREATED);
            } else {
                return response()
                    ->json("This resource couldn't be saved due to internal error", Response::HTTP_INTERNAL_SERVER_ERROR);
            }
        } else {
            return response()->json("This resource already exist.", Response::HTTP_CONFLICT);
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
                $categories = $categories->where($key, $input[$key]);
            }
        }
        $categories->each(function ($item, $key) {
            $item->type;
        });
        return response()->json($categories, Response::HTTP_OK);
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
        $category_to_be_updated = Category::where('id', $id)->first();
        if (in_array('name', $input)) {
            $category = Category::where('name', Str::ucfirst($request->name))->first();
            if ($category) {
                $category->type;
                return response()->json("A resource exist by this name.", Response::HTTP_CONFLICT);
            }
            $input['name'] = Str::ucfirst($input['name']);
        }
        if ($category_to_be_updated->fill($input)->save()) {
            $category_to_be_updated->type;
            return (new CategoryResource($category_to_be_updated))
                ->response()
                ->setStatusCode(Response::HTTP_CREATED);
        } else {
            return response()
                ->json("This resource couldn't be saved due to internal error", Response::HTTP_INTERNAL_SERVER_ERROR);
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
        $category = Category::find($id);
        if (!$category) {
            return response()
                ->json("Resource Not Found", Response::HTTP_NOT_FOUND);
        }        
        $category->status='deleted';
        $category->save();
        return response(null, Response::HTTP_NO_CONTENT);
    }
}
