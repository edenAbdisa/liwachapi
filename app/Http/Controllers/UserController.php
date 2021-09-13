<?php

namespace App\Http\Controllers;

use App\Http\Resources\AddressResource;
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
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

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
        try {
            $user = User::where('status', '!=', 'deleted')
                ->orWhereNull('status')->get()
                ->each(function ($item, $key) {
                    $item->address;
                    $item->membership;
                    $item->remember_token = "";
                });
            return response()
                ->json(
                    HelperClass::responeObject(
                        $user,
                        true,
                        Response::HTTP_OK,
                        'Successfully fetched.',
                        "Users are fetched sucessfully.",
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
    public function internalUsers($status)
    {
        $user = User::where('status', '=', $status)->orWhereNull('status')->where('type', '!=', 'user')->where('type', '!=', 'organization')->orWhereNull('type')->get();
        return (new UserResource($user))
            ->response()
            ->setStatusCode(Response::HTTP_OK);
    }
    public function organizationByStatus($status)
    {
        $user = User::where('status', '=', $status)->where('type', '=', 'organization')->get()->each(
            function ($item, $key) {
                $item->address;
                $item->membership;
            }
        );
        return (new UserResource($user))
            ->response()
            ->setStatusCode(Response::HTTP_OK);
    }
    public function userCount()
    {
        //abort_if(Gate::denies('request_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');
        //User::with(['roles'])->get() 
        //$wordCount = Wordlist::where('id', '<=', $correctedComparisons)->count();
        $userGrouped = User::where('status', '=', 'active')->where(function ($q) {
            $q->where('type', 'user')
                ->orWhere('type', 'org');
        })->get()->groupBy(function ($item) {
            return $item->type;
        });
        foreach ($userGrouped as $key => $user) {
            $day = $key;
            $totalCount = $user->count();
            $userGrouped[$key] = $totalCount;
        }
        return response()
            ->json($userGrouped, Response::HTTP_OK);
    }
    public function userCountByDate($attribute, $start, $end)
    {
        try {
            $items = User::orderBy($attribute)->whereBetween($attribute, [$start, $end])->get()->groupBy(function ($item) {
                return $item->created_at->format('Y-m-d');
            });
        } catch (Exception $e) {
            return response()
                ->json("There is no such attribute.", Response::HTTP_OK);
        }
        foreach ($items as $key => $item) {
            $day = $key;
            $totalCount = $item->count();
            $items[$key] = $totalCount;
        }
        return response()
            ->json($items, Response::HTTP_OK);
    }
    public function login(Request $request)
    {
        try {
            $validatedData = Validator::make($request->all(), [ 
                'email' => ['required'],
                'password' => ['required']
            ]);
            if ($validatedData->fails()) {
                return response()
                    ->json(
                        HelperClass::responeObject(null, false, Response::HTTP_BAD_REQUEST, "Validation failed check JSON request", "", $validatedData->errors()),
                        Response::HTTP_BAD_REQUEST
                    );
            }
        $user = User::where('email', $request->email)->where('status','!=','blocked')->first();
        if ($user) {
            if (Hash::check($request->password, $user->password)) {
                $token = $user->createToken('Laravel Password Grant', [$user->type])->accessToken;
                $user['remember_token'] = $token;
                if ($user->save()) {
                    $user->address;
                    $user->membership;
                    return response()
                ->json(
                    HelperClass::responeObject($user, true, Response::HTTP_OK,'User found',"User is succesfully loged in.",""),
                    Response::HTTP_OK
                );
                }else{
                    return response()
                    ->json(
                        HelperClass::responeObject($user, true, Response::HTTP_INTERNAL_SERVER_ERROR,'Internal Error',"","An error occured while trying to log in."),
                        Response::HTTP_INTERNAL_SERVER_ERROR
                    );
                }
                
            } else {
                return response()
                ->json(
                    HelperClass::responeObject(null, false, Response::HTTP_CONFLICT,'Password issue.',"The password doesnt match the email.",""),
                    Response::HTTP_CONFLICT
                );
            }
        } else {
            return response()
                ->json(
                    HelperClass::responeObject(null, false, Response::HTTP_NOT_FOUND,'User doesnt exist.',"The is no registered user by this email.",""),
                    Response::HTTP_NOT_FOUND
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
            HelperClass::responeObject(null, false, RESPONSE::HTTP_INTERNAL_SERVER_ERROR, 'Internal server error.', "", $ex->getMessage()),
            Response::HTTP_INTERNAL_SERVER_ERROR
        );
}}
    public function logout(Request $request)
    {
        try{
        $token = $request->user()->token(); 
        $token->revoke();
        $user = User::where('id', $token->user_id)->first();
        $user['remember_token'] = '';
        if($user->save()){ 
        return response()
        ->json(
            HelperClass::responeObject(null, true, RESPONSE::HTTP_OK, 'Successfully logged out', "You have been successfully logged out!", ""),
            Response::HTTP_OK
        );
    }else{
        return response()
        ->json(
            HelperClass::responeObject(null, true, RESPONSE::HTTP_INTERNAL_SERVER_ERROR, 'logout failure.', "We could not successfully log out your account please try again!", ""),
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
}}
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
        try {
            $validatedData = Validator::make($request->all(), [
                'first_name' => ['required','max:20'],
                'last_name' => ['required','max:20'],
                'email' => ['required','max:255'],
                'password' => ['required','max:12'],
                'phone_number' => ['required','max:30'], 
                'birthdate' => ['required','max:15'],
                'type' => ['required',Rule::in(['user','organization','hr','operations'])], 
                'membership_id' => ['required','numeric'],
                'address.latitude' => ['required', 'numeric'],
                'address.longitude' => ['required', 'numeric'],
                'address.country' => ['required', 'max:50'],
                'address.city' => ['required', 'max:50'],
                'address.type' => ['required', 'max:10', Rule::in(['user'])]

            ]);
            if ($validatedData->fails()) {
                return response()
                    ->json(
                        HelperClass::responeObject(null, false, Response::HTTP_BAD_REQUEST, "Validation failed check JSON request", "", $validatedData->errors()),
                        Response::HTTP_BAD_REQUEST
                    );
            }
        $input = $request->all();
        $user = User::where('email', $request->email)->first();
        if (!$user) {
            $user = User::where('phone_number', $request->phone_number)->first();
            if($user){
                return response()
                ->json(
                    HelperClass::responeObject(null, false, Response::HTTP_CONFLICT,'User already exist.', "",  "A user already exist by this phonenumber "),
                    Response::HTTP_CONFLICT
                );
            }
            $user = new User($input);
            $user->password = Hash::make($request->password);
            $user->remember_token  = $user->createToken('Laravel Password Grant')->accessToken;
            $address = $request->address;
            $address = Address::create($address);
            if ($user->type === "organization") {
                $user->status = "pending";
            }
            $user->status = "active";
                $user->address_id = $address->id;
                if($user->save()){
                    $user->address;
                    $user->membership;
                    return response()
                ->json(
                    HelperClass::responeObject($user, true, Response::HTTP_CREATED,'User created.',"A user is created by the details given.",""),
                    Response::HTTP_CREATED
                );
                }else{
                    return response()
                    ->json(
                        HelperClass::responeObject(null, false, Response::HTTP_INTERNAL_SERVER_ERROR,'Internal error', "",  "This user couldnt be saved."),
                        Response::HTTP_INTERNAL_SERVER_ERROR
                    );
                } 
        } else {
            return response()
                ->json(
                    HelperClass::responeObject(null, false, Response::HTTP_CONFLICT,'User already exist.', "",  "A user already exist by this email "),
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
        try {
            $validatedData = Validator::make($request->all(), [
                'first_name' => ['max:20'],
                'last_name' => ['max:20'],
                'email' => ['max:255'],
                'phone_number' => ['max:30'],
                'status' => ['max:255'],
                'birthdate' => ['max:15'],
                'type' => ['numeric'],
                'address_id' => ['numeric'],
                'membership_id' => ['numeric']
            ]);
            if ($validatedData->fails()) {
                return response()
                    ->json(
                        HelperClass::responeObject(null, false, Response::HTTP_BAD_REQUEST, "Validation failed check JSON request", "", $validatedData->errors()),
                        Response::HTTP_BAD_REQUEST
                    );
            }
        $input = $request->all();
        $users = User::all();
        $col = DB::getSchemaBuilder()->getColumnListing('users');
        /* $user = $request->user();
            if($user){
                $request->request->add(['id' => $user->id]);
            }  */
        $requestKeys = collect($request->all())->keys();
        foreach ($requestKeys as $key) {
            if (empty($users)) {
                return response()->json($users, 200);
            }
            if (in_array($key, $col)) {
                $users = $users->where($key, $input[$key])->values();
            }
        }
        $users->each(function ($item, $key) {
            $item->address;
            $item->membership;
        });
        return response()->json($users, 200);
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
        try {
            $validatedData = Validator::make($request->all(), [
                'first_name' => ['max:20'],
                'last_name' => ['max:20'],
                'email' => ['max:255'],
                'phone_number' => ['max:30'],
                'status' => ['max:255'],
                'birthdate' => ['max:15'],
                'type' => ['numeric'],
                'address_id' => ['numeric'],
                'memebrship_id' => ['numeric']
            ]);
            if ($validatedData->fails()) {
                return response()
                    ->json(
                        HelperClass::responeObject(null, false, Response::HTTP_BAD_REQUEST, "Validation failed check JSON request", "", $validatedData->errors()),
                        Response::HTTP_BAD_REQUEST
                    );
            }
            $input = $request->all();
            $user = User::where('id', $id)->first();
            
            if ($request->address) {
                $address_to_be_updated = $request->address;
                $address = Address::where('id', $user->bartering_location_id)->first();
                $address->city = $address_to_be_updated['city'];
                $address->country = $address_to_be_updated['country'];
                $address->latitude = (float)$address_to_be_updated['latitude'];
                $address->longitude = (float)$address_to_be_updated['longitude'];
                $address->save();
            }
            $user_to_be_updated = $user->fill($input);
            $user_to_be_updated->status=$user->status;
            if ($request->password) {
                $user_to_be_updated->password = Hash::make($request->password);
            }
            if ($user_to_be_updated->save()) {
                $user_to_be_updated->address;
                $user_to_be_updated->membership;
                return response()
                    ->json(
                        HelperClass::responeObject($user_to_be_updated, true, Response::HTTP_CREATED, 'User updated.', "The user is updated sucessfully.", ""),
                        Response::HTTP_CREATED
                    );
            } else {
                return response()
                    ->json(
                        HelperClass::responeObject($user_to_be_updated, false, Response::HTTP_INTERNAL_SERVER_ERROR, 'Internal error', "", "This user couldnt be updated."),
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
    public function destroy(Request $request)
    {
        try {
            $user = $request->user();
            if (!$user) {
                response()
                    ->json(
                        HelperClass::responeObject(null, false, Response::HTTP_NOT_FOUND, "Resource Not Found", '', "User by this id doesnt exist."),
                        Response::HTTP_NOT_FOUND
                    );
            }
            $user->status = 'deleted';
            $user->save();
            return response()
                ->json(
                    HelperClass::responeObject(null, true, Response::HTTP_OK, 'Successfully deleted.', "User is deleted sucessfully.", ""),
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
