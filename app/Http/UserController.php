<?php
namespace App\Http\Controllers;
use Illuminate\Http\Response;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
class UserController extends Controller
{
    /*
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return User::all();
    }
    public function login(Request $request){
    	$user= User::where('email',$request->email)->first();
    	$response=['user'=> $user];
    	if( $user) {
    		if(Hash::check($request->password,$user->password)){
    		   $token=$user->createToken('Laravel Password Grant Client')->accessToken;
    		   $user['remember_token']= $token;
    		   $response=['user'=> $user];
    		   return $user->save()? response($response,200):
    			  "Couldn't provide token for user"; 
    		}else{
    		   $response=["message"=>"Password mismatch"];
          		   return response($response,422);
    		}
    	}else{
    		$response=['message'=>'User doesnt not exist'];
    		return response($response,422);
    	}
    }
    public function logout(Request $request){ 
	$token= $request->user()->token();
	$token->revoke();
	$user= User::where('id',$token->user_id)->first();
	$user['remember_token']='';
	$response['message'] = $user->save()? 'You have been successfully logged out!':'We could not successfully log out your account please try again!';
	return response($response,200);	
    }
    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {    
        $input = $request->all(); 
	$user= User::where('email',$request->email)->first();
	if(!$user){
	$input['password']=Hash::make($input['password']);
    	$input['remember_token'] = Str::random(10);   
        $user=User::create($input);
    	$token = $user->createToken('Laravel Password Grant Client')->accessToken;
    	$user['remember_token']= $token;
	$saveduser= $user->save();
	  if($saveduser){
        	$response=['user'=> $saveduser];
		$response=['message'=>'Successfully registered'];
          }else{
		$response=['user'=> $saveduser];
		$response=['message'=>'Could not register user'];
	  }
	}else{
		$response=['message'=>'An account already exist by this email please login'];
	}
   	return response($response,200);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\User  $user
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        return User::find($id);
    }

    public function update(Request $request,  $id)
    {
        $user = User::findOrFail($id);
        $input = $request->all();
        return $user->fill($input)->save(); 
    }
    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\uuid  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy( $id)
    {
        $user = User::findOrFail($id);
        $user->delete();
    }
}
