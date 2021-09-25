<?php

namespace App\Http\Controllers;

use Exception;
use App\Models\ItemSwapType;
use App\Models\Address;
use App\Models\Type;
use App\Models\Item;
use App\Models\Media;
use Illuminate\Http\Request;
use App\Models\UserTransaction;
use Gate;
use App\Http\Resources\ItemResource;
use App\Http\Resources\AddressResource;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class ItemController extends Controller
{


    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        try {
            $items = Item::where('status', '!=', 'deleted')
                ->orWhereNull('status')->get()
                ->each(function ($item, $key) {
                    $item->itemSwapType->each(function ($type, $key) {
                        $type->type;
                    });
                    $item->bartering_location;
                    $item->type;
                    $item->user;
                    $item->media;
                    $item->request;
                    $item->request->each(function($req, $key){
                        $req->requester_item;
                    });
                });

            return response()
                ->json(
                    HelperClass::responeObject($items, true, Response::HTTP_OK, 'Successfully fetched.', "Item are fetched sucessfully.", ""),
                    Response::HTTP_OK
                );
        } catch (ModelNotFoundException $ex) {
            return response()
                ->json(
                    HelperClass::responeObject(null, false, RESPONSE::HTTP_UNPROCESSABLE_ENTITY, 'The model doesnt exist.', "", $ex->getMessage()),
                    Response::HTTP_INTERNAL_SERVER_ERROR
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
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function itemCountByDate($attribute, $start, $end)
    {
        //whereBetween('created_at', [$dateS->format('Y-m-d').
        //" 00:00:00", $dateE->format('Y-m-d')." 23:59:59"])->get();
        try {
            $items = Item::orderBy($attribute)->whereBetween($attribute, [$start, $end])->get()->groupBy(function ($item) {
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
    public function countByStatus()
    {
        $itemGrouped = Item::all()->groupBy(function ($item) {
            return $item->status;
        });
        foreach ($itemGrouped as $key => $item) {
            $itemGrouped[$key] = $item->count();
        }
        return response()
            ->json($itemGrouped, Response::HTTP_OK);
    }
    public function itemsByLocation(Request $request)
    {
        try {
            $input = $request->all();
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
                ->where('type', 'item')
                ->get();
            /* $addresses = Address:: where('latitude', $input['latitude'])
                ->where('longitude', $input['longitude'])
                ->where('type', 'item')->get(); */
            if ($addresses->count() <= 0) {
                return response()
                    ->json(
                        HelperClass::responeObject($addresses, true, Response::HTTP_OK, 'Items doesnt exist', "Item doesn't exist within your location.", ""),
                        Response::HTTP_OK
                    );
            }
            $addresses->each(function ($address, $key) {
                $address->item->user;
                $address->item->bartering_location;
                $address->item->media;
                $address->item->itemSwapType->each(function ($type, $key) {
                    $type->type;
                });
            });
            return response()
                ->json(
                    HelperClass::responeObject($addresses, true, Response::HTTP_OK, 'List of address with their item.', "These are the list of items near the address you choose.", ""),
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
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //if organization or user do smt on status. Check if 
        //the memebrship of this user enables the user to enter a new product
        try {
            //check memebrship and decide if they can upload item.
            $user = $request->user();               
            $usertransaction = UserTransaction::where('user_id', $user->id)->first();  
            if((int)$usertransaction->left_limit_of_post<=0){
                return
                response()
                ->json(
                    HelperClass::responeObject($usertransaction, false, Response::HTTP_OK, "Doesnt have transaction left.","","This user doesnt have transaction left."),
                    Response::HTTP_OK
                );
            }       
            $validatedData = Validator::make($request->all(), [
                'name' => ['required', 'max:50'],
                'description' => ['required', 'max:255'],
                'type_id' => ['required', 'numeric'],
                'address.latitude' => ['required', 'numeric'],
                'address.longitude' => ['required', 'numeric'],
                'address.country' => ['required', 'max:50'],
                'address.city' => ['required', 'max:50'],
                'address.type' => ['required', 'max:10', Rule::in(['item'])]

            ]);
            if ($validatedData->fails()) {
                return response()
                    ->json(
                        HelperClass::responeObject(null, false, Response::HTTP_BAD_REQUEST, "Validation failed check JSON request", "", $validatedData->errors()),
                        Response::HTTP_BAD_REQUEST
                    );
            }
            $input = $request->all();
           
            $address = $request->address;
            $address = new Address($address);
            $address->type = 'item';
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
                $item = new Item($input);
                $item->user_id = $user->id;
                if ($item->save()) {
                    $itemSwapType = $request->swap_type;
                    foreach ($itemSwapType as $t) {
                        $type = Type::where('id', $t)->where('used_for','item')->where('status', '!=', 'deleted')->first();
                        if (!$type) {
                            return response()
                                ->json(
                                    HelperClass::responeObject(null, false, Response::HTTP_BAD_REQUEST, "Can't do a swap due to selected type error.", "", "A type doesnt exist by the id $t that is used for item."),
                                    Response::HTTP_BAD_REQUEST
                                );
                        }
                        $swap = new ItemSwapType();
                        $swap->type_id = $t;
                        $swap->item_id = $item->id;
                        if (!$swap->save()) {
                            return  response()
                                ->json(
                                    HelperClass::responeObject(null, false, Response::HTTP_INTERNAL_SERVER_ERROR, "Inernal error", "", "The swap type $swap resource couldn't be saved due to internal error"),
                                    Response::HTTP_INTERNAL_SERVER_ERROR
                                );
                        }
                    }
                    if($usertransaction){
                        $usertransaction->left_limit_of_post = (int)$usertransaction->left_limit_of_post - 1;
                        if (!$usertransaction->save()) {
                            return response()
                            ->json(
                                HelperClass::responeObject(null, false, Response::HTTP_INTERNAL_SERVER_ERROR, "Internal error", "", "The number of user transaction couldnt be updated."),
                                Response::HTTP_INTERNAL_SERVER_ERROR
                            );
                        }
                    }
                    /* $itemMedia = $request->media;
                    foreach ($itemMedia as $m) {
                        //check if the sent type id is in there 
                        $media = new Media();
                        $media->type = 'item';
                        $media->url = $m;
                        $media->item_id = $item->id;
                        if (!$media->save()) {
                            return  response()
                                ->json(
                                    HelperClass::responeObject(null, false, Response::HTTP_INTERNAL_SERVER_ERROR, "Internal error", "", "The media $media resource couldn't be saved due to internal error"),
                                    Response::HTTP_INTERNAL_SERVER_ERROR
                                );
                        }
                    }                     
                    $item->media;*/
                    $item->bartering_location;
                    $item->type;
                    $item->itemSwapType->each(function ($type, $key) {
                        $type->type;
                    });
                    $item->user;
                    $item->request;
                    $item->request->each(function($req, $key){
                        $req->requester_item;
                    });
                    return
                        response()
                        ->json(
                            HelperClass::responeObject($item, true, Response::HTTP_CREATED, "Item created.", "The item has been created.", ""),
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
            $items = Item::all();
            if ($items->count() <= 0) {
                return response()
                    ->json(
                        HelperClass::responeObject($items, true, Response::HTTP_OK, 'List of items.', "There is no item.", ""),
                        Response::HTTP_OK
                    );
            }
            $col = DB::getSchemaBuilder()->getColumnListing('items'); 
            $user = $request->user();
            if($user){
                $request->request->add(['user_id' => $user->id]);
            }
            $input = $request->all();
            
            $requestKeys = collect($request->all())->keys();
            foreach ($requestKeys as $key) {
                if (in_array($key, $col)) {
                    $items = $items->where($key, $input[$key])->values();
                }
            }
            $items=$items->where('status','!=', 'deleted')->values();
            $items->each(function ($item, $key) {
                $item->media;
                $item->bartering_location;
                $item->type;
                $item->user;
                $item->itemSwapType->each(function ($type, $key) {
                    $type->type;
                });
                $item->request;
                $item->request->each(function($req, $key){
                    $req->requester_item;
                });
                
            });
            return response()
                ->json(
                    HelperClass::responeObject($items, true, Response::HTTP_OK, 'List of items.', "These are the list of items based on your search.", ""),
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
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Item  $item
     * @return \Illuminate\Http\Response
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
            $item = Item::where('id', $id)->first();
            if ($request->address) {
                $address_to_be_updated = $request->address;
                $address = Address::where('id', $item->bartering_location_id)->first();
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
             
            if ($item->fill($input)->save()) {
                if ($request->swap_type) {
                    $toBeRemoved = $request->swap_type["removed"];
                    $newToBeSaved = $request->swap_type["added"];
                    $oldSwap = ItemSwapType::where('item_id', $item->id)
                        ->where('type_id', $toBeRemoved)->get();
                    ItemSwapType::destroy($oldSwap);
                    foreach ($newToBeSaved as $t) {
                        $type = Type::where('id', $t)->where('used_for','item')->where('status', '!=', 'deleted')->first();
                            if (!$type) {
                                return response()
                                    ->json(
                                        HelperClass::responeObject(null, false, Response::HTTP_BAD_REQUEST, "Can't do a swap due to selected type error.", "", "A type doesnt exist by the id $t that is used for item."),
                                        Response::HTTP_BAD_REQUEST
                                    );
                            }
                        $swap = new ItemSwapType();
                        $swap->type_id = $t;
                        $swap->item_id = $item->id;
                        if (!$swap->save()) {
                            return  response()
                                ->json(
                                    HelperClass::responeObject(null, false, Response::HTTP_INTERNAL_SERVER_ERROR, "Inernal error", "", "The swap type $swap resource couldn't be saved due to internal error"),
                                    Response::HTTP_INTERNAL_SERVER_ERROR
                                );
                        }
                    }
                }
                $item->media;
                $item->bartering_location;
                $item->type;
                $item->user;
                $item->request;
                $item->itemSwapType->each(function ($type, $key) {
                    $type->type;
                });
                $item->request->each(function($req, $key){
                    $req->requester_item;
                });
                return response()
                    ->json(
                        HelperClass::responeObject($item, true, Response::HTTP_OK, 'Item detail.', "Item updated successfully.", ""),
                        Response::HTTP_OK
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
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Item  $item
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        try {
            $item = Item::find($id);
            if (!$item) {
                response()
                    ->json(
                        HelperClass::responeObject(null, false, Response::HTTP_NOT_FOUND, "Resource Not Found", '', "Item by this id doesnt exist."),
                        Response::HTTP_NOT_FOUND
                    );
            }
            $item->status = 'deleted';
            $item->save();
            return response()
                ->json(
                    HelperClass::responeObject(null, true, Response::HTTP_OK, 'Successfully deleted.', "Item is deleted sucessfully.", ""),
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
}
