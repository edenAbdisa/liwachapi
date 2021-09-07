<?php

namespace App\Http\Controllers;

use App\Models\Address;
use Illuminate\Http\Request;
use Gate;
use App\Http\Resources\AddressResource;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class AddressController extends Controller
{
    /**
     * @OA\Get(
     *      path="/addresses",
     *      operationId="getAddressesList",
     *      tags={"Address"},
     *      summary="Get list of Address",
     *      description="Returns list of Address",
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
        //abort_if(Gate::denies('address_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');
        //User::with(['roles'])->get() 
        $addresses=Address::all();
        $addresses->each(function ($address, $key) { 

            $address->item->user;
            $address->item->bartering_location;
            $address->item->media;
            $address->item->itemSwapType->each(function ($type, $key) {
                $type->type;
            });
        });

        return ($addresses)
            ->response()
            ->setStatusCode(Response::HTTP_OK);
    }


    /**
     * @OA\Post(
     *      path="/address",
     *      operationId="storeAddress",
     *      tags={"Address"},
     *      summary="Store new Address",
     *      description="Returns address data",
     *      @OA\RequestBody(
     *          required=true,
     *          @OA\JsonContent(ref="#/components/schemas/Address")
     *      ),
     *      @OA\Response(
     *          response=201,
     *          description="Successful operation",
     *          @OA\JsonContent(ref="#/components/schemas/Address")
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
        $address = Address::create($request->all());
        //CHECK IF THE SESSION COOKIE OR THE TOKEN IS RIGH
        //IF IT ISNT RETURN HTTP_FORBIDDEN OR HTTP_BAD_REQUEST
        //dd("line 81"); 
        if ($address->save()) {
            return (new AddressResource($address))
                ->response()
                ->setStatusCode(Response::HTTP_CREATED);
        } else {
            return (new AddressResource($address))
                ->response()
                ->setStatusCode(Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * @OA\Get(
     *      path="/addresses/{id}",
     *      operationId="getAddressById",
     *      tags={"Addresses"},
     *      summary="Get address information",
     *      description="Returns address data",
     *      @OA\Parameter(
     *          name="id",
     *          description="Address id",
     *          required=true,
     *          in="path",
     *          @OA\Schema(
     *              type="integer"
     *          )
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *          @OA\JsonContent(ref="#/components/schemas/Address")
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
        $addresses = Address::all();
        $col = DB::getSchemaBuilder()->getColumnListing('addresses');
        $requestKeys = collect($request->all())->keys();
        foreach ($requestKeys as $key) {
            if (empty($addresses)) {
                return response()->json($addresses, 200);
            }
            if (in_array($key, $col)) {
                $addresses = $addresses->where($key, $input[$key])->values();
            }
        }
        return response()->json($addresses, 200);
    }

    /**
     * @OA\Put(
     *      path="/addresses/{id}",
     *      operationId="updateAddress",
     *      tags={"Addresss"},
     *      summary="Update existing address",
     *      description="Returns updated address data",
     *      @OA\Parameter(
     *          name="id",
     *          description="Address id",
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
     *          @OA\JsonContent(ref="#/components/schemas/Address")
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
        $address = Address::where('id', $id)->first();
        if ($address->fill($input)->save()) {
            return (new AddressResource($address))
                ->response()
                ->setStatusCode(Response::HTTP_CREATED);
        }
    }

    /**
     * @OA\Delete(
     *      path="/addresses/{id}",
     *      operationId="deleteAddress",
     *      tags={"Addressess"},
     *      summary="Delete existing address",
     *      description="Deletes a record and returns no content",
     *      @OA\Parameter(
     *          name="id",
     *          description="Address id",
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
        $address = Address::findOrFail($id);
        $address->delete();
        return response(null, Response::HTTP_NO_CONTENT);
    }
    //cant be deletd alone since it violates foreign key no need
    //to delete this data by an end point
}
