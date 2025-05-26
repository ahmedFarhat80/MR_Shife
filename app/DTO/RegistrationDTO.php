<?php

namespace App\DTO;

class RegistrationDTO
{
    /**
     * Create a new DTO instance.
     *
     * @param array $nameTranslations
     * @param string $phoneNumber
     * @param string|null $email
     * @param string $password
     * @param string $userType
     * @param array|null $businessNameTranslations
     * @param array|null $businessAddressTranslations
     * @param string|null $businessType
     * @param array|null $addressTranslations
     * @param string|null $subscriptionPlan
     * @param string|null $subscriptionPeriod
     */
    public function __construct(
        public array $nameTranslations,
        public string $phoneNumber,
        public ?string $email,
        public string $password,
        public string $userType,
        public ?array $businessNameTranslations = null,
        public ?array $businessAddressTranslations = null,
        public ?string $businessType = null,
        public ?array $addressTranslations = null,
        public ?string $subscriptionPlan = null,
        public ?string $subscriptionPeriod = null,
    ) {
    }

    /**
     * Create a new DTO from request data.
     *
     * @param array $data
     * @param string $userType
     * @return self
     */
    public static function fromRequest(array $data, string $userType): self
    {
        // Process name translations
        $nameTranslations = [];
        if (isset($data['name_en'])) {
            $nameTranslations['en'] = $data['name_en'];
        }
        if (isset($data['name_ar'])) {
            $nameTranslations['ar'] = $data['name_ar'];
        }

        // Process business name translations for service providers
        $businessNameTranslations = null;
        if ($userType === 'merchant' && isset($data['business_name_en'])) {
            $businessNameTranslations = [];
            $businessNameTranslations['en'] = $data['business_name_en'];
            if (isset($data['business_name_ar'])) {
                $businessNameTranslations['ar'] = $data['business_name_ar'];
            }
        }

        // Process business address translations for service providers
        $businessAddressTranslations = null;
        if ($userType === 'merchant' && isset($data['business_address_en'])) {
            $businessAddressTranslations = [];
            $businessAddressTranslations['en'] = $data['business_address_en'];
            if (isset($data['business_address_ar'])) {
                $businessAddressTranslations['ar'] = $data['business_address_ar'];
            }
        }

        // Process address translations for customers
        $addressTranslations = null;
        if ($userType === 'customer' && isset($data['address_en'])) {
            $addressTranslations = [];
            $addressTranslations['en'] = $data['address_en'];
            if (isset($data['address_ar'])) {
                $addressTranslations['ar'] = $data['address_ar'];
            }
        }

        return new self(
            nameTranslations: $nameTranslations,
            phoneNumber: $data['phone_number'] ?? '',
            email: $data['email'] ?? null,
            password: $data['password'] ?? '',
            userType: $userType,
            businessNameTranslations: $businessNameTranslations,
            businessAddressTranslations: $businessAddressTranslations,
            businessType: $data['business_type'] ?? null,
            addressTranslations: $addressTranslations,
            subscriptionPlan: $data['subscription_plan'] ?? null,
            subscriptionPeriod: $data['subscription_period'] ?? null,
        );
    }
}
