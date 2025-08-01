<?php

namespace App\Models;

use App\Helpers\OTPHelper;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VerificationCode extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'phone_number',
        'code',
        'type',
        'registration_data',
        'expires_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'expires_at' => 'datetime',
        'registration_data' => 'array',
    ];

    /**
     * Check if the verification code has expired.
     *
     * @return bool
     */
    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    /**
     * Generate a new verification code.
     *
     * @param string $phone_number
     * @param string $type
     * @param array|null $registrationData
     * @param string $purpose 'registration' or 'login'
     * @return self
     */
    public static function generateCode(string $phone_number, string $type, ?array $registrationData = null, string $purpose = 'registration'): self
    {
        // Delete any existing codes for this phone number and type
        self::where('phone_number', $phone_number)
            ->where('type', $type)
            ->delete();

        // Generate OTP code using the shared helper
        $code = OTPHelper::generateByType($type, $purpose);

        // Get expiry time using the helper
        $expiryMinutes = OTPHelper::getExpiryMinutes($type, $purpose);

        // Create and return a new verification code
        return self::create([
            'phone_number' => $phone_number,
            'code' => $code,
            'type' => $type,
            'registration_data' => $registrationData,
            'expires_at' => now()->addMinutes($expiryMinutes),
        ]);
    }

    /**
     * Generate a verification code for registration with user data.
     *
     * @param string $phone_number
     * @param string $type
     * @param array $registrationData
     * @return self
     */
    public static function generateRegistrationCode(string $phone_number, string $type, array $registrationData): self
    {
        return self::generateCode($phone_number, $type, $registrationData);
    }
}
