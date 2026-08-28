<?php

namespace App\Http\Controllers;

use App\Services\JwtTokenService;
use App\Services\PilotAuthenticationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function __construct(private PilotAuthenticationService $authentication) {}

    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request, JwtTokenService $tokens)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $pilot = $this->authentication->attempt($credentials['email'], $credentials['password']);

        if (!$pilot) {
            return $request->expectsJson()
                ? response()->json(['message' => 'Invalid credentials.'], 422)
                : back()->withErrors(['email' => 'Invalid credentials.']);
        }

        $this->authentication->upgradeLegacyPassword($pilot, $credentials['password']);

        if ($request->expectsJson()) {
            return response()->json([
                'access_token' => $tokens->issue($pilot),
                'token_type' => 'bearer',
                'expires_in' => config('oryxpace.jwt_ttl_minutes') * 60,
                'user' => $pilot->toAuthProfile(),
            ]);
        }

        Auth::guard('web')->login($pilot, $request->boolean('remember'));
        $request->session()->regenerate();

        return redirect()->intended(route('departments'));
    }

    public function me(Request $request)
    {
        return response()->json(['data' => $request->user()->toAuthProfile()]);
    }

    public function logout(Request $request)
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return $request->expectsJson()
            ? response()->json(['success' => true])
            : redirect()->route('login');
    }
}
