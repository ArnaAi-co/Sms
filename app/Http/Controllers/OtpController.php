<?php


use App\Models\MainSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

class OtpController extends \App\Http\Controllers\Controller
{
    public function sendOtp(Request $request)
    {
        $request->validate([
            'phone_number' => 'required|regex:/^09[0-9]{9}$/'
        ]);

        $phoneNumber = $request->phone_number;
        $cacheKey = 'otp_cooldown_' . $phoneNumber;

        // Check if OTP was sent within the last 2 minutes
        // if (Cache::has($cacheKey)) {
        //     return response()->json([
        //         'message' => __('Please wait 2 minutes before requesting another OTP')
        //     ], 429);
        // }

        // Generate a 6-digit OTP
        $otp = mt_rand(100000, 999999);

        // Store OTP in session or cache for verification
        Session::put('otp_' . $phoneNumber, $otp);
        Session::put('otp_expires_at_' . $phoneNumber, \App\Http\Controllers\now()->addMinutes(10));

        // Send SMS via IPPanel
        $input_data = [
            "code" => $otp,
        ];
        $ippanel = new \App\Helpers\Ippanel();
        $sms = $ippanel->sendPattern(\App\Http\Controllers\env('OTP_PATTERN'), $request->phone_number, $input_data);

        // Set 2-minute cooldown
        Cache::put($cacheKey, true, \App\Http\Controllers\now()->addMinutes(2));

        return \App\Http\Controllers\response()->json([
            'message' => \App\Http\Controllers\__('OTP sent successfully')
        ]);
    }

    public function loginWithOtp(Request $request)
    {

        $request->validate([
            'phone_number_otp' => 'required|regex:/^09[0-9]{9}$/',
            'otp' => 'required|digits:6'
        ]);
        $phoneNumber = $request->phone_number_otp;



        $otp = $request->otp;

        $storedOtp = Session::get('otp_' . $phoneNumber);
        $expiresAt = Session::get('otp_expires_at_' . $phoneNumber);

        if (!$storedOtp || \App\Http\Controllers\now()->gt($expiresAt) || $storedOtp != $otp) {
            return \App\Http\Controllers\back()->withErrors(['otp' => \App\Http\Controllers\__('Invalid or expired OTP')])->withInput();
        }


        // Ensure the phone number starts with +98 instead of 0
        if (substr($phoneNumber, 0, 1) == '0') {
            $phoneNumber = '+98' . substr($phoneNumber, 1);
        }

        $settings = MainSetting::first();
        $user = User::where('phone_number', $phoneNumber)->first();

        if (!$user) {
            // Create temporary user and redirect to complete registration
            $user = User::create([
                'phone_number' => $phoneNumber,
                'country' => 'Iran',
                'email' => 'temp_' . uniqid() . '@example.com', // Temporary unique email
                'name' => 'temp_' . uniqid(), // Temporary unique name
                'password' => Hash::make('temp_' . uniqid()), // Temporary unique password
                'status' => 'active',
                'group' => \App\Http\Controllers\config('settings.default_user'),
                'images' => $settings->image_credits ?? 0,
                'tokens' => $settings->token_credits ?? 0,
                'characters' => \App\Http\Controllers\config('settings.voiceover_welcome_chars'),
                'minutes' => \App\Http\Controllers\config('settings.whisper_welcome_minutes'),
                'default_voiceover_language' => \App\Http\Controllers\config('settings.voiceover_default_language'),
                'default_voiceover_voice' => \App\Http\Controllers\config('settings.voiceover_default_voice'),
                'default_template_language' => \App\Http\Controllers\config('settings.default_language'),
                'default_model_template' => \App\Http\Controllers\config('settings.default_model_user_template'),
                'default_model_chat' => \App\Http\Controllers\config('settings.default_model_user_bot'),
                'job_role' => 'Happy Person',
                'referral_id' => strtoupper(Str::random(15)),
            ]);

            // Assign default role if it exists
            $defaultRole = \App\Http\Controllers\config('settings.default_user');
            if ($defaultRole && Role::where('name', $defaultRole)->exists()) {
                $user->assignRole($defaultRole);
            } else {
                \Log::warning("Default role '$defaultRole' not found for user with phone: $phoneNumber");
            }

            try {
                if (\App\Http\Controllers\config('services.settings.sms.after_register_enabled') == 1) {
                    $text = \App\Http\Controllers\config('services.settings.sms.after_register_text');
                    if (!empty($text)) {
                        $ippanel = new \App\Helpers\Ippanel();
                        $ippanel->sendSms($phoneNumber, $text);
                    }
                }
            } catch (\Exception $exception) {

            }


            // Store user ID in session for completion
            Session::put('pending_user_id', $user->id);

            return \App\Http\Controllers\redirect()->route('complete.registration');
        }

        try {
            if (\App\Http\Controllers\config('services.settings.sms.after_login_enabled') == 1) {
                $text = \App\Http\Controllers\config('services.settings.sms.after_login_text');

                if (!empty($text)) {
                    $ippanel = new \App\Helpers\Ippanel();
                    $ippanel->sendSms($phoneNumber, $text);
                }
            }
        }catch (\Exception $exception){

        }

        Auth::login($user);
        Session::forget(['otp_' . $phoneNumber, 'otp_expires_at_' . $phoneNumber]);

        return \App\Http\Controllers\redirect()->intended(\App\Http\Controllers\route('user.dashboard'));
    }


    public function showCompleteRegistrationForm()
    {
        return \App\Http\Controllers\view('auth.complete-registration');
    }

    public function completeRegistration(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email',
        ]);

        $userId = Session::get('pending_user_id');
        $user = User::find($userId);

        if (!$user) {
            return \App\Http\Controllers\redirect()->route('login')->withErrors(['error' => \App\Http\Controllers\__('Invalid registration attempt')]);
        }

        $user->update([
            'name' => $request->name,
            'email' => $request->email,
        ]);

        Session::forget('pending_user_id');
        Auth::login($user);

        return \App\Http\Controllers\redirect()->route('user.dashboard');
    }
}
