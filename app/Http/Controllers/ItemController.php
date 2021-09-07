<?php

namespace App\Http\Controllers;
use Exception;
use App\Models\ItemSwapType;
use App\Models\Address;
use App\Models\Type;
use App\Models\Item;
use App\Models\Media;
use Illuminate\Http\Request;
use Gate;
use App\Http\Resources\ItemResource;
use App\Http\Resources\AddressResource;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\ModelNotFoundException;
class ItemController extends Controller
{


    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $items = Item::where('status','!=','deleted')
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
        });
        return (new ItemResource($items))
            ->response()
            ->setStatusCode(Response::HTTP_OK);
    }
        /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function itemCountByDate($attribute,$start,$end)
    {
        //whereBetween('created_at', [$dateS->format('Y-m-d').
        //" 00:00:00", $dateE->format('Y-m-d')." 23:59:59"])->get();
        try{
        $items = Item::orderBy($attribute)->whereBetween($attribute, [$start,$end])->get()->groupBy(function($item) {
             return $item->created_at->format('Y-m-d');
       });
       }catch(Exception $e){
        return response()
        ->json("There is no such attribute.",Response::HTTP_OK);
       }
       foreach($items as $key => $item){
        $day = $key;
        $totalCount = $item->count();
        $items[$key]=$totalCount;
       }
        return response()
            ->json($items,Response::HTTP_OK);
    }
    public function countByStatus()
    {
        //abort_if(Gate::denies('request_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');
        //User::with(['roles'])->get() 
        //$wordCount = Wordlist::where('id', '<=', $correctedComparisons)->count();
        $itemGrouped = Item::all()->groupBy(function($item) {
            return $item->status;
        });
        foreach($itemGrouped as $key => $item){
            $itemGrouped[$key]=$item->count();
           }          
        return response()
        ->json($itemGrouped,Response::HTTP_OK);
    }
    public function itemsByLocation(Request $request)
    {
        try {
            $input = $request->all();
        $addresses = Address::where('city', $input['city'])->where('type', 'item')->get();
        $addresses->each(function ($address, $key) {            
            $address->item->itemSwapType;
            $address->item->user;
            $address->item->bartering_location;
            $address->item->media;
            $address->item->itemSwapType->each(function ($type, $key) {
                $type->type;
            });
        });
        return response()->json($addresses, 200);
        } catch (ModelNotFoundException $ex) { // User not found
            abort(422, 'Invalid email: administrator not found');
        } catch (Exception $ex) { // Anything that went wrong
            abort(500, 'Could not create office or assign it to administrator');
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
        //$file = $request->file('picture');
       // if ($file) {
           // $filename = time() . '_' . $file->getClientOriginalName();
            //if (!HelperClass::uploadFile($file, $filename, 'files/items')) {
                // return response()
                // ->json("The picture couldn't be uploaded", Response::HTTP_INTERNAL_SERVER_ERROR);
            //}            
            $input = $request->all();
            $address = $request->address;         
            // $address = json_decode($address, true,512,JSON_BIGINT_AS_STRING);
            $address = new Address($address);   
            $address->type='item';
            if ($address->save()) {
                //$type = Type::where('id', $request->type_name)->first();
                //if ($type) {
                    $input['status'] = 'open';
                    $input['number_of_flag'] = 0;
                    $input['number_of_request'] = 0;
                    $input['bartering_location_id'] = $address->id;
                   // $input['type_id'] = $type->id;
                   // $input['picture'] = $filename;                    
                    $item = Item::create($input);
                    if ($item->save()) {
                        $itemSwapType = $request->swap_type;
                        foreach ($itemSwapType as $t) {
                            //check if the sent type id is in there 
                            $swap = new ItemSwapType();
                            $swap->type_id = $t;
                            $swap->item_id = $item->id;
                            if (!$swap->save()) {
                                return response()
                                    ->json("The swap type $swap resource couldn't be saved due to internal error", Response::HTTP_INTERNAL_SERVER_ERROR);
                            }
                        }
                        $itemMedia = $request->media;
                        foreach ($itemMedia as $m) {
                            //check if the sent type id is in there 
                            $media = new Media();
                            $media->type = 'item';
                            $media->url = $m;
                            $media->item_id = $item->id;
                            if (!$media->save()) {
                                return response()
                                    ->json("The media $media resource couldn't be saved due to internal error", Response::HTTP_INTERNAL_SERVER_ERROR);
                            }
                        }
                        $item->bartering_location;
                        $item->type;
                        $item->itemSwapType->each(function ($type, $key) {
                            $type->type;
                        });
                        $item->user; 
                        $item->media;
                        $item->request;
                        return (new ItemResource($item))
                            ->response()
                            ->setStatusCode(Response::HTTP_CREATED);
                    } else {
                        return response()
                            ->json("This resource couldn't be saved due to internal error", Response::HTTP_INTERNAL_SERVER_ERROR);
                    }
                /* } else {
                    return response()
                        ->json("No such type",Response::HTTP_INTERNAL_SERVER_ERROR);
                } */
            } else {
                return response()
                    ->json("The address couldn't be saved due to internal error", Response::HTTP_INTERNAL_SERVER_ERROR);
            }
        /* } else {
            return response()
                ->json("The image files isnt uploaded", Response::HTTP_INTERNAL_SERVER_ERROR);
        } */
        //$item = Item::create($request->all());
        //CHECK IF THE SESSION COOKIE OR THE TOKEN IS RIGH
        //IF IT ISNT RETURN HTTP_FORBIDDEN OR HTTP_BAD_REQUEST
        //dd("line 81");         
    }

    public function search(Request $request)
    {
        $input = $request->all();
        $items = Item::all();
        $col = DB::getSchemaBuilder()->getColumnListing('items');
        $requestKeys = collect($request->all())->keys();
        foreach ($requestKeys as $key) {
            if (empty($items)) {
                return response()->json($items, 200);
            }
            if (in_array($key, $col)) {
                $items = $items->where($key, $input[$key])->values();
            }
        }
        $items->each(function ($item, $key) {
            $item->media;
            $item->bartering_location;
            $item->type;
            $item->user; 
            $item->itemSwapType->each(function ($type, $key) {
                $type->type;
            });
            $item->request;
        });
        return response()->json($items, 200);
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
        $input = $request->all();
        $item = Item::where('id', $id)->first();
       if ($request->address) {
            $address_to_be_updated=$request->address;
            $address = Address::where('id', $item->bartering_location_id)->first(); 
            $address->city=$address_to_be_updated['city'];  
            $address->country=$address_to_be_updated['country']; 
            $address->latitude=(float)$address_to_be_updated['latitude'];  
            $address->longitude=(float)$address_to_be_updated['longitude'];        
            $address->save(); 
       }
        if ($request->media) {
            $itemMedia = $request->media;
            foreach ($itemMedia as $m) {
                $mediaOld = Media::where('id', $m['id'])->first();
                $mediaOld->url=$m['url'];
                $mediaOld->save();
            }             
        } 
        //->select('users.*', 'contacts.phone', 'orders.price')
        if ($request->swap_type) {            
           $toBeRemoved=$request->swap_type["removed"];
           $newToBeSaved=$request->swap_type["added"];
           $oldSwap = ItemSwapType::where('item_id', $item->id)
                                    ->where('type_id', $toBeRemoved)->get();
           ItemSwapType::destroy($oldSwap);
           foreach ($newToBeSaved as $t) {
                //check if the sent type id is in there 
                $swap = new ItemSwapType();
                $swap->type_id = $t;
                $swap->item_id = $item->id;
                if (!$swap->save()) {
                    return response()
                        ->json("The swap type $swap resource couldn't be saved due to internal error", Response::HTTP_INTERNAL_SERVER_ERROR);
                }
            }
        }
        //     $input['type_id'] = $type->id;
        // }
        //swap update isnt done
        if ($item->fill($input)->save()) {
            $item->media;
            $item->bartering_location;
            $item->type;
            $item->user;
            $item->request;
            $item->itemSwapType->each(function ($type, $key) {
                $type->type;
            });
            return (new ItemResource($item))
                ->response()
                ->setStatusCode(Response::HTTP_CREATED);
        }
        //Item::where('id', $id)->update(['delayed' => 1]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Item  $item
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $item = Item::find($id);
        if (!$item) {
            return response()
                ->json("Resource Not Found", Response::HTTP_NOT_FOUND);
        }
        $item->status='deleted';
        $item->save(); 
        return response(null, Response::HTTP_NO_CONTENT);
    }
}
