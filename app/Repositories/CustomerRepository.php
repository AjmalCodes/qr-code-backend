<?php

namespace App\Repositories;

use App\Models\Customer;

class CustomerRepository
{
    public function create(array $data): Customer
    {
        return Customer::create($data);
    }

    public function findByEmail(string $email): ?Customer
    {
        return Customer::where('email', $email)->first();
    }

    public function findByVerificationToken(string $token): ?Customer
    {
        return Customer::where('email_verification_token', $token)->first();
    }

    public function update(Customer $customer, array $data): bool
    {
        return $customer->update($data);
    }
}
