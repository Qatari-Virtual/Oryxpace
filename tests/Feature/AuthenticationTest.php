<?php

namespace Tests\Feature;

use App\Models\Pilot;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_api_login_uses_pilots_and_returns_a_bearer_token(): void
    {
        Pilot::create([
            'name' => 'Test Pilot',
            'callsign' => 'QRV123',
            'email' => 'pilot@example.com',
            'password' => Hash::make('secret'),
            'status' => 1,
        ]);

        $response = $this->postJson('/auth/login', [
            'email' => 'PILOT@example.com',
            'password' => 'secret',
        ]);

        $response->assertOk()
            ->assertJsonPath('token_type', 'bearer')
            ->assertJsonPath('user.email', 'pilot@example.com');

        $this->getJson('/auth/me', [
            'Authorization' => 'Bearer ' . $response->json('access_token'),
        ])->assertOk()->assertJsonPath('data.callsign', 'QRV123');
    }

    public function test_legacy_password_is_upgraded_after_login(): void
    {
        $pilot = Pilot::create([
            'name' => 'Legacy Pilot',
            'callsign' => 'QRV124',
            'email' => 'legacy@example.com',
            'password' => md5('secret'),
            'status' => 1,
        ]);

        $this->postJson('/auth/login', [
            'email' => $pilot->email,
            'password' => 'secret',
        ])->assertOk();

        $this->assertTrue(Hash::check('secret', $pilot->fresh()->password));
    }

    public function test_python_bcrypt_passwords_are_supported(): void
    {
        Pilot::create([
            'name' => 'Python Pilot',
            'callsign' => 'QRV126',
            'email' => 'python@example.com',
            'password' => str_replace('$2y$', '$2b$', Hash::make('secret')),
            'status' => 1,
        ]);

        $this->postJson('/auth/login', [
            'email' => 'python@example.com',
            'password' => 'secret',
        ])->assertOk();
    }

    public function test_inactive_pilots_cannot_login_or_access_browser_pages(): void
    {
        Pilot::create([
            'name' => 'Inactive Pilot',
            'callsign' => 'QRV125',
            'email' => 'inactive@example.com',
            'password' => Hash::make('secret'),
            'status' => 0,
        ]);

        $this->postJson('/auth/login', [
            'email' => 'inactive@example.com',
            'password' => 'secret',
        ])->assertStatus(422);

        $this->get('/departments')->assertRedirect(route('login'));
    }
}
