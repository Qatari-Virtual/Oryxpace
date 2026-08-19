<?php

namespace App\Services;

use App\Models\Pilot;
use Illuminate\Support\Facades\Hash;

class PilotAuthenticationService
{
    public function attempt(string $email, string $password): ?Pilot
    {
        $pilot = Pilot::query()
            ->whereRaw('LOWER(email) = ?', [strtolower(trim($email))])
            ->first();

        if (!$pilot || !$pilot->isActive() || !$this->passwordMatches($password, (string) $pilot->password)) {
            return null;
        }

        return $pilot->fresh();
    }

    private function passwordMatches(string $plain, string $stored): bool
    {
        if (preg_match('/^\$2[aby]\$/', $stored) === 1) {
            return Hash::check($plain, $stored);
        }

        if (preg_match('/^[a-f0-9]{32}$/i', $stored) === 1) {
            $matches = hash_equals(strtolower($stored), md5($plain));
        } else {
            $matches = hash_equals($stored, $plain);
        }

        return $matches;
    }

    public function upgradeLegacyPassword(Pilot $pilot, string $plain): void
    {
        $stored = (string) $pilot->password;

        if (preg_match('/^\$2[aby]\$/', $stored) !== 1) {
            $pilot->forceFill(['password' => Hash::make($plain)])->save();
        }
    }
}
