<?php

/**
 * @author OOI JIAN XIN
 *
 * The login page must give new users an obvious path to registration, and the
 * existing registration route must remain accessible and functional.
 */

namespace Tests\Feature;

use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoginRegistrationUiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Registration resolves the Student role via UserAccountFactory.
        $this->seed(RoleSeeder::class);
    }

    public function test_login_page_shows_a_register_button(): void
    {
        $response = $this->get('/login');

        $response
            ->assertOk()
            ->assertSee('New to SkillBridge?')
            ->assertSee('Create a free account')
            ->assertSee(route('register'));
    }

    public function test_register_page_is_accessible(): void
    {
        $this->get('/register')
            ->assertOk()
            ->assertSee('First Name')
            ->assertSee('Register');
    }

    public function test_new_user_can_register_from_scratch(): void
    {
        $response = $this->post('/register', [
            'first_name' => 'New',
            'last_name' => 'Student',
            'email' => 'brand-new@test.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertRedirect(route('dashboard', absolute: false));
        $this->assertAuthenticated();

        $this->assertDatabaseHas('users', [
            'email' => 'brand-new@test.com',
            'status' => 'Active',
        ]);
    }
}
