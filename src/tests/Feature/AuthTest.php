<?php

namespace Tests\Feature;

use App\Contracts\Authenticator;
use App\Models\User;
use App\Providers\AppServiceProvider;
use App\Services\Auth\DevAuthenticator;
use App\Services\Auth\LdapAuthenticator;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that booting refuses to start if APP_ENV=production and AUTH_MODE=dev.
     */
    public function test_production_with_dev_mode_is_blocked(): void
    {
        config([
            'app.env' => 'production',
            'auth.auth_mode' => 'dev',
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('FATAL CONFIGURATION ERROR: AUTH_MODE cannot be set to [dev] in production.');

        $provider = new AppServiceProvider($this->app);
        $provider->boot();
    }

    /**
     * Test login page displays the red DEV MODE banner when AUTH_MODE=dev.
     */
    public function test_login_page_renders_with_red_dev_mode_banner(): void
    {
        config(['auth.auth_mode' => 'dev']);

        $response = $this->get('/login');

        $response->assertStatus(200);
        $response->assertSee('DEV MODE ACTIVE');
        $response->assertSee('DevAuthenticator');
        $response->assertSee('secret123');
        $response->assertSee('CPF');
    }

    /**
     * Test login page does NOT display the red banner when AUTH_MODE!=dev.
     */
    public function test_dev_mode_banner_is_hidden_when_auth_mode_is_not_dev(): void
    {
        config(['auth.auth_mode' => 'ldap']);

        $response = $this->get('/login');

        $response->assertStatus(200);
        $response->assertDontSee('DEV MODE ACTIVE');
    }

    /**
     * Test that EVERY seeded CPF can log in with the fixed dev password.
     */
    public function test_every_seeded_cpf_can_log_in_with_fixed_dev_password(): void
    {
        $this->seed(UserSeeder::class);

        $activeUsers = User::where('active', true)->get();
        $this->assertGreaterThan(0, $activeUsers->count());

        $devPassword = config('auth.dev_password', 'secret123');

        // Every active seeded user can log in
        foreach ($activeUsers as $user) {
            $response = $this->post('/login', [
                'cpf' => $user->cpf,
                'password' => $devPassword,
            ]);

            $response->assertRedirect('/dashboard');
            $this->assertAuthenticatedAs($user);

            // Log out before next user
            $this->post('/logout');
            $this->assertGuest();
        }

        // Inactive seeded users cannot log in
        $inactiveUsers = User::where('active', false)->get();
        foreach ($inactiveUsers as $inactiveUser) {
            $response = $this->from('/login')->post('/login', [
                'cpf' => $inactiveUser->cpf,
                'password' => $devPassword,
            ]);

            $response->assertRedirect('/login');
            $response->assertSessionHasErrors('cpf');
            $this->assertGuest();
        }
    }

    /**
     * Test login fails with invalid password.
     */
    public function test_login_fails_with_invalid_password(): void
    {
        $this->seed(UserSeeder::class);

        $response = $this->from('/login')->post('/login', [
            'cpf' => 'ADMIN001',
            'password' => 'wrong-password-999',
        ]);

        $response->assertRedirect('/login');
        $response->assertSessionHasErrors('cpf');
        $this->assertGuest();
    }

    /**
     * Test login fails with unknown CPF.
     */
    public function test_login_fails_with_unknown_cpf(): void
    {
        $response = $this->from('/login')->post('/login', [
            'cpf' => 'NONEXISTENT',
            'password' => 'secret123',
        ]);

        $response->assertRedirect('/login');
        $response->assertSessionHasErrors('cpf');
        $this->assertGuest();
    }

    /**
     * Test that inactive user cannot log in.
     */
    public function test_inactive_user_cannot_log_in(): void
    {
        $inactiveUser = User::factory()->create([
            'cpf' => 'INACTIVE01',
            'active' => false,
        ]);

        $response = $this->from('/login')->post('/login', [
            'cpf' => 'INACTIVE01',
            'password' => 'secret123',
        ]);

        $response->assertRedirect('/login');
        $response->assertSessionHasErrors('cpf');
        $this->assertGuest();
    }

    /**
     * Test authenticated user can access dashboard and view account details.
     */
    public function test_authenticated_user_can_access_dashboard_and_logout(): void
    {
        $user = User::factory()->create([
            'cpf' => 'DASH001',
            'name' => 'Dashboard User',
            'designation' => 'Civil Engineer',
            'location' => 'Vadodara',
            'active' => true,

        ]);

        $this->actingAs($user);

        $response = $this->get('/dashboard');
        $response->assertStatus(200);
        $response->assertSee('Welcome back, Dashboard User!');
        $response->assertSee('DASH001');
        $response->assertSee('Civil Engineer');

        // Logout
        $logoutResponse = $this->post('/logout');
        $logoutResponse->assertRedirect('/login');
        $this->assertGuest();
    }

    /**
     * Test service container binding resolves Authenticator according to AUTH_MODE.
     */
    public function test_service_container_binds_authenticator_correctly(): void
    {
        // Dev mode resolution
        config(['auth.auth_mode' => 'dev']);
        $authenticator = app(Authenticator::class);
        $this->assertInstanceOf(DevAuthenticator::class, $authenticator);

        // Ldap mode resolution
        config(['auth.auth_mode' => 'ldap']);
        $ldapAuthenticator = app(Authenticator::class);
        $this->assertInstanceOf(LdapAuthenticator::class, $ldapAuthenticator);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('LDAP authentication is not implemented yet.');
        $ldapAuthenticator->authenticate('TEST01', 'password');
    }
}
