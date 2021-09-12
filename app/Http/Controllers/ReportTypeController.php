<?php

namespace App\Http\Controllers;

use Exception;
use App\Models\ReportType;
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
            try{
                $reporttype=ReportType::where('status', '!=', 'deleted')->orWhereNull('status')->get();
                return response()
                ->json(HelperClass::responeObject(
                    $reporttype,true, Response::HTTP_OK,'Successfully fetched.',"Report type are fetched sucessfully.","")
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
                ->where('type_for', $request->type_for)
                ->first();
            if (!$reportType) {
                $input = $request->all();
                $input['report_detail'] = Str::ucfirst($input['report_detail']);
                $reporttype = new ReportType($input);
                $reporttype->status = "active";
                //CHECK IF THE SESSION COOKIE OR THE TOKEN IS RIGH
                //IF IT ISNT RETURN HTTP_FORBIDDEN OR HTTP_BAD_REQUEST
                //dd("line 81"); 
                if ($reporttype->save()) {
                    return response()
                        ->json([
                            'data' => $reporttype,
                            'success' => true,
                            'errors' => [
                                [
                                    'status' => Response::HTTP_CREATED,
                                    'title' => 'Report type created.',
                                    'message' => "The report type is created sucessfully."
                                ],
                            ]
                        ], Response::HTTP_CREATED);
                } else {
                    return response()
                        ->json([
                            'data' => $reporttype,
                            'success' => false,
                            'errors' => [
                                [
                                    'status' => Response::HTTP_INTERNAL_SERVER_ERROR,
                                    'title' => 'Internal error',
                                    'message' => "This report type couldnt be saved."
                                ],
                            ]
                        ], Response::HTTP_INTERNAL_SERVER_ERROR);
                }
            } else {
                return response()
                    ->json([
                        'data' => $reportType,
                        'success' => false,
                        'errors' => [
                            [
                                'status' => Response::HTTP_CONFLICT,
                                'title' => 'Report type already exist.',
                                'message' => "This report type already exist in the database."
                            ],
                        ]
                    ], Response::HTTP_CONFLICT);
            }
        } catch (ModelNotFoundException $ex) { // User not found
            return response()
                ->json([
                    'success' => false,
                    'errors' => [
                        [
                            'status' => RESPONSE::HTTP_UNPROCESSABLE_ENTITY,
                            'title' => 'The model doesnt exist.',
                            'message' => $ex->getMessage()
                        ],
                    ]
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (Exception $ex) { // Anything that went wrong
            return response()
                ->json([
                    'success' => false,
                    'errors' => [
                        [
                            'status' => 500,
                            'title' => 'Internal server error',
                            'message' => $ex->getMessage()
                        ],
                    ]
                ], Response::HTTP_INTERNAL_SERVER_ERROR);
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
        $col = DB::getSchemaBuilder()->getColumnListing('report_types');
        $requestKeys = collect($request->all())->keys();
        foreach ($requestKeys as $key) {
            if (empty($reporttypes)) {
                return response()->json($reporttypes, 200);
            }
            if (in_array($key, $col)) {
                $reporttypes = $reporttypes->where($key, $input[$key])->values();
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
        try {
            $validatedData = Validator::make($request->all(), [
                'report_detail' => ['max:50'],
                'type_for' => ['max:30'],
                'status' => ['max:50']
            
            ]);
            if ($validatedData->fails()) {
                return response()
                ->json( HelperClass::responeObject(null,false, Response::HTTP_BAD_REQUEST,"Validation failed check JSON request","",$validatedData->errors())
                , Response::HTTP_BAD_REQUEST);
            }
            $input = $request->all();
            $reporttype_to_be_edited = ReportType::where('id', $id)->first();
            if (!$reporttype_to_be_edited) {
                return response()
                    ->json([
                        'data' => null,
                        'success' => false,
                        'errors' => [
                            [
                                'status' => Response::HTTP_CONFLICT,
                                'title' => 'Report type doesnt exist.',
                                'message' => "This report type doesnt exist in the database."
                            ],
                        ]
                    ], Response::HTTP_CONFLICT);
            }
            if ($request->report_detail) {
                $reporttype = ReportType::where('report_detail', Str::ucfirst($request->name))->first();
                if ($reporttype && $request->used_for) {
                    $used_for_is_same = strcmp($reporttype->used_for, $request->used_for) == 0 ? true : false;
                    if ($used_for_is_same) {
                        return response()
                            ->json([
                                'data' => $reporttype,
                                'success' => false,
                                'errors' => [
                                    [
                                        'status' => Response::HTTP_CONFLICT,
                                        'title' => 'Report type already exist.',
                                        'message' => "This report type already exist in the database."
                                    ],
                                ]
                            ], Response::HTTP_CONFLICT);
                    }
                    $input['report_detail'] = Str::ucfirst($input['report_detail']);
                }
            }
            if ($reporttype_to_be_edited->fill($input)->save()) {
                return response()
                    ->json([
                        'data' => $reporttype_to_be_edited,
                        'success' => true,
                        'errors' => [
                            [
                                'status' => Response::HTTP_CREATED,
                                'title' => 'Report type updated.',
                                'message' => "The report type is updated sucessfully."
                            ],
                        ]
                    ], Response::HTTP_CREATED);
            } else {
                return response()
                    ->json([
                        'data' => $reporttype,
                        'success' => false,
                        'errors' => [
                            [
                                'status' => Response::HTTP_INTERNAL_SERVER_ERROR,
                                'title' => 'Internal error',
                                'message' => "This report type couldnt be updated."
                            ],
                        ]
                    ], Response::HTTP_INTERNAL_SERVER_ERROR);
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
                    HelperClass::responeObject(null, true, Response::HTTP_NO_CONTENT, 'Successfully deleted.', "Report type is deleted sucessfully.", ""),
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
