<?php

use App\Http\Middleware\CrmApiToken;
use App\Http\Middleware\EnsureUserIsAdmin;
use App\Http\Middleware\SetLocale;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        // API của CRM (nhóm "api": không session, không CSRF) — tạm nằm trong viger_web
        then: fn () => Route::middleware('api')->group(__DIR__.'/../routes/crm_api.php'),
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'admin' => EnsureUserIsAdmin::class,
            'crm.token' => CrmApiToken::class,
        ]);

        // Khách chưa đăng nhập vào CRM => trang login của CRM
        $middleware->redirectGuestsTo(
            fn ($request) => $request->routeIs('crm.*') ? route('crm.login') : route('login')
        );

        $middleware->web(append: [
            SetLocale::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
