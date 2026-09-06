<?php

namespace Tests\Feature;

use App\Services\Concerns\InternalHttp;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class InternalHttpTest extends TestCase
{
    public function test_only_original_session_cookie_and_csrf_token_are_forwarded(): void
    {
        config(['app.url' => 'https://skillbridge.test']);
        $session = app('session')->driver();
        $session->start();
        $name = $session->getName();
        $request = Request::create('https://skillbridge.test/admin/users/1/edit', 'GET', [], [], [], [
            'HTTP_COOKIE' => 'unrelated=private; '.$name.'=original%2Bencrypted%3D; other=private',
        ]);
        $request->cookies->set($name, 'decrypted-session-id');
        $request->setLaravelSession($session);
        $this->app->instance('request', $request);
        Http::fake();

        $this->client()->get('https://skillbridge.test/api/users/1/profile');

        Http::assertSent(fn ($sent) => $sent->hasHeader('Cookie', $name.'=original%2Bencrypted%3D')
            && $sent->hasHeader('X-CSRF-TOKEN', $session->token()));
    }

    public function test_credentials_are_not_sent_to_another_origin(): void
    {
        config(['app.url' => 'https://skillbridge.test']);
        Http::fake();
        try {
            $this->client()->get('https://other.test/api/users/1/profile');
            $this->fail('Expected the different origin to be rejected.');
        } catch (\LogicException $exception) {
            $this->assertStringContainsString('configured application origin', $exception->getMessage());
        }
        Http::assertNothingSent();
    }

    public function test_missing_cookie_does_not_manufacture_credentials(): void
    {
        config(['app.url' => 'https://skillbridge.test']);
        Http::fake();
        $this->client()->get('https://skillbridge.test/api/users/1/profile');
        Http::assertSent(fn ($sent) => ! $sent->hasHeader('Cookie') && ! $sent->hasHeader('X-CSRF-TOKEN'));
    }

    private function client(): PendingRequest
    {
        return (new class
        {
            use InternalHttp;

            public function client(): PendingRequest
            {
                return $this->internalHttp();
            }
        })->client();
    }
}
