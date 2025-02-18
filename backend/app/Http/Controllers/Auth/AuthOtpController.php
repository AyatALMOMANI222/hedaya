<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Crypt;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\UserOtp;
use Twilio\Rest\Client;


class AuthOtpController extends Controller
{
    // Show the login page with mobile number
    public function login()
    {
        return view('Auth.OtpLogin');
    }

    // Combined Generate OTP for Login/Register
    public function generateOtpForLoginRegister(Request $request)
    {
        $request->validate([
            'mobile_no' => 'required|regex:/^(\+?(\d{1,3}))?(\d{10})$/', // Mobile number validation (ensure proper format)
        ]);

        // Generate OTP
        $userOtp = $this->generateOTP($request->mobile_no);

        // Ensure OTP object is valid
        if ($userOtp instanceof UserOtp) {
            $this->sendSMS($request->mobile_no, $userOtp->otp);

            // Determine if it's a login or registration
            $user = User::where('mobile_no', $request->mobile_no)->first();
            $action = $user ? 'login' : 'register';

            return response()->json([
                'success'=> true,
                'message' => 'OTP has been sent.',
                'mobile_no' => $request->mobile_no,
                'action' => $action
            ], 200);
        }

        // Error if OTP generation failed
        return response()->json(['error' => 'Failed to generate OTP.'], 500);
    }


    public function verifyOtp(Request $request)
    {
        $request->validate([
            'otp' => 'required|digits:5',  // Ensure OTP is exactly 6 digits
            'mobile_no' => 'required|regex:/^(\+?(\d{1,3}))?(\d{10})$/', // Mobile number validation (ensure proper format)
        ]);

        // Retrieve OTP record for the mobile number
        $user = User::where('mobile_no', $request->mobile_no)->first();

        if (!$user) {
            return response()->json(['error' => 'User not found.'], 400);
        }

        $userOtp = UserOtp::where('user_id', $user->id)
            ->where('otp', $request->otp)
            ->first();

        // Validate OTP
        $now = now();
        if (!$userOtp) {
            return response()->json(['error' => 'Invalid OTP.'], 400);
        } elseif ($now->isAfter($userOtp->expire_at)) {
            return response()->json(['error' => 'OTP has expired.'], 400);
        }

        // Check if the user already exists
        $user = User::where('mobile_no', $request->mobile_no)->first();

        if (!$user) {
            // Create new user if they don't exist
            $user = User::create([
                'mobile_no' => $request->mobile_no
            ]);
        }

        // Delete OTP after use
        $userOtp->delete();

        // Log in the user
        Auth::login($user);

        // Generate a token using Sanctum
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'success'=> true,
            'message' => 'Authentication successful!',
            'user' => $user,
            'token' => $token,
            'token_type' => 'Bearer',
        ], 200);
    }
    // Function to generate OTP

    private function generateOTP($mobile_no)
    {
        $user = User::where('mobile_no', $mobile_no)->first();
    
        if (!$user) {
            $user = User::create([
                'mobile_no' => $mobile_no // Only mobile_no is needed
            ]);
        }
    
        // Generate OTP
        $otp = rand(12345, 99999);
    
        // Encrypt the OTP before storing it
        // $encryptedOtp = Crypt::encryptString($otp);
    
        // Create OTP record with encrypted OTP
        $userOtp = UserOtp::create([
            'user_id' => $user->id,
            'mobile_no' => $mobile_no,
            'otp' =>$otp ,
            'expire_at' => now()->addMinutes(10),
        ]);
    
        return $userOtp; // Return OTP object
    }
    

    // Function to send SMS using Twilio (mock implementation)
    // private function sendSMS($receiverNumber, $otp)
    // {
    //     // This is a mock API. Replace with actual Twilio integration if needed.
    //     $message = 'Your OTP is: ' . $otp;

    //     try {
    //         // Example Twilio integration (commented out)
    //         // $TwilioSid = env('TWILIO_SID');
    //         // $TwilioToken = env('TWILIO_AUTH_TOKEN');
    //         // $TwilioNumber = env('TWILIO_NUMBER');

    //         // $client = new \Twilio\Rest\Client($TwilioSid, $TwilioToken);
    //         // $client->messages->create('+91' . $receiverNumber, [
    //         //     'from' => $TwilioNumber,
    //         //     'body' => $message
    //         // ]);

    //         // Log the OTP for testing purposes

    //     } catch (\Exception $e) {
    //         return response()->json(['error' => $e->getMessage()], 500);
    //     }
    // }








public function sendSMS($receiverNumber, $otp)
{
    // إعدادات Twilio
    $sid = env('TWILIO_SID');
    $authToken = env('TWILIO_AUTH_TOKEN');
    $twilioPhoneNumber = env('TWILIO_PHONE_NUMBER');

    // إنشاء عميل Twilio
    $client = new Client($sid, $authToken);

    // إرسال الرسالة عبر Twilio
    try {
        $client->messages->create(
            $receiverNumber, // الرقم المستلم
            [
                'from' => $twilioPhoneNumber, // الرقم الخاص بحساب Twilio
                'body' => 'Your OTP is: ' . $otp, // محتوى الرسالة
            ]
        );

        return true; // نجاح إرسال الرسالة
    } catch (\Exception $e) {
    return response()->json([
        'error'=>'an error occured',
        'message'=>$e->getMessage()
    ],500);
    }
}

}