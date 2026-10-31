<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthAntiSlopTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_sign_in_contains_no_ai_slop(): void
    {
        $response = $this->get(route('client.sign-in'));

        $response->assertStatus(200);
        $response->assertDontSee('VIP Customer Club');
        $response->assertDontSee('Secure 256-Bit SSL');
        $response->assertDontSee('24/7 Dedicated Support');
        $response->assertDontSee('btn-primary w-100 py-2.5 rounded-pill');
        $response->assertSee('auth-card');
        $response->assertSee('aria-hidden="true"', false);
    }

    public function test_admin_login_contains_no_ai_slop(): void
    {
        $response = $this->get(route('login'));

        $response->assertStatus(200);
        $response->assertDontSee('Management Portal');
        $response->assertDontSee('ri-shield-user-line');
        $response->assertDontSee('role="switch"', false);
        $response->assertDontSee('btn-primary w-100 py-2.5 rounded-pill');
        $response->assertSee('auth-card');
        $response->assertSee('aria-hidden="true"', false);
    }
}
