<?php

namespace App\Repositories;

use App\Models\Customer;

class CustomerRepository extends BaseRepository
{
    /**
     * Create a new repository instance.
     */
    public function __construct(Customer $model)
    {
        parent::__construct($model);
    }

    /**
     * Find customer by phone number.
     *
     * @param string $phoneNumber
     * @return \App\Models\Customer|null
     */
    public function findByPhoneNumber(string $phoneNumber): ?Customer
    {
        return $this->model->where('phone_number', $phoneNumber)->first();
    }

    /**
     * Find customer by email.
     *
     * @param string $email
     * @return \App\Models\Customer|null
     */
    public function findByEmail(string $email): ?Customer
    {
        return $this->model->where('email', $email)->first();
    }

    /**
     * Check if phone number exists.
     *
     * @param string $phoneNumber
     * @param int|null $excludeId
     * @return bool
     */
    public function phoneNumberExists(string $phoneNumber, ?int $excludeId = null): bool
    {
        $query = $this->model->where('phone_number', $phoneNumber);

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }

    /**
     * Check if email exists.
     *
     * @param string $email
     * @param int|null $excludeId
     * @return bool
     */
    public function emailExists(string $email, ?int $excludeId = null): bool
    {
        $query = $this->model->where('email', $email);

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }

    /**
     * Get active customers.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getActiveCustomers()
    {
        return $this->model->where('status', 'active')->get();
    }

    /**
     * Get verified customers.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getVerifiedCustomers()
    {
        return $this->model->where('is_verified', true)->get();
    }

    /**
     * Update customer verification status.
     *
     * @param int $id
     * @param bool $isVerified
     * @return bool
     */
    public function updateVerificationStatus(int $id, bool $isVerified = true): bool
    {
        return $this->update($id, [
            'is_verified' => $isVerified,
            'phone_verified_at' => $isVerified ? now() : null,
            'status' => $isVerified ? 'active' : 'pending',
        ]);
    }
}
