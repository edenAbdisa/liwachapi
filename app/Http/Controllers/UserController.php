<?php

namespace App\Http\Controllers;

use Exception;
use Illuminate\Http\Request;
use Gate;
use App\Http\Resources\UserResource;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use App\Models\User;
use App\Models\Address;
use App\Models\Membership;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    /**
     * @OA\Get(
     *      path="/users",
     *      operationId="getUsersList",
     *      tags={"User"},
     *      summary="Get list of User",
     *      description="Returns list of User",
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *          @OA\JsonContent(ref="#/components/schemas/UserResource")
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
        $user = User::all()
            ->each(function ($item, $key) {
                $item->address;
                $item->membership;
            });
        return (new UserResource($user))
            ->response()
            ->setStatusCode(Response::HTTP_OK);
    }
    public function internalUsers()
    {
        $user = User::where('status', '!=', 'active')->orWhereNull('status')->
        where('type','!=','user')->
        where('type','!=','organization')->orWhereNull('type')->get();
        return (new UserResource($user))
            ->response()
            ->setStatusCode(Response::HTTP_OK);
    }
    public function organizationByStatus($status)
    {
        $user = User::where('status', '=', $status)->
        where('type','=','organization')->get()->each(
            function ($item, $key) {
            $item->address;
            $item->membership;
        });
        return (new UserResource($user))
            ->response()
            ->setStatusCode(Response::HTTP_OK);
    }
    public function userCount()
    {
        //abort_if(Gate::denies('request_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');
        //User::with(['roles'])->get() 
        //$wordCount = Wordlist::where('id', '<=', $correctedComparisons)->count();
        $userGrouped = User::where('status', '=', 'active')->where(function($q) {
            $q->where('type', 'user')
              ->orWhere('type', 'organization');
        })->get()->groupBy(function($item) {
            return $item->type;
        });
        foreach($userGrouped as $key => $user){
            $day = $key;
            $totalCount = $user->count();
            $userGrouped[$key]=$totalCount;
           }          
        return response()
        ->json($userGrouped,Response::HTTP_OK);
    }
    public function userCountByDate($attribute)
    {
        try{
        $items = User::orderBy($attribute)->get()->groupBy(function($item) {
             return $item->created_at->format('Y-m-d');
       });
       }catch(Exception $e){
        return response()
        ->json("There is no such attribute.",Response::HTTP_OK);
       }
       foreach($items as $key => $item){
        $day = $key;
        $totalCount = $item->count();
        $items[$key]=$totalCount;
       }
        return response()
            ->json($items,Response::HTTP_OK);
    }
    public function login(Request $request)
    {
        $user = User::where('email', $request->email)->first();
        $response = ['user' => $user];
        if ($user) {
            if (Hash::check($request->password, $user->password)) {
                $token = $user->createToken('Laravel Password Grant Client')->accessToken;
                $user['remember_token'] = $token;
                $response = ['user' => $user];
                $user->address;
                $user->membership;
                return $user->save() ? response($response, 200) :
                    "Couldn't provide token for user";
            } else {
                $response = ["message" => "Password mismatch"];
                return response($response, 422);
            }
        } else {
            $response = ['message' => 'User doesnt not exist'];
            return response($response, 422);
        }
    }
    public function logout(Request $request)
    {
        $token = $request->user()->token();
        $token->revoke();
        $user = User::where('id', $token->user_id)->first();
        $user['remember_token'] = '';
        $response['message'] = $user->save() ? 'You have been successfully logged out!' : 'We could not successfully log out your account please try again!';
        return response($response, 200);
    }
    /**
     * @OA\Post(
     *      path="/user",
     *      operationId="storeUser",
     *      tags={"User"},
     *      summary="Store new User",
     *      description="Returns user data",
     *      @OA\RequestBody(
     *          required=true,
     *          @OA\JsonContent(ref="#/components/schemas/User")
     *      ),
     *      @OA\Response(
     *          response=201,
     *          description="Successful operation",
     *          @OA\JsonContent(ref="#/components/schemas/User")
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
        $input = $request->all();
        /* return $request->all();
        $input['first_name']=$request->first_name;
        $input['last_name']=$request->last_name;
        $input['email']=$request->email;
        $input['profile_picture']=$request->profile_picture;
        $input['phone_number']=$request->phone_number;
        $input['TIN_picture']=$request->TIN_picture;
        $input['status']=$request->status;
        $input['birthdate']=$request->birthdate;
        $input['type']=$request->type;
        $input['membership_id']=$request->membership_id; */
        $user = User::where('email', $request->email)->first();
        if (!$user) {
            $input['password'] = Hash::make($request->password);
            $input['remember_token'] = Str::random(10);
            $user = User::create($input);
            $token = "user->createToken('Laravel Password Grant')->accessToken";
            $user['remember_token'] = $token;
            $address = $request->address;
            $address = Address::create($address);
            if (!$address->save()) {
                return response()
                    ->json("The address resource couldn't be saved due to internal error", Response::HTTP_INTERNAL_SERVER_ERROR);
            }
            $user['address_id'] = $address->id;
            $saveduser = $user->save();
            if ($saveduser) {
                $user->address;
                $user->membership;
                return response(new UserResource($user), Response::HTTP_CREATED);
            } else {
                return response()
                    ->json("This resource couldn't be saved due to internal error", Response::HTTP_INTERNAL_SERVER_ERROR);
            }
        } else {
            return response()
                ->json("An account already exist by this email.", Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * @OA\Get(
     *      path="/users/{id}",
     *      operationId="getUserById",
     *      tags={"Users"},
     *      summary="Get user information",
     *      description="Returns user data",
     *      @OA\Parameter(
     *          name="id",
     *          description="User id",
     *          required=true,
     *          in="path",
     *          @OA\Schema(
     *              type="integer"
     *          )
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *          @OA\JsonContent(ref="#/components/schemas/User")
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
        $users = User::all();
        $col = DB::getSchemaBuilder()->getColumnListing('users');
        $requestKeys = collect($request->all())->keys();
        foreach ($requestKeys as $key) {
            if (empty($users)) {
                return response()->json($users, 200);
            }
            if (in_array($key, $col)) {
                $users = $users->where($key, $input[$key]);
            }
        }
        $users->each(function ($item, $key) {
            $item->address;
            $item->membership;
        });
        return response()->json($users, 200);
    }

    /**
     * @OA\Put(
     *      path="/users/{id}",
     *      operationId="updateUser",
     *      tags={"Users"},
     *      summary="Update existing user",
     *      description="Returns updated user data",
     *      @OA\Parameter(
     *          name="id",
     *          description="User id",
     *          required=true,
     *          in="path",
     *          @OA\Schema(
     *              type="integer"
     *          )
     *      ),
     *      @OA\RequestBody(
     *          required=true,
     *          @OA\JsonContent(ref="#/components/schemas/UpdateUserRequest")
     *      ),
     *      @OA\Response(
     *          response=202,
     *          description="Successful operation",
     *          @OA\JsonContent(ref="#/components/schemas/User")
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
        $user = User::where('id', $id)->first();
        if (in_array('address', $input)) {
            $address = Address::where('id', $user->address_id)->first();
            if(!$address->fill($input["address"])->save()){
                return response()
                ->json($address, Response::HTTP_INTERNAL_SERVER_ERROR);
            }
        } 
        if (in_array('password', $input)) {
            $input['password'] = Hash::make($request->password);
        }
        if ($user->fill($input)->save()) {
            $user->address;
            $user->membership;
            return (new UserResource($user))
                ->response()
                ->setStatusCode(Response::HTTP_CREATED);
        }
    }
    /**
     * @OA\Delete(
     *      path="/users/{id}",
     *      operationId="deleteUser",
     *      tags={"Userss"},
     *      summary="Delete existing user",
     *      description="Deletes a record and returns no content",
     *      @OA\Parameter(
     *          name="id",
     *          description="User id",
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
        $user = User::find($id);
        if (!$user) {
            return response()
                ->json("Resource Not Found", Response::HTTP_NOT_FOUND);
        }
        $user->status='deleted';
        $user->save();
        return response(null, Response::HTTP_NO_CONTENT);
    }
}
