<?php

namespace App\Http\Controllers;

use App\Models\ReportType;
use Illuminate\Http\Request;
use Gate;
use App\Http\Resources\ReportTypeResource;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
class ReportTypeController extends Controller
{
    /**
     * @OA\Get(
     *      path="/reporttype",
     *      operationId="getReportTypeList",
     *      tags={"ReportType"},
     *      summary="Get list of ReportType",
     *      description="Returns list of ReportType",
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *          @OA\JsonContent(ref="#/components/schemas/ReportTypeResource")
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
        //abort_if(Gate::denies('reporttype_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');
        //User::with(['roles'])->get() 
        return (new ReportTypeResource(ReportType::all()))
            ->response()
            ->setStatusCode(Response::HTTP_OK);
    }


    /**
     * @OA\Post(
     *      path="/reporttype",
     *      operationId="storeReportType",
     *      tags={"ReportType"},
     *      summary="Store new ReportType",
     *      description="Returns reporttype data",
     *      @OA\RequestBody(
     *          required=true,
     *          @OA\JsonContent(ref="#/components/schemas/ReportType")
     *      ),
     *      @OA\Response(
     *          response=201,
     *          description="Successful operation",
     *          @OA\JsonContent(ref="#/components/schemas/ReportType")
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
        $input=$request->all();
        $reporttype = ReportType::create($input);
        $input['name']=Str::ucfirst($input['name']);
        //CHECK IF THE SESSION COOKIE OR THE TOKEN IS RIGH
        //IF IT ISNT RETURN HTTP_FORBIDDEN OR HTTP_BAD_REQUEST
        //dd("line 81"); 
        if($reporttype->save()){ 
            return (new ReportTypeResource($reporttype))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
        }else{ 
            return response()
                   ->json("This resource couldn't be saved due to internal error", Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * @OA\Get(
     *      path="/reporttype/{id}",
     *      operationId="getReportTypeById",
     *      tags={"ReportType"},
     *      summary="Get reporttype information",
     *      description="Returns reporttype data",
     *      @OA\Parameter(
     *          name="id",
     *          description="ReportType id",
     *          required=true,
     *          in="path",
     *          @OA\Schema(
     *              type="integer"
     *          )
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *          @OA\JsonContent(ref="#/components/schemas/ReportType")
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
        $reporttypes = ReportType::all();  
        $col=DB::getSchemaBuilder()->getColumnListing('report_types'); 
        $requestKeys = collect($request->all())->keys();       
        foreach ($requestKeys as $key) { 
            if(empty($reporttypes)){
                return response()->json($reporttypes, 200);
            }
            if(in_array($key,$col)){ 
                $reporttypes = $reporttypes->where($key,$input[$key]);
            }            
        } 
        return response()->json($reporttypes, 200); 
    }

    /**
     * @OA\Put(
     *      path="/reporttype/{id}",
     *      operationId="updateReportType",
     *      tags={"ReportType"},
     *      summary="Update existing reporttype",
     *      description="Returns updated reporttype data",
     *      @OA\Parameter(
     *          name="id",
     *          description="ReportType id",
     *          required=true,
     *          in="path",
     *          @OA\Schema(
     *              type="integer"
     *          )
     *      ),
     *      @OA\RequestBody(
     *          required=true,
     *          @OA\JsonContent(ref="#/components/schemas/UpdateReportTypeRequest")
     *      ),
     *      @OA\Response(
     *          response=202,
     *          description="Successful operation",
     *          @OA\JsonContent(ref="#/components/schemas/ReportType")
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
        $reporttype_to_be_edited= ReportType::where('id',$id)->first();
        if($reporttype_to_be_edited){        
            if(in_array('name',$input)){
                $reporttype= ReportType::where('name',Str::ucfirst($request->name))->first();
                if($reporttype){
                    return response()->json("A resource exist by this name.", Response::HTTP_CONFLICT);      
                }
                $input['name']=Str::ucfirst($input['name']);
            } 
            if($reporttype_to_be_edited->fill($input)->save()){
                return (new ReportType($reporttype))
                ->response()
                ->setStatusCode(Response::HTTP_CREATED);
            } 
        }
    }

    /**
     * @OA\Delete(
     *      path="/reporttype/{id}",
     *      operationId="deleteReportType",
     *      tags={"ReportType"},
     *      summary="Delete existing reporttype",
     *      description="Deletes a record and returns no content",
     *      @OA\Parameter(
     *          name="id",
     *          description="ReportType id",
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
        $reporttype = ReportType::findOrFail($id);
        $reporttype->delete();
        return response(null, Response::HTTP_NO_CONTENT);
    }
}
