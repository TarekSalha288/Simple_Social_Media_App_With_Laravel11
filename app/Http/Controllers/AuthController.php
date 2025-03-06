<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\UploadImageTrait;
use Illuminate\Support\Facades\Validator;
use  Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
 use UploadImageTrait;
    /**
     * Register a User.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function register() {
        $validator = Validator::make(request()->all(), [
            'name' => 'required',
            'user_name'=>'required|unique:users,id',
            'email' => 'required|email|unique:users',
            'password' => 'required|confirmed|min:8',
            'image_path' => 'image|mimes:jpeg,png,jpg',
        ]);

        if($validator->fails()){
            return response()->json($validator->errors()->toJson(), 400);
        }

        $user = new User;
        $user->name = request()->name;
        $user->email = request()->email;
        $user->password = bcrypt(request()->password);
        $user->user_name = request()->user_name;
        $path=$this->uploadImage( request(),'users',$user->user_name);
        $user->image_path = $path;
        $user->save();
        return response()->json($user, 201);
    }


    /**
     * Get a JWT via given credentials.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function login()
    {
        // Validate credentials
        $credentials = request(['email', 'password']);

        // Attempt to authenticate the user
        if (!$token = auth()->attempt($credentials)) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        // Update the user's FCM token
        $user = auth()->user();
        if(request()->has('fcm')){
            $user->fcm_token = request()->fcm;
            $user->save();
        }


        // Return the token
        return $this->respondWithToken($token);
    }

    /**
     * Get the authenticated User.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function me()
    {
        $followers=User::find(Auth::user()->id)->followers;
        $followings=User::find(Auth::user()->id)->followings;
        return response()->json(['user_info'=>Auth::user(),
    'followers'=>$followers,
'followings'=>$followings]);
    }

    /**
     * Log the user out (Invalidate the token).
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout()
    {
        auth()->logout();

        return response()->json(['message' => 'Successfully logged out']);
    }

    /**
     * Refresh a token.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function refresh()
    {
        return $this->respondWithToken(auth()->refresh());
    }

    /**
     * Get the token array structure.
     *
     * @param  string $token
     *
     * @return \Illuminate\Http\JsonResponse
     */

    protected function respondWithToken($token)
    {
        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth()->factory()->getTTL() * 60 // Convert minutes to seconds
        ]);
    }


}