<?php

namespace App\Http\Controllers;

use App\Models\Category; 
use Illuminate\Http\Request;
use Gate;
use App\Http\Resources\CategoryResource;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Http\Resources\Json\JsonResource;

class CategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return (new CategoryResource(Category::all()))
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
        $category= Category::where('name',$request->name)->first();
        if(!$category){
        $category = Category::create($request->all());
        //CHECK IF THE SESSION COOKIE OR THE TOKEN IS RIGH
        //IF IT ISNT RETURN HTTP_FORBIDDEN OR HTTP_BAD_REQUEST
         
        if($category->save()){ 
            return (new CategoryResource($category))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
        }else{ 
            return (new CategoryResource($category))
            ->response()
            ->setStatusCode(Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Category  $category
     * @return \Illuminate\Http\Response
     */
    public function show(Category $category)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Category  $category
     * @return \Illuminate\Http\Response
     */
    public function edit(Category $category)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Category  $category
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Category $category)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Category  $category
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $category = Category::findOrFail($id);
        $category->delete();
    }
}
