<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class RegistrationSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'session_id',
        'user_type',
        'data',
        'current_step',
        'otp_verified',
        'expires_at',
    ];

    protected $casts = [
        'data' => 'array',
        'otp_verified' => 'boolean',
        'expires_at' => 'datetime',
    ];

    /**
     * Create a new registration session.
     *
     * @param string $userType
     * @param array $data
     * @param int $step
     * @return static
     */
    public static function createSession(string $userType, array $data, int $step = 1): self
    {
        // Clean up expired sessions
        self::cleanupExpiredSessions();

        return self::create([
            'session_id' => Str::uuid()->toString(),
            'user_type' => $userType,
            'data' => $data,
            'current_step' => $step,
            'otp_verified' => false,
            'expires_at' => now()->addHours(24), // Session expires in 24 hours
        ]);
    }

    /**
     * Find session by session ID.
     *
     * @param string $sessionId
     * @return static|null
     */
    public static function findBySessionId(string $sessionId): ?self
    {
        return self::where('session_id', $sessionId)
            ->where('expires_at', '>', now())
            ->first();
    }

    /**
     * Update session data.
     *
     * @param array $newData
     * @param int|null $step
     * @return bool
     */
    public function updateSessionData(array $newData, ?int $step = null): bool
    {
        $currentData = $this->data ?? [];
        $mergedData = array_merge($currentData, $newData);

        $updateData = ['data' => $mergedData];

        if ($step !== null) {
            $updateData['current_step'] = $step;
        }

        return $this->update($updateData);
    }

    /**
     * Check if session is expired.
     *
     * @return bool
     */
    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    /**
     * Clean up expired sessions.
     *
     * @return int
     */
    public static function cleanupExpiredSessions(): int
    {
        return self::where('expires_at', '<', now())->delete();
    }

    /**
     * Get session data with a specific key.
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function getDataValue(string $key, $default = null)
    {
        return $this->data[$key] ?? $default;
    }

    /**
     * Check if session can proceed to a specific step.
     *
     * @param int $targetStep
     * @return bool
     */
    public function canProceedToStep(int $targetStep): bool
    {
        // For step 3 (subscription), OTP must be verified
        if ($targetStep === 3) {
            return $this->current_step >= 2 && $this->otp_verified;
        }

        return $this->current_step >= ($targetStep - 1);
    }

    /**
     * Mark OTP as verified.
     *
     * @return bool
     */
    public function markOTPVerified(): bool
    {
        return $this->update(['otp_verified' => true]);
    }

    /**
     * Check if OTP is verified.
     *
     * @return bool
     */
    public function isOTPVerified(): bool
    {
        return $this->otp_verified;
    }

    /**
     * Check if session requires OTP verification.
     *
     * @return bool
     */
    public function requiresOTPVerification(): bool
    {
        return $this->current_step >= 2 && !$this->otp_verified;
    }
}
