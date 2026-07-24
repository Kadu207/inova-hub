<?php

namespace Tests\Feature;

use App\Models\Membership;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register_login_and_logout(): void
    {
        $this->get('/register')->assertOk();

        $register = $this->post('/register', [
            '_token' => session()->token(),
            'name' => 'Carlos',
            'email' => 'carlos@example.com',
            'password' => 'Password1!',
            'password_confirmation' => 'Password1!',
            'organization_name' => 'Org Carlos',
        ]);

        $register->assertRedirect('/hub');
        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', ['email' => 'carlos@example.com']);
        $this->assertDatabaseHas('organizations', ['name' => 'Org Carlos']);
        $this->assertSame(1, Membership::query()->withoutGlobalScopes()->count());

        $this->post('/logout', ['_token' => session()->token()])->assertRedirect('/login');
        $this->assertGuest();

        $this->get('/login')->assertOk();

        $login = $this->post('/login', [
            '_token' => session()->token(),
            'email' => 'carlos@example.com',
            'password' => 'Password1!',
        ]);

        $login->assertRedirect('/hub');
        $this->assertAuthenticated();
        $this->get('/hub')->assertOk()->assertSee('Carlos');
    }

    public function test_login_is_rate_limited_after_five_failures(): void
    {
        User::factory()->create([
            'email' => 'limit@example.com',
            'password' => 'Password1!',
        ]);

        $this->get('/login')->assertOk();

        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', [
                '_token' => session()->token(),
                'email' => 'limit@example.com',
                'password' => 'wrong-password',
            ])->assertSessionHasErrors('email');
        }

        $this->post('/login', [
            '_token' => session()->token(),
            'email' => 'limit@example.com',
            'password' => 'wrong-password',
        ])->assertSessionHasErrors('email');
    }

    public function test_guest_is_redirected_from_hub(): void
    {
        $this->get('/hub')->assertRedirect('/login');
    }
}
