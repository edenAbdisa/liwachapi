<?php

namespace App\Http\Controllers;
use App\Models\ItemSwapType;
use App\Models\Address;
use App\Models\Type;
use App\Models\Item;
use Illuminate\Http\Request;
use Gate;
use App\Http\Resources\ItemResource;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class ItemController extends Controller
{
    

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $items= Item::all()->each(function($item, $key) {
            $item->bartering_location ;
            $item->type ;  
       });
        return (new ItemResource($items))
            ->response()
            ->setStatusCode(Response::HTTP_OK);
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
        $file=$request->file('picture');
        if($file) {            
            $filename = time().'_'.$file->getClientOriginalName();
            if(!HelperClass::uploadFile($file,$filename, 'files/items')){
                return response()
                            ->json("The picture couldn't be uploaded", Response::HTTP_INTERNAL_SERVER_ERROR);
            }                       
            $address = $request->address;
            $address=json_decode( $address, true);
            $address['type'] = 'item'; 
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
                    $item=Item::create($input);
                    if($item->save()){
			            $itemSwapType=json_decode($input['swap_type']); 
                        foreach ($itemSwapType as $t) {
                            //check if the sent type id is in there  
                            $id=$item->id;                                                
                            if(!ItemSwapType::create(["type_id" => $t],
                            ["item_id" => $id])->save()){
                                return response()
                                ->json("The swap type $swap resource couldn't be saved due to internal error", Response::HTTP_INTERNAL_SERVER_ERROR);                     
                             }
                        }
                        $item->bartering_location ;
                        $item->type ;
                        return (new ItemResource($item))
                        ->response()
                        ->setStatusCode(Response::HTTP_CREATED);
                    }else{ 
                        return response()
                            ->json("This resource couldn't be saved due to internal error", Response::HTTP_INTERNAL_SERVER_ERROR);
                    }            
                }else{
                    return ("No such type")
                    ->response()
                    ->setStatusCode(Response::HTTP_INTERNAL_SERVER_ERROR);      
                }           
            }else{
                return response()
                        ->json("The address couldn't be saved due to internal error", Response::HTTP_INTERNAL_SERVER_ERROR);
            }
        } else{
            return response()
                        ->json("The image files isnt uploaded", Response::HTTP_INTERNAL_SERVER_ERROR);
            
        }       
            //$item = Item::create($request->all());
            //CHECK IF THE SESSION COOKIE OR THE TOKEN IS RIGH
            //IF IT ISNT RETURN HTTP_FORBIDDEN OR HTTP_BAD_REQUEST
            //dd("line 81");         
    }
    
    public function search(Request $request)
    { 
        $input = $request->all();
        $items = Item::all();  
        $col=DB::getSchemaBuilder()->getColumnListing('items'); 
        $requestKeys = collect($request->all())->keys();       
        foreach ($requestKeys as $key) { 
            if(empty($items)){
                return response()->json($items, 200);
            }
            if(in_array($key,$col)){ 
                $items = $items->where($key,$input[$key]);
            }            
        } 
        $items->each(function($item, $key) {
            $item->bartering_location ;
            $item->type ;
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
        $item= Item::where('id',$id)->first();
        if(in_array('address',$input)){
            $address= Address::where('id',$item->bartering_location_id)->first();
            $address->fill($address_to_be_updated)->save();
        }
        if(in_array('type_name',$input)){            
            $type= Type::where('name',$request->type_name)->first();
            $input['type_id']=$type->id;
        }
        if($item->fill($input)->save()){
            $item->bartering_location ;
            $item->type ;
            return ($item)
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
    public function destroy( $id)
    {
        $item = Item::find($id);
        if(!$item){
            return response()
                   ->json("Resource Not Found", Response::HTTP_NOT_FOUND);
     
        }
        $item->delete();
        return response(null, Response::HTTP_NO_CONTENT);
    }
}
