<?php

namespace App\Http\Controllers;

use Exception;
use App\Models\Message;
use Illuminate\Http\Request;
use Gate;
use App\Http\Resources\MessageResource;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class MessageController extends Controller
{
    /**
     * @OA\Get(
     *      path="/messages",
     *      operationId="getMessageList",
     *      tags={"Message"},
     *      summary="Get list of Message",
     *      description="Returns list of Message",
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *          @OA\JsonContent(ref="#/components/schemas/MessageResource")
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
            $message = Message::all()->each(function ($item, $key) {
                $item->sender;
                $item->request;
            });
            return response()
                ->json(
                    HelperClass::responeObject(
                        $message,
                        true,
                        Response::HTTP_OK,
                        'Successfully fetched.',
                        "Message are fetched sucessfully.",
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
     *      path="/message",
     *      operationId="storeMessage",
     *      tags={"Message"},
     *      summary="Store new Message",
     *      description="Returns message data",
     *      @OA\RequestBody(
     *          required=true,
     *          @OA\JsonContent(ref="#/components/schemas/Message")
     *      ),
     *      @OA\Response(
     *          response=201,
     *          description="Successful operation",
     *          @OA\JsonContent(ref="#/components/schemas/Message")
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
                'type' => ['required','max:10'],
                'chat_id' => ['required'],
                'content'=> ['required']
            ]);
            if ($validatedData->fails()) {
                return response()
                    ->json(
                        HelperClass::responeObject(null, false, Response::HTTP_BAD_REQUEST, "Validation failed check JSON request", "", $validatedData->errors()),
                        Response::HTTP_BAD_REQUEST
                    );
            }
        $user =$request->user();
        $message = new Message($request->all());
        $message->sender_id=$user->id;
        if ($message->save()) {
            $message->bartering_location;
            $message->type;
            return response()
                    ->json(
                        HelperClass::responeObject($message, true, Response::HTTP_CREATED, "Message created.", "Message sent.", ""),
                        Response::HTTP_CREATED
            );
        } else {
            return response()
                        ->json(
                            HelperClass::responeObject(null, false, Response::HTTP_INTERNAL_SERVER_ERROR, "Message couldnt be saved.", "", "Message not sent."),
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
     * @OA\Get(
     *      path="/message/{id}",
     *      operationId="getMessageById",
     *      tags={"Messages"},
     *      summary="Get message information",
     *      description="Returns message data",
     *      @OA\Parameter(
     *          name="id",
     *          description="Message id",
     *          required=true,
     *          in="path",
     *          @OA\Schema(
     *              type="integer"
     *          )
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *          @OA\JsonContent(ref="#/components/schemas/Message")
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
                'type' => ['max:10']
            ]);
            if ($validatedData->fails()) {
                return response()
                    ->json(
                        HelperClass::responeObject(null, false, Response::HTTP_BAD_REQUEST, "Validation failed check JSON request", "", $validatedData->errors()),
                        Response::HTTP_BAD_REQUEST
                    );
            } 
            $messages = Message::all();
            $col = DB::getSchemaBuilder()->getColumnListing('messages');
            $user = $request->user();
            if($user){
                $request->request->add(['sender_id' => $user->id]);
            }
            $input = $request->all();
            $requestKeys = collect($request->all())->keys();
            foreach ($requestKeys as $key) {
                if (empty($messages)) {
                    return response()->json($messages, 200);
                }
                if (in_array($key, $col)) {
                    $messages = $messages->where($key, $input[$key])->values();
                }
            }
            $messages->each(function ($item, $key) {
                $item->bartering_location;
                $item->type;
            });
            return response()->json($messages, 200);
        } catch (ModelNotFoundException $ex) { // User not found
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
     *      path="/messages/{id}",
     *      operationId="updateMessage",
     *      tags={"Message"},
     *      summary="Update existing message",
     *      description="Returns updated message data",
     *      @OA\Parameter(
     *          name="id",
     *          description="Message id",
     *          required=true,
     *          in="path",
     *          @OA\Schema(
     *              type="integer"
     *          )
     *      ),
     *      @OA\RequestBody(
     *          required=true,
     *          @OA\JsonContent(ref="#/components/schemas/UpdateMessageRequest")
     *      ),
     *      @OA\Response(
     *          response=202,
     *          description="Successful operation",
     *          @OA\JsonContent(ref="#/components/schemas/Message")
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
                'type' => ['max:10']
            ]);
            if ($validatedData->fails()) {
                return response()
                    ->json(
                        HelperClass::responeObject(null, false, Response::HTTP_BAD_REQUEST, "Validation failed check JSON request", "", $validatedData->errors()),
                        Response::HTTP_BAD_REQUEST
                    );
            }
            $input = $request->all();
            $message = Message::where('id', $id)->first();
            if ($message->fill($input)->save()) {
                $message->bartering_location;
                $message->type;
                return ($message)
                    ->response()
                    ->setStatusCode(Response::HTTP_CREATED);
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
     *      path="/messages/{id}",
     *      operationId="deleteMessage",
     *      tags={"Message"},
     *      summary="Delete existing message",
     *      description="Deletes a record and returns no content",
     *      @OA\Parameter(
     *          name="id",
     *          description="Message id",
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
            $message = Message::find($id);
            if (!$message) {
                response()
                    ->json(
                        HelperClass::responeObject(null, false, Response::HTTP_NOT_FOUND, "Resource Not Found", '', "Message by this id doesnt exist."),
                        Response::HTTP_NOT_FOUND
                    );
            }
            $message->delete();
            return response()
                ->json(
                    HelperClass::responeObject(null, true, Response::HTTP_NO_CONTENT, 'Successfully deleted.', "Message is deleted sucessfully.", ""),
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
