<?php

namespace App\Http\Controllers;

use App\Models\Type;
use Illuminate\Http\Request;
use Gate;
use App\Http\Resources\TypeResource;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

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
        $type = Type::where('name', $request->name)->first();
        if (!$type) {
            $type = Type::create($request->all());
            //CHECK IF THE SESSION COOKIE OR THE TOKEN IS RIGH
            //IF IT ISNT RETURN HTTP_FORBIDDEN OR HTTP_BAD_REQUEST

            if ($type->save()) {
                $type->category;
                return (new TypeResource($type))
                    ->response()
                    ->setStatusCode(Response::HTTP_CREATED);
            } else {
                return response()
                    ->json("This resource couldn't be saved due to internal error", Response::HTTP_INTERNAL_SERVER_ERROR);
            }
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
        $input = $request->all();
        $type_to_be_updated = Type::where('id', $id)->first();
        if (in_array('name', $input)) {
            $type = Type::where('name', Str::ucfirst($request->name))->first();
            if ($type) {
                return response()->json("A resource exist by this name.", Response::HTTP_CONFLICT);
            }
            $input['name'] = Str::ucfirst($input['name']);
        }
        if ($type_to_be_updated->fill($input)->save()) {
            $type_to_be_updated->type;
            return (new TypeResource($type_to_be_updated))
                ->response()
                ->setStatusCode(Response::HTTP_CREATED);
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
