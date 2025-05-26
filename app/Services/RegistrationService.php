<?php

namespace App\Services;

use App\DTO\RegistrationDTO;
use App\Models\Customer;
use App\Models\Merchant;
use App\Models\VerificationCode;
use Illuminate\Support\Facades\Hash;

class RegistrationService
{
    /**
     * Register a new user based on the provided DTO.
     *
     * @param RegistrationDTO $dto
     * @return array
     */
    public function register(RegistrationDTO $dto): array
    {
        // Check if the user type is valid
        if (!in_array($dto->userType, ['merchant', 'customer'])) {
            return [
                'success' => false,
                'message' => 'Invalid user type.',
                'data' => null
            ];
        }

        // Check if the phone number is already registered
        if ($this->isPhoneNumberRegistered($dto->phoneNumber, $dto->userType)) {
            return [
                'success' => false,
                'message' => 'Phone number is already registered.',
                'data' => null
            ];
        }

        // Register the user based on the user type
        if ($dto->userType === 'merchant') {
            return $this->registerMerchant($dto);
        } else {
            return $this->registerCustomer($dto);
        }
    }

    /**
     * Register a new merchant.
     *
     * @param RegistrationDTO $dto
     * @return array
     */
    private function registerMerchant(RegistrationDTO $dto): array
    {
        // Validate required fields for merchant
        if (empty($dto->businessNameTranslations) || empty($dto->businessType)) {
            return [
                'success' => false,
                'message' => 'Business name and type are required for merchants.',
                'data' => null
            ];
        }

        // Create the merchant
        $merchant = Merchant::create([
            'name' => $dto->nameTranslations,
            'phone_number' => $dto->phoneNumber,
            'email' => $dto->email,
            'password' => Hash::make($dto->password),
            'business_name' => $dto->businessNameTranslations,
            'business_address' => $dto->businessAddressTranslations,
            'business_type' => $dto->businessType,
            'status' => 'pending',
            'subscription_plan' => $dto->subscriptionPlan ?? 'free',
            'subscription_period' => $dto->subscriptionPeriod,
        ]);

        // Generate verification code
        $verificationCode = $this->generateVerificationCode($dto->phoneNumber, 'merchant');

        return [
            'success' => true,
            'message' => 'Merchant registered successfully. Please verify your phone number.',
            'data' => [
                'user' => $merchant,
                'verification_code' => $verificationCode->code, // Remove this in production
            ]
        ];
    }

    /**
     * Register a new customer.
     *
     * @param RegistrationDTO $dto
     * @return array
     */
    private function registerCustomer(RegistrationDTO $dto): array
    {
        // Create the customer
        $customer = Customer::create([
            'name' => $dto->nameTranslations,
            'phone_number' => $dto->phoneNumber,
            'email' => $dto->email,
            'password' => Hash::make($dto->password),
            'address' => $dto->addressTranslations,
            'status' => 'pending',
            'subscription_plan' => $dto->subscriptionPlan ?? 'free',
            'subscription_period' => $dto->subscriptionPeriod,
        ]);

        // Generate verification code
        $verificationCode = $this->generateVerificationCode($dto->phoneNumber, 'customer');

        return [
            'success' => true,
            'message' => 'Customer registered successfully. Please verify your phone number.',
            'data' => [
                'user' => $customer,
                'verification_code' => $verificationCode->code, // Remove this in production
            ]
        ];
    }

    /**
     * Check if the phone number is already registered.
     *
     * @param string $phoneNumber
     * @param string $userType
     * @return bool
     */
    private function isPhoneNumberRegistered(string $phoneNumber, string $userType): bool
    {
        if ($userType === 'merchant') {
            return Merchant::where('phone_number', $phoneNumber)->exists();
        } else {
            return Customer::where('phone_number', $phoneNumber)->exists();
        }
    }

    /**
     * Generate a verification code for the phone number.
     *
     * @param string $phoneNumber
     * @param string $userType
     * @return VerificationCode
     */
    private function generateVerificationCode(string $phoneNumber, string $userType): VerificationCode
    {
        return VerificationCode::generateCode($phoneNumber, $userType);
    }
}
