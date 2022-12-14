<?php

namespace App\Http\Controllers;

use App\Http\ApiResponse;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function registerUser(Request $req)
    {

        $req->validate([
            "name" => "required",
            "email" => "email|required|unique:users",
            "password" => "required"
        ]);

        $name = $req->name;
        $email = $req->email;
        $password = $req->password;

        $user =  new User();

        $user->name = $name;
        $user->email = $email;
        $user->password = Hash::make($password);
        $user->save();

        unset($user['roles']);

        return ApiResponse::success($user);
    }

    public function login(Request $req)
    {

        $email = $req->email;
        $password = $req->password;
        $user = User::where(['email' => $email])->first();

        if (!$user) {
            return response([
                "code" => "404",
                "message" => "User not found"
            ]);
        }

        if (!Hash::check($password, $user->password)) {
            return response([
                "message" => "Incorrect Password"
            ]);
        }
        $roles = $user->getRoleNames();
        foreach ($user->tokens as $tKey => $token) {
            $token->delete();
        }

        unset($user['tokens']);
        unset($user['roles']);

        $user['role'] = $roles[0];
        $token = $user->createToken($user->name)->plainTextToken;

        $data = [
            'user' => $user,
            'token' => $token
        ];
        return ApiResponse::success($data);
    }

    public function logout(Request $request)
    {
        if ($request->user()) {
            $request->user()->tokens()->delete();
        }

        return ApiResponse::success("User Logged out successfully");
    }

    public function userDetails(Request $req)
    {


        $user = User::all()->except(Auth::id());

        if (!$user) {
            return ApiResponse::errorGeneral("user not found");
        }
        return ApiResponse::success($user);
    }
}
