<?php

namespace App\Http\Controllers;

use Exception;
use App\Models\ServiceSwapType;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response;
class ServiceSwapTypeController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    { 
        try{
            $serviceswaptype=ServiceSwapType::all();
            return response()
            ->json(HelperClass::responeObject(
                $serviceswaptype,true, Response::HTTP_OK,'Successfully fetched.',"Service swap type are fetched sucessfully.","")
                , Response::HTTP_OK);
        }catch (ModelNotFoundException $ex) { // User not found
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
     * @param  \App\Models\ServiceSwapType  $serviceSwapType
     * @return \Illuminate\Http\Response
     */
    public function show(ServiceSwapType $serviceSwapType)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\ServiceSwapType  $serviceSwapType
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, ServiceSwapType $serviceSwapType)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\ServiceSwapType  $serviceSwapType
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        try {
            $serviceswaptype = ServiceSwapType::find($id);
            if (!$serviceswaptype) {
                response()
                    ->json(
                        HelperClass::responeObject(null, false, Response::HTTP_NOT_FOUND, "Resource Not Found", '', "Service swap type by this id doesnt exist."),
                        Response::HTTP_NOT_FOUND
                    );
            }
            $serviceswaptype->delete();
            return response()
                ->json(
                    HelperClass::responeObject(null, true, Response::HTTP_NO_CONTENT, 'Successfully deleted.', "Service swap type is deleted sucessfully.", ""),
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
