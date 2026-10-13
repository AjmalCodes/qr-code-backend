<?php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Api\Concerns\ApiResponses;
use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\ForgotPasswordRequest;
use App\Http\Requests\Customer\RegisterRequest;
use App\Http\Requests\Customer\ResetPasswordRequest;
use App\Http\Requests\Customer\VerifyEmailRequest;
use App\Http\Requests\LoginRequest;
use App\Models\Customer;
use App\Services\CustomerAuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;

class AuthController extends Controller
{
    use ApiResponses;

    public function __construct(private CustomerAuthService $customerAuthService) {}

    /**
     * Create an account and dispatch an email verification code. The
     * account cannot log in or claim offers until it is verified.
     */
    public function signup(RegisterRequest $request): JsonResponse
    {
        $customer = $this->customerAuthService->register($request->validated());

        return $this->success(
            $this->present($customer),
            'Account created. Please check your email to verify your address.',
            201,
        );
    }

    /**
     * Validate the emailed verification token, activating the account.
     */
    public function verifyEmail(VerifyEmailRequest $request): JsonResponse
    {
        $result = $this->customerAuthService->verifyEmail($request->validated('token'));

        if ($result === 'invalid') {
            return $this->error('This verification code is invalid.', null, 422);
        }

        if ($result === 'expired') {
            return $this->error('This verification code has expired. Please request a new one.', null, 422);
        }

        return $this->success($this->present($result), 'Email verified successfully');
    }

    /**
     * Log in — blocked until the account's email is verified.
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $credentials = $request->validated();

        $customer = $this->customerAuthService->attemptLogin($credentials['email'], $credentials['password']);

        if (! $customer) {
            return $this->error('The provided credentials are incorrect.', [
                'email' => ['The provided credentials are incorrect.'],
            ], 422);
        }

        if (! $customer->hasVerifiedEmail()) {
            return $this->error('Please verify your email address before logging in.', null, 403);
        }

        $token = $customer->createToken('customer-api-token')->plainTextToken;

        return $this->success([
            'customer' => $this->present($customer),
            'token' => $token,
        ], 'Login successful');
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user('customer')->currentAccessToken()->delete();

        return $this->success(null, 'Logged out successfully');
    }

    public function profile(Request $request): JsonResponse
    {
        return $this->success($this->present($request->user('customer')));
    }

    /**
     * Send a password reset code. Always returns success, win or lose, so
     * this endpoint cannot be used to enumerate registered email addresses.
     */
    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        $this->customerAuthService->sendPasswordResetLink($request->validated('email'));

        return $this->success(null, 'If that email address is registered, a reset code has been sent.');
    }

    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        $status = $this->customerAuthService->resetPassword(
            $request->validated('email'),
            $request->validated('token'),
            $request->validated('password'),
        );

        if ($status !== Password::PASSWORD_RESET) {
            return $this->error('This password reset code is invalid or has expired.', null, 422);
        }

        return $this->success(null, 'Password reset successfully');
    }

    /**
     * @return array<string, mixed>
     */
    private function present(Customer $customer): array
    {
        return [
            'id' => $customer->id,
            'name' => $customer->name,
            'email' => $customer->email,
            'email_verified' => $customer->hasVerifiedEmail(),
        ];
    }
}
