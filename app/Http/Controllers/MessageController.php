<?php

namespace App\Http\Controllers;

use App\Models\Message;
use Illuminate\Http\Request;
use Gate;
use App\Http\Resources\MessageResource;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
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
        //abort_if(Gate::denies('address_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');
        //User::with(['roles'])->get() 
        $message= Message::all()
                         ->each(function($item, $key) {
                            $item->sender ;
                            $item->chat ; 
                        });
        return (new MessageResource($message))
            ->response()
            ->setStatusCode(Response::HTTP_OK);
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
        $message = Message::create($request->all());
        //CHECK IF THE SESSION COOKIE OR THE TOKEN IS RIGH
        //IF IT ISNT RETURN HTTP_FORBIDDEN OR HTTP_BAD_REQUEST
        //dd("line 81"); 
        if($message->save()){ 
            $message->bartering_location ;
            $message->type ;
            return (new MessageResource($message))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
        }else{ 
            return response()
                   ->json("This resource couldn't be saved due to internal error", Response::HTTP_INTERNAL_SERVER_ERROR);
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
        $input = $request->all();
        $messages = Message::all();  
        $col=DB::getSchemaBuilder()->getColumnListing('messages'); 
        $requestKeys = collect($request->all())->keys();       
        foreach ($requestKeys as $key) { 
            if(empty($messages)){
                return response()->json($messages, 200);
            }
            if(in_array($key,$col)){ 
                $messages = $messages->where($key,$input[$key]);
            }            
        } 
        $messages->each(function($item, $key) {
            $item->bartering_location ;
            $item->type ; 
        });
        return response()->json($messages, 200); 
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
        $input = $request->all();          
        $message= Message::where('id',$id)->first();
        if($message->fill($input)->save()){
            $message->bartering_location ;
            $message->type ;
            return ($message)
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
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
        $message = Message::findOrFail($id);
        $message->delete();
        return response(null, Response::HTTP_NO_CONTENT);
    }
}
