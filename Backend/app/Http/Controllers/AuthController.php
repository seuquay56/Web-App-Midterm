<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\RegisterRequest;
use App\Models\User;

class AuthController extends Controller
{
    public function show_register() {
        return view('authentications.register');
    }

    public function show_login() {
        return view('authentications.login');
    }

    public function register(RegisterRequest $request){
        // validated() only the fields that have rules,
        // The confirmation field isn't included
        // User::create, create the user and inserts the row
        $user = User::create($request->validated());

        return response() -> json(
            ['user' => $user,
            // createToken('web')->plainTextToken: issues the token
             'token' => $user->createToken('web')->plainTextToken]
             , 201
        );
    }
}
