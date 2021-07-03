<?php

namespace App\Http\Controllers;

use App\Models\Type;
use Illuminate\Http\Request;
use Gate;
use App\Http\Resources\TypeResource;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Http\Resources\Json\JsonResource;

class TypeController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return (new TypeResource(Type::all()))
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
        $type= Type::where('name',$request->name)->first();
        if(!$type){
        $type = Type::create($request->all());
        //CHECK IF THE SESSION COOKIE OR THE TOKEN IS RIGH
        //IF IT ISNT RETURN HTTP_FORBIDDEN OR HTTP_BAD_REQUEST
         
        if($type->save()){ 
            return (new TypeResource($type))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
        }else{ 
            return (new TypeResource($type))
            ->response()
            ->setStatusCode(Response::HTTP_INTERNAL_SERVER_ERROR);
        }
     }
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Type  $type
     * @return \Illuminate\Http\Response
     */
    public function show(Type $type)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Type  $type
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Type $type)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Type  $type
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $type = Type::findOrFail($id);
        $type->delete();
    }
}
