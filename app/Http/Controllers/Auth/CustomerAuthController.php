<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\AuthMail;
use App\Models\Address;
use App\Models\Customer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

class CustomerAuthController extends Controller
{
    public function signOut(): RedirectResponse
    {
        auth('customer')->logout();

        return redirect()->route('client.sign-in')->with(['message' => __('Signed out successfully')]);
    }

    public function signIn(Request $request): View
    {
        if ($request->filled('redirect')) {
            session(['url.intended' => $request->input('redirect')]);
        }

        $title = __('sign in');
        $subtitle = __('Sign in as customer');

        return view('client.auth.login', compact('title', 'subtitle'));
    }

    public function signUp(Request $request): View
    {
        if (config('app.sms.sign')) {
            abort(403);
        }

        if ($request->filled('redirect')) {
            session(['url.intended' => $request->input('redirect')]);
        }

        $title = __('sign up');
        $subtitle = __('Sign up as customer');

        return view('client.auth.register', compact('title', 'subtitle'));
    }

    public function signUpNow(Request $request): JsonResponse|RedirectResponse
    {
        if (config('app.sms.sign')) {
            abort(403);
        }

        $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:255'],
            'mobile' => ['required', 'string', 'regex:/^09\d{9}$/', 'unique:customers,mobile'],
            'email' => ['required', 'email', 'unique:customers,email'],
            'address' => ['required', 'string', 'min:10'],
        ], [
            'mobile.regex' => __('Mobile number format is invalid'),
        ]);

        $wantsJson = $this->wantsJsonResponse($request);

        $passwd = generateUniqueID(12);
        Mail::to($request->input('email'))->send(new AuthMail($passwd));

        $customer = new Customer;
        $customer->name = $request->input('name');
        $customer->mobile = $request->input('mobile');
        $customer->email = $request->input('email');
        $customer->password = Hash::make($passwd);
        $customer->save();

        $address = new Address;
        $address->customer_id = $customer->id;
        $address->address = $request->input('address');
        $address->save();

        auth('customer')->login($customer);
        $customer->load('addresses');

        $msg = __('Your account has been created successfully.');
        $emailHint = __("Please check your email to find password, Don't forget check spam/junk too, If you find our email in spam folder, Please mark it `Not spam`");

        if ($wantsJson) {
            return success([
                'profile_complete' => $customer->isCheckoutReady(),
                'addresses' => $customer->addresses,
                'customer' => [
                    'name' => $customer->name,
                    'mobile' => $customer->mobile,
                    'email' => $customer->email,
                ],
            ], $msg.' '.$emailHint);
        }

        return redirect()->intended(route('client.card'))
            ->with(['message' => $msg.' '.$emailHint]);
    }

    public function singInDo(Request $request): JsonResponse|RedirectResponse
    {
        return $this->signInDo($request);
    }

    public function signInDo(Request $request): JsonResponse|RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'string', 'email', 'max:255'],
            'password' => ['required', 'string', 'min:6'],
        ]);

        $wantsJson = $this->wantsJsonResponse($request);

        $customer = Customer::query()->where('email', $request->input('email'))->first();
        if ($customer === null) {
            $msg = __('Email or password is incorrect');

            return $wantsJson
                ? errors([], 422, $msg)
                : redirect()->back()->withErrors([$msg]);
        }

        if (Hash::check($request->input('password'), $customer->password)) {
            auth('customer')->login($customer);
            $customer->load('addresses');

            if ($wantsJson) {
                return success([
                    'profile_complete' => $customer->isCheckoutReady(),
                    'addresses' => $customer->addresses,
                    'customer' => [
                        'name' => $customer->name,
                        'mobile' => $customer->mobile,
                        'email' => $customer->email,
                    ],
                ], __('Signed in successfully'));
            }

            return redirect()->intended(route('client.card'))
                ->with(['message' => __('Signed in successfully')]);
        }

        $msg = __('Email or password is incorrect');

        return $wantsJson
            ? errors([], 422, $msg)
            : redirect()->back()->withErrors([$msg, __('If you forget your password call us')]);
    }

    public function sendSms(Request $request): array
    {
        $tel = $request->input('tel');
        $customer = Customer::query()->where('mobile', $tel)->first();
        $code = rand(11111, 99999);

        if (config('app.sms.driver') === 'Kavenegar') {
            $args = [
                'receptor' => $tel,
                'template' => trim(getSetting('sign')),
                'token' => $code,
            ];
        } else {
            $args = [
                'code' => $code,
            ];
        }

        sendingSMS(getSetting('sign'), $tel, $args);

        Log::info('auth code: '.$code);

        if ($customer === null) {
            $customer = new Customer;
            $customer->mobile = $tel;
            $customer->code = $code;
            $customer->save();
        } else {
            $customer->code = $code;
            $customer->save();
        }

        return [
            'OK' => true,
            'message' => __('Auth code send successfully'),
        ];
    }

    public function checkAuth(Request $request): array
    {
        $request->validate([
            'tel' => ['required', 'string', 'min:6'],
            'code' => ['required', 'string', 'min:5'],
        ]);

        $customer = Customer::query()
            ->where('mobile', $request->input('tel'))
            ->where('code', $request->input('code'))
            ->first();

        if ($customer === null) {
            return [
                'OK' => false,
                'message' => __('Auth code is invalid'),
                'error' => __('Auth code is invalid'),
            ];
        }

        $customer->code = null;
        $customer->save();

        auth('customer')->login($customer);
        $customer->load('addresses');
        $profileComplete = $customer->isCheckoutReady();
        $redirectUrl = $profileComplete
            ? (session()->pull('url.intended') ?: route('client.card'))
            : route('client.card');

        return [
            'OK' => true,
            'message' => __('You are logged in successfully'),
            'redirect' => $redirectUrl,
            'profile_complete' => $profileComplete,
            'addresses' => $customer->addresses,
            'customer' => [
                'name' => $customer->name,
                'mobile' => $customer->mobile,
                'email' => $customer->email,
            ],
        ];
    }

    protected function wantsJsonResponse(Request $request): bool
    {
        return $request->expectsJson() || $request->ajax() || $request->input('format') === 'json';
    }
}
