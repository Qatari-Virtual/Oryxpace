<?php

namespace Tests\Feature;

use App\Models\Pilot;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PilotAvatarTest extends TestCase
{
    use RefreshDatabase;

    public function test_pilot_get_avatar_url_returns_proxy_url_when_ifc_is_set(): void
    {
        $pilot = new Pilot([
            'name' => 'Divy Patel',
            'callsign' => 'QTR001',
            'ifc' => 'Divy_Patel',
        ]);

        $this->assertStringContainsString('/avatar/divy_patel/128.png', $pilot->getAvatarUrl(128));
        $this->assertStringContainsString('/avatar/divy_patel/64.png', $pilot->getAvatarUrl(64));
    }

    public function test_pilot_get_avatar_url_handles_full_urls(): void
    {
        $pilot1 = new Pilot(['ifc' => 'https://community.infiniteflight.com/u/bumy']);
        $pilot2 = new Pilot(['ifc' => 'https://community.infiniteflight.com/u/arya_joshi/']);
        $pilot3 = new Pilot(['ifc' => 'http://community.infiniteflight.com/u/hypercharged']);

        $this->assertStringContainsString('/avatar/bumy/128.png', $pilot1->getAvatarUrl(128));
        $this->assertStringContainsString('/avatar/arya_joshi/128.png', $pilot2->getAvatarUrl(128));
        $this->assertStringContainsString('/avatar/hypercharged/128.png', $pilot3->getAvatarUrl(128));
    }

    public function test_pilot_get_avatar_url_returns_null_when_ifc_is_null_or_empty(): void
    {
        $pilotWithNull = new Pilot(['ifc' => null]);
        $pilotWithEmpty = new Pilot(['ifc' => '']);

        $this->assertNull($pilotWithNull->getAvatarUrl());
        $this->assertNull($pilotWithEmpty->getAvatarUrl());
    }

    public function test_auth_profile_includes_avatar_url(): void
    {
        $pilot = new Pilot([
            'id' => 1,
            'name' => 'Test Pilot',
            'callsign' => 'QTR100',
            'email' => 'test@qatarivirtual.com',
            'status' => 1,
            'ifc' => 'schyllberg',
        ]);

        $profile = $pilot->toAuthProfile();

        $this->assertArrayHasKey('avatar_url', $profile);
        $this->assertStringContainsString('/avatar/schyllberg/128.png', $profile['avatar_url']);
    }
}
