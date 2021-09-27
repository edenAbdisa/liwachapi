<?php

namespace App\Http\Controllers;

use Exception;
use App\Models\ReportType;
use App\Models\Flag;
use Illuminate\Http\Request;
use Gate;
use App\Http\Resources\ReportTypeResource;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

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
        try {
            $reporttype = ReportType::where('status', '!=', 'deleted')->orWhereNull('status')->get();
            return response()
                ->json(
                    HelperClass::responeObject(
                        $reporttype,
                        true,
                        Response::HTTP_OK,
                        'Successfully fetched.',
                        "Report type are fetched sucessfully.",
                        ""
                    ),
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
                    HelperClass::responeObject(null, false, RESPONSE::HTTP_UNPROCESSABLE_ENTITY, 'Internal server error.', "", $ex->getMessage()),
                    Response::HTTP_UNPROCESSABLE_ENTITY
                );
        }
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
        try {
            $validatedData = Validator::make($request->all(), [
                'report_detail' => ['required', 'max:250'],
                'type_for' => ['required', 'max:50']
            ]);
            if ($validatedData->fails()) {
                return response()
                    ->json([
                        'data' => null,
                        'success' => false,
                        'errors' => [
                            [
                                'status' => Response::HTTP_BAD_REQUEST,
                                'title' => "Validation failed check JSON request",
                                'message' => $validatedData->errors()
                            ],
                        ]
                    ], Response::HTTP_BAD_REQUEST);
            }
            $reportType = ReportType::where('report_detail', Str::ucfirst($request->report_detail))
                ->where('type_for', $request->type_for)->where('status', '!=', 'deleted')
                ->first();
            if (!$reportType) {
                $input = $request->all();
                $input['report_detail'] = Str::ucfirst($input['report_detail']);
                $reporttype = new ReportType($input);
                $reporttype->status = "active";
                $reporttype->report_detail=Str::ucfirst($request->report_detail);  
                if ($reporttype->save()) {
                    return response()
                    ->json(
                        HelperClass::responeObject($reporttype, true, Response::HTTP_CREATED, "Report type created.", "The report type is created sucessfully.", ""),
                        Response::HTTP_CREATED
                    );
                } else {
                    return response()
                        ->json(
                            HelperClass::responeObject(null, false, Response::HTTP_INTERNAL_SERVER_ERROR, "Internal error", "", "The report type couldn't be saved due to internal error"),
                            Response::HTTP_INTERNAL_SERVER_ERROR
                        );
                }
            } else {
                return response()
                    ->json(
                        HelperClass::responeObject($reportType, false, Response::HTTP_CONFLICT, 'Report already exist.', "",  "This report already exist in the database."),
                        Response::HTTP_CONFLICT
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
        try {
            $validatedData = Validator::make($request->all(), [
                'report_detail' => ['max:50'],
                'type_for' => ['max:30'],
                'status' => ['max:50']

            ]);
            if ($validatedData->fails()) {
                return response()
                    ->json(
                        HelperClass::responeObject(null, false, Response::HTTP_BAD_REQUEST, "Validation failed check JSON request", "", $validatedData->errors()),
                        Response::HTTP_BAD_REQUEST
                    );
            }
        $input = $request->all();
        $reporttypes = ReportType::all();
        if ($reporttypes->count() <= 0) {
            return response()
                ->json(
                    HelperClass::responeObject($reporttypes, true, Response::HTTP_OK, 'List of report.', "There is no report type by this search.", ""),
                    Response::HTTP_OK
                );
        }
        $col = DB::getSchemaBuilder()->getColumnListing('report_types');
        $requestKeys = collect($request->all())->keys();
        foreach ($requestKeys as $key) { 
            if (in_array($key, $col)) {
                if ($key == 'report_detail') {
                    $input[$key] = Str::ucfirst($input[$key]);
                }
                $reporttypes = $reporttypes->where($key, $input[$key])->values();
            }
        }
        return response()
        ->json(
            HelperClass::responeObject($reporttypes, true, Response::HTTP_OK, 'List of report.', "List of report by this search.", ""),
            Response::HTTP_OK
        ); 
    }catch (ModelNotFoundException $ex) { // User not found
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
        try {
            $validatedData = Validator::make($request->all(), [
                'report_detail' => ['max:50'],
                'type_for' => ['max:30'],
                'status' => ['max:50']

            ]);
            if ($validatedData->fails()) {
                return response()
                    ->json(
                        HelperClass::responeObject(null, false, Response::HTTP_BAD_REQUEST, "Validation failed check JSON request", "", $validatedData->errors()),
                        Response::HTTP_BAD_REQUEST
                    );
            }
            $input = $request->all();
            $reporttype_to_be_edited = ReportType::where('id', $id)->first();
            if (!$reporttype_to_be_edited) {
                return response()
                        ->json(
                            HelperClass::responeObject(null, false, Response::HTTP_NOT_FOUND, 'Report type doesnt exist.', "This report type doesnt exist in the database.", ""),
                            Response::HTTP_OK
                        ); 
            }
            $reason_used_to_flag = Flag::where('reason_id', $id)->get()->count();
            if ($reason_used_to_flag > 0) {
                return response()
                    ->json(
                        HelperClass::responeObject(null, false, Response::HTTP_CONFLICT, "Reason isn't updated.", "", "Couldn't update the reason as it has already been used to flag items and services."),
                        Response::HTTP_CONFLICT
                    );
            }
            if ($request->report_detail) {
                $reporttype = ReportType::where('report_detail', Str::ucfirst($request->name))->first();
                if ($reporttype && $request->used_for) {
                    $used_for_is_same = strcmp($reporttype->used_for, $request->used_for) == 0 ? true : false;
                    if ($used_for_is_same) {
                        return response()
                    ->json(
                        HelperClass::responeObject($reporttype, false, Response::HTTP_OK, 'Report type already exist.', "", "This report type already exist in the database."),
                        Response::HTTP_OK
                    );
                    }
                    $input['report_detail'] = Str::ucfirst($input['report_detail']);
                }
            }
            if ($reporttype_to_be_edited->fill($input)->save()) {
                return response()
                    ->json(
                        HelperClass::responeObject($reporttype_to_be_edited, true, Response::HTTP_CREATED,'Report type updated.', "The report type is updated sucessfully.", ""),
                        Response::HTTP_CREATED
                    );
            } else {
                return response()
                        ->json(
                            HelperClass::responeObject(null, false, Response::HTTP_INTERNAL_SERVER_ERROR, "Report type couldnt be updated.", "", "This report type couldnt be updated due to internal error"),
                            Response::HTTP_INTERNAL_SERVER_ERROR
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
        try {
            $reporttype = ReportType::find($id);
            if (!$reporttype) {
                response()
                    ->json(
                        HelperClass::responeObject(null, false, Response::HTTP_NOT_FOUND, "Resource Not Found", '', "Report type by this id doesnt exist."),
                        Response::HTTP_NOT_FOUND
                    );
            }
            $reporttype->status = 'deleted';
            $reporttype->save();
            return response()
                ->json(
                    HelperClass::responeObject(null, true, Response::HTTP_OK, 'Successfully deleted.', "Report type is deleted sucessfully.", ""),
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
                    HelperClass::responeObject(null, false, RESPONSE::HTTP_UNPROCESSABLE_ENTITY, 'Internal error occured.', "", $ex->getMessage()),
                    Response::HTTP_INTERNAL_SERVER_ERROR
                );
        }
    }
}
