<?php

namespace Tests\Unit\Services;

use App\Models\PasswordReset;
use App\Models\User;
use App\Notifications\PasswordResetNotification;
use App\Services\PasswordResetService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use SebastianBergmann\PeekAndPoke\Proxy;
use Tests\TestCase;

class PasswordResetServiceTest extends TestCase
{
    use DatabaseTransactions;

    private $user;
    private $passwordResetService;
    private $proxyNotification;
    private $resetToken;

    public function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'password' => Hash::make('123456ff')
        ]);

        $passwordReset = new PasswordReset();
        $this->passwordResetService = new PasswordResetService($passwordReset);

        Notification::fake();
        $this->passwordResetService->sendPasswordResentLink($this->user);

        Notification::assertSentTo($this->user, PasswordResetNotification::class,
        function($notification) {
           $this->proxyNotification = new Proxy($notification);
           return $notification !== null;
            }
        );

        $this->resetToken = $this->passwordResetService->getResetToken($this->proxyNotification->password_token);
    }

    /**
     * @test
     */
    public function getResetCodeIsAlphaNumericString()
    {
        $token = $this->passwordResetService->getResetCode();
        $this->assertTrue(ctype_alnum($token));
        $this->assertEquals(strlen($token), 6);
    }

    /**
     * @test
     */
    public function sendPasswordResentLink()
    {
        Notification::fake();
        $this->passwordResetService->sendPasswordResentLink($this->user);
        Notification::assertSentTo($this->user, PasswordResetNotification::class);
    }

    /**
     * @test
     */
    public function getResetIdentifierCode()
    {
        $newToken = $this->passwordResetService->getResetIdentifierCode($this->resetToken);

        $this->assertTrue(ctype_alnum($newToken));
        $this->assertEquals(strlen($newToken), 6);
    }

    /**
     * @test
     */
    public function getResetToken()
    {
        $this->assertInstanceOf(PasswordReset::class,$this->resetToken);
    }

    /**
     * @test
     */
    public function expiresTokenNow()
    {
        $this->assertTrue($this->passwordResetService->expiresTokenNow($this->resetToken));
        $token = $this->resetToken->fresh();
        sleep(1);
        $this->assertLessThan(Carbon::now()->toArray()['formatted'], $token->expires_at);
    }
}