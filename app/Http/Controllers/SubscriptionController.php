<?php

namespace App\Http\Controllers;

use App\Models\Subscription;
use Illuminate\Http\Request;
use Gate;
use App\Http\Resources\SubscriptionResource;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class SubscriptionController extends Controller
{
    /**
     * @OA\Get(
     *      path="/subscription",
     *      operationId="getSubscriptionList",
     *      tags={"Subscription"},
     *      summary="Get list of Subscription",
     *      description="Returns list of Subscription",
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *          @OA\JsonContent(ref="#/components/schemas/SubscriptionResource")
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
        //abort_if(Gate::denies('subscription_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');
        //User::with(['roles'])->get() 
        $subscription = Subscription::all()
            ->each(function ($item, $key) {
                $item->type;
                $item->user;
            });
        return (new SubscriptionResource($subscription))
            ->response()
            ->setStatusCode(Response::HTTP_OK);
    }


    /**
     * @OA\Post(
     *      path="/subscription",
     *      operationId="storeSubscription",
     *      tags={"Subscription"},
     *      summary="Store new Subscription",
     *      description="Returns subscription data",
     *      @OA\RequestBody(
     *          required=true,
     *          @OA\JsonContent(ref="#/components/schemas/Subscription")
     *      ),
     *      @OA\Response(
     *          response=201,
     *          description="Successful operation",
     *          @OA\JsonContent(ref="#/components/schemas/Subscription")
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
        $subscription = Subscription::create($request->all());
        //CHECK IF THE SESSION COOKIE OR THE TOKEN IS RIGH
        //IF IT ISNT RETURN HTTP_FORBIDDEN OR HTTP_BAD_REQUEST
        //dd("line 81"); 
        if ($subscription->save()) {
            $subscription->type;
            $subscription->user;
            return (new SubscriptionResource($subscription))
                ->response()
                ->setStatusCode(Response::HTTP_CREATED);
        } else {
            return response()
                ->json("This resource couldn't be saved due to internal error", Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * @OA\Get(
     *      path="/subscription/{id}",
     *      operationId="getSubscriptionById",
     *      tags={"Subscription"},
     *      summary="Get subscription information",
     *      description="Returns subscription data",
     *      @OA\Parameter(
     *          name="id",
     *          description="Subscription id",
     *          required=true,
     *          in="path",
     *          @OA\Schema(
     *              type="integer"
     *          )
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *          @OA\JsonContent(ref="#/components/schemas/Subscription")
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
        $subscriptions = Subscription::all();
        $col = DB::getSchemaBuilder()->getColumnListing('subscriptions');
        $requestKeys = collect($request->all())->keys();
        foreach ($requestKeys as $key) {
            if (empty($subscriptions)) {
                return response()->json($subscriptions, 200);
            }
            if (in_array($key, $col)) {
                $subscriptions = $subscriptions->where($key, $input[$key])->values();
            }
        }
        $subscriptions->each(function ($item, $key) {
            $item->type;
            $item->user;
        });
        return response()->json($subscriptions, 200);
    }

    /**
     * @OA\Put(
     *      path="/subscription/{id}",
     *      operationId="updateSubscription",
     *      tags={"Subscription"},
     *      summary="Update existing subscription",
     *      description="Returns updated subscription data",
     *      @OA\Parameter(
     *          name="id",
     *          description="Subscription id",
     *          required=true,
     *          in="path",
     *          @OA\Schema(
     *              type="integer"
     *          )
     *      ),
     *      @OA\RequestBody(
     *          required=true,
     *          @OA\JsonContent(ref="#/components/schemas/UpdateSubscriptionRequest")
     *      ),
     *      @OA\Response(
     *          response=202,
     *          description="Successful operation",
     *          @OA\JsonContent(ref="#/components/schemas/Subscription")
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
        $subscription = Subscription::where('id', $id)->first();
        if ($subscription->fill($input)->save()) {
            $subscription->type;
            $subscription->user;
            return ($subscription)
                ->response()
                ->setStatusCode(Response::HTTP_CREATED);
        }
    }

    /**
     * @OA\Delete(
     *      path="/subscription/{id}",
     *      operationId="deleteSubscription",
     *      tags={"Subscription"},
     *      summary="Delete existing subscription",
     *      description="Deletes a record and returns no content",
     *      @OA\Parameter(
     *          name="id",
     *          description="Subscription id",
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
        $subscription = Subscription::find($id);
        if (!$subscription) {
            return response()
                ->json("Resource Not Found", Response::HTTP_NOT_FOUND);
        }
        $subscription->delete();
        return response(null, Response::HTTP_NO_CONTENT);
    }
}
