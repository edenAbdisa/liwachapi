<?php

namespace App\Http\Controllers;

use App\Models\Address;
use App\Models\Type;
use App\Models\Item;
use Illuminate\Http\Request;
use Gate;
use App\Http\Resources\ItemResource;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Http\Resources\Json\JsonResource;

class ItemController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return (new ItemResource(Item::all()))
            ->response()
            ->setStatusCode(Response::HTTP_OK);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
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
        $address = $request->address;
        $address['type']='item'; 
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
                $item=Item::create($input);
                if($item->save()){ 
                    return (new ItemResource($item))
                    ->response()
                    ->setStatusCode(Response::HTTP_CREATED);
                }else{ 
                    return (new ItemResource($item))
                    ->response()
                    ->setStatusCode(Response::HTTP_INTERNAL_SERVER_ERROR);
                }            
           }else{
            return ("No such type")
            ->response()
            ->setStatusCode(Response::HTTP_INTERNAL_SERVER_ERROR);
      
           }
        }
        
        //$item = Item::create($request->all());
        //CHECK IF THE SESSION COOKIE OR THE TOKEN IS RIGH
        //IF IT ISNT RETURN HTTP_FORBIDDEN OR HTTP_BAD_REQUEST
        //dd("line 81"); 
        
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Good  $good
     * @return \Illuminate\Http\Response
     */
    public function show(Good $good)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Good  $good
     * @return \Illuminate\Http\Response
     */
    public function edit(Good $good)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Good  $good
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Good $good)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Good  $good
     * @return \Illuminate\Http\Response
     */
    public function destroy(Good $good)
    {
        //
    }
}
