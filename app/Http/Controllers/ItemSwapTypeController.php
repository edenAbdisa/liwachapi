<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Models\ItemSwapType; 
use Gate; 
use Symfony\Component\HttpFoundation\Response; 
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException; 
class ItemSwapTypeController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    { 
        try{
            $itemswap=ItemSwapType::all();
            return response()
            ->json(HelperClass::responeObject($itemswap,true, Response::HTTP_OK,'Successfully fetched.',"Item swap is fetched sucessfully.","")
                , Response::HTTP_OK);
        } catch (ModelNotFoundException $ex) { // User not found
            return response()
            ->json( HelperClass::responeObject(null,false, RESPONSE::HTTP_UNPROCESSABLE_ENTITY,'The model doesnt exist.',"",$ex->getMessage())
              , Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (Exception $ex) { // Anything that went wrong
            return response()
            ->json( HelperClass::responeObject(null,false, RESPONSE::HTTP_UNPROCESSABLE_ENTITY,'Internal server error.',"",$ex->getMessage())
            , Response::HTTP_UNPROCESSABLE_ENTITY);
               
        }

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
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\SwapType  $swapType
     * @return \Illuminate\Http\Response
     */
    public function show(ItemSwapType $swapType)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\SwapType  $swapType
     * @return \Illuminate\Http\Response
     */
    public function edit(ItemSwapType $swapType)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\SwapType  $swapType
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, ItemSwapType $swapType)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\SwapType  $swapType
     * @return \Illuminate\Http\Response
     */
    public function destroy( $id)
    {
        try {
            $itemswaptype = ItemSwapType::find($id);
            if (!$itemswaptype) {
                response()
                    ->json(
                        HelperClass::responeObject(null, false, Response::HTTP_NOT_FOUND, "Resource Not Found", '', "Item swap type by this id doesnt exist."),
                        Response::HTTP_NOT_FOUND
                    );
            } 
            $itemswaptype->delete();
            return response()
                ->json(
                    HelperClass::responeObject(null, true, Response::HTTP_NO_CONTENT, 'Successfully deleted.', "Item swap type is deleted sucessfully.", ""),
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
