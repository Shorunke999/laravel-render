<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Register a new user
     */
    public function register(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'first_name' => 'required|string|max:255',
                "last_name" => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users',
                'phone_number' => 'required|string|regex:/^\+?[0-9]{10,15}$/',
                'password' => 'required|string|min:8|confirmed',
                'type' => 'required|in:customer,admin'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'errors' => $validator->errors()
                ], 422);
            }

            $validatedData = $validator->validated();

            if(User::where('email',$validatedData['email'])->first())
            {
                return response()->json([
                    'message' =>  'Email '.$validatedData['email'].' has already been taken',
                ], 400);
            }

            $user = User::create([
                'name' => $validatedData['first_name'] . ' ' .$validatedData['last_name'],
                'email' => $validatedData['email'],
                'phone_number' => $validatedData['phone_number'],
                'password' => Hash::make($validatedData['password']),
                //'type' => $validatedData['type']
                'type' => strtolower($validatedData['type'])
            ]);

            // Create token for the newly registered user
            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'message' => 'User registered successfully',
                'user' => new UserResource($user),
                'access_token' => $token,
                'token_type' => 'Bearer'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error during Registation ',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Login user
     */
    public function login(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'email' => 'required|email',
                'type' => 'in:admin,customer',
                'password' => 'required',
            ]);

            // Set default type if not provided
            if (!$request->has('type') || empty($request->type)) {
                $request->merge(['type' => 'customer']);
            }

            if ($validator->fails()) {
                return response()->json([
                    'errors' => $validator->errors()
                ], 422);
            }

            $user = User::where('email', $request->email)
                        ->where('type', $request->type)
                        ->first();

            if (!$user || !Hash::check($request->password, $user->password)) {
                throw ValidationException::withMessages([
                    'email' => ['The provided credentials are incorrect.'],
                ]);
            }

            // Revoke previous tokens (optional)
            // $user->tokens()->delete();

            // Create new token
            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'message' => 'Login successful',
                'user' => new UserResource($user),
                'access_token' => $token,
                'token_type' => 'Bearer'
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'message' => 'Error Logging in',
                'error' => $th->getMessage()
            ], 422);
        }catch (\Exception $e) {
            return response()->json([
                'message' => 'Error Logging in',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Logout user
     */
    public function logout(Request $request)
    {
        // Revoke the token that was used to authenticate the current request
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logged out successfully'
        ]);
    }

    public function unauthorized()
    {
        return response()->json([
            'message' => 'Unauthorized User'
        ], 401);
    }

    /**
     * Get authenticated user
     */
    public function me(Request $request)
    {
        return response()
        ->json([
            "user"=> new UserResource($request->user())
        ]);
    }
}
