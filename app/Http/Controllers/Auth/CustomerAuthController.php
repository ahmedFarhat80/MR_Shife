<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\VerificationCode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class CustomerAuthController extends Controller
{
    /**
     * Register a new customer.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'phone_number' => 'required|string|unique:customers,phone_number',
            'email' => 'nullable|email|unique:customers,email',
            'password' => 'required|string|min:8|confirmed',
            'address' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $customer = Customer::create([
            'name' => $request->name,
            'phone_number' => $request->phone_number,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'address' => $request->address,
            'status' => 'pending',
        ]);

        // Generate verification code
        $verificationCode = VerificationCode::generateCode(
            $request->phone_number,
            'customer'
        );

        // In a real application, you would send this code via SMS
        // For now, we'll just return it in the response for testing
        return response()->json([
            'message' => 'Customer registered successfully. Please verify your phone number.',
            'verification_code' => $verificationCode->code, // Remove this in production
            'user' => $customer,
        ], 201);
    }

    /**
     * Verify phone number with code.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function verifyPhone(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone_number' => 'required|string|exists:customers,phone_number',
            'code' => 'required|string|size:6|regex:/^[0-9]{6}$/',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $verificationCode = VerificationCode::where('phone_number', $request->phone_number)
            ->where('type', 'customer')
            ->where('code', $request->code)
            ->first();

        if (!$verificationCode) {
            return response()->json(['message' => 'Invalid verification code.'], 400);
        }

        if ($verificationCode->isExpired()) {
            return response()->json(['message' => 'Verification code has expired.'], 400);
        }

        // Update customer
        $customer = Customer::where('phone_number', $request->phone_number)->first();
        $customer->phone_verified_at = now();
        $customer->is_verified = true;
        $customer->status = 'active';
        $customer->save();

        // Delete the verification code
        $verificationCode->delete();

        // Log the user in
        Auth::guard('customer')->login($customer);

        return response()->json([
            'message' => 'Phone number verified successfully.',
            'user' => $customer,
        ]);
    }

    /**
     * Login a customer.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone_number' => 'required|string',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        if (!Auth::guard('customer')->attempt($request->only('phone_number', 'password'))) {
            throw ValidationException::withMessages([
                'phone_number' => ['The provided credentials are incorrect.'],
            ]);
        }

        $customer = Auth::guard('customer')->user();

        if ($customer->status !== 'active') {
            Auth::guard('customer')->logout();
            return response()->json(['message' => 'Your account is not active.'], 403);
        }

        return response()->json([
            'message' => 'Logged in successfully.',
            'user' => $customer,
        ]);
    }

    /**
     * Logout a customer.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout(Request $request)
    {
        Auth::guard('customer')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json(['message' => 'Logged out successfully.']);
    }
}
