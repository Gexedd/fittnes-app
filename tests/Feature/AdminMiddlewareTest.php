<?php

use App\Http\Middleware\EnsureIsAdmin;
use App\Models\User;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

it('deja pasar al administrador', function () {
    $admin = User::factory()->admin()->create();
    $request = Request::create('/admin');
    $request->setUserResolver(fn () => $admin);

    $response = (new EnsureIsAdmin)->handle($request, fn () => response('ok'));

    expect($response->getContent())->toBe('ok');
});

it('aborta con 403 al suscriptor', function () {
    $user = User::factory()->create();
    $request = Request::create('/admin');
    $request->setUserResolver(fn () => $user);

    try {
        (new EnsureIsAdmin)->handle($request, fn () => response('ok'));
        $this->fail('Se esperaba un HttpException con estado 403.');
    } catch (HttpException $exception) {
        expect($exception->getStatusCode())->toBe(403);
    }
});
