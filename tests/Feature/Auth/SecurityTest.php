<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Tests\TestCase;

class SecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_from_protected_home(): void
    {
        $this->get('/')->assertRedirect('/login');
    }

    public function test_login_is_throttled_after_repeated_failures(): void
    {
        Event::fake([Lockout::class]);
        $user = User::factory()->create();

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->post('/login', ['email' => $user->email, 'password' => 'wrong-password']);
        }

        $this->post('/login', ['email' => $user->email, 'password' => 'wrong-password'])
            ->assertSessionHasErrors('email');

        Event::assertDispatched(Lockout::class);
        $this->assertGuest();
    }

    public function test_password_reset_link_can_be_requested_for_known_account(): void
    {
        Notification::fake();
        $user = User::factory()->create();

        $this->post('/forgot-password', ['email' => $user->email])->assertSessionHas('status');

        Notification::assertSentTo($user, ResetPassword::class);
    }

    public function test_password_reset_request_does_not_enumerate_unknown_account(): void
    {
        $this->post('/forgot-password', ['email' => 'unknown@example.test'])
            ->assertSessionHas('status')
            ->assertSessionHasNoErrors();
    }

    public function test_password_can_be_reset_with_valid_token(): void
    {
        $user = User::factory()->create();
        $token = Password::createToken($user);

        $this->post('/reset-password', [
            'token' => $token,
            'email' => $user->email,
            'password' => 'new-secure-password',
            'password_confirmation' => 'new-secure-password',
        ])->assertRedirect('/login');

        $this->assertTrue(password_verify('new-secure-password', $user->fresh()->password));
    }

    public function test_invalid_password_reset_token_is_rejected(): void
    {
        $user = User::factory()->create();

        $this->post('/reset-password', [
            'token' => Str::random(64),
            'email' => $user->email,
            'password' => 'new-secure-password',
            'password_confirmation' => 'new-secure-password',
        ])->assertSessionHasErrors('email');
    }
}
