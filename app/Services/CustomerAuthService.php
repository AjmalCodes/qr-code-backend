<?php

namespace App\Services;

use App\Mail\CustomerEmailVerification;
use App\Models\Customer;
use App\Repositories\CustomerRepository;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

class CustomerAuthService
{
    public function __construct(private CustomerRepository $customerRepository) {}

    public function register(array $data): Customer
    {
        $customer = $this->customerRepository->create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'email_verification_token' => Str::random(40),
            'email_verification_expires_at' => now()->addHours(24),
        ]);

        Mail::to($customer->email)->send(new CustomerEmailVerification($customer));

        return $customer;
    }

    /**
     * @return Customer|'invalid'|'expired'
     */
    public function verifyEmail(string $token): Customer|string
    {
        $customer = $this->customerRepository->findByVerificationToken($token);

        if (! $customer) {
            return 'invalid';
        }

        if ($customer->email_verification_expires_at?->isPast()) {
            return 'expired';
        }

        $this->customerRepository->update($customer, [
            'email_verified_at' => now(),
            'email_verification_token' => null,
            'email_verification_expires_at' => null,
        ]);

        return $customer->fresh();
    }

    public function resendVerification(Customer $customer): void
    {
        $this->customerRepository->update($customer, [
            'email_verification_token' => Str::random(40),
            'email_verification_expires_at' => now()->addHours(24),
        ]);

        Mail::to($customer->email)->send(new CustomerEmailVerification($customer->fresh()));
    }

    public function attemptLogin(string $email, string $password): ?Customer
    {
        $customer = $this->customerRepository->findByEmail($email);

        if (! $customer || ! Hash::check($password, $customer->password)) {
            return null;
        }

        return $customer;
    }

    public function sendPasswordResetLink(string $email): string
    {
        return Password::broker('customers')->sendResetLink(['email' => $email]);
    }

    /**
     * @return string one of Password::PASSWORD_RESET / Password::INVALID_TOKEN / Password::INVALID_USER
     */
    public function resetPassword(string $email, string $token, string $newPassword): string
    {
        return Password::broker('customers')->reset(
            ['email' => $email, 'token' => $token, 'password' => $newPassword],
            function (Customer $customer, string $password) {
                $this->customerRepository->update($customer, ['password' => Hash::make($password)]);
            },
        );
    }
}
