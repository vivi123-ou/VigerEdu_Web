<?php

use App\Http\Middleware\EnsureAdminAccess;
use App\Http\Middleware\EnsureMenuAccess;
use App\Http\Middleware\GuardAgainstAbuse;
use App\Http\Middleware\NoIndex;
use App\Http\Middleware\SecurityHeaders;
use App\Modules\Api\Http\Middleware\AuthenticateApiClient;
use App\Modules\CrmSync\Console\RetryCrm;
use App\Modules\CrmSync\Console\ShowCrmPayload;
use App\Modules\System\Console\PruneOldRecords;
use App\Modules\System\Console\SecurityAudit;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Auth\Middleware\AuthenticatesRequests;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    // Lệnh của các module (app/Modules/*/Console)
    ->withCommands([
        RetryCrm::class,
        ShowCrmPayload::class,
        PruneOldRecords::class,
        SecurityAudit::class,
    ])
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'admin' => EnsureAdminAccess::class,
            'menu' => EnsureMenuAccess::class,
            'noindex' => NoIndex::class,
            'api.token' => AuthenticateApiClient::class,
        ]);

        // Chống truy cập bất thường (chặn IP, đường dò, quá nhiều yêu cầu) và header bảo mật cho mọi trang.
        // Đặt sau TrustProxies để lấy đúng IP thật khi chạy sau Cloudflare (khai báo proxy tin cậy qua TRUSTED_PROXIES).
        $middleware->append([GuardAgainstAbuse::class, SecurityHeaders::class]);

        // Tài khoản bị khóa giữa phiên: đăng xuất và đưa về trang đăng nhập trước khi Filament kiểm tra quyền (nếu không sẽ hiện 403).
        $middleware->prependToPriorityList(before: AuthenticatesRequests::class, prepend: EnsureAdminAccess::class);

        // Đăng nhập quản trị do Filament làm (app/Providers/Filament/AdminPanelProvider.php)
        $middleware->redirectGuestsTo(fn () => route('filament.admin.auth.login'));
        $middleware->redirectUsersTo(fn () => route('filament.admin.pages.dashboard'));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        // Trang quản trị không có quyền: báo "không tìm thấy" (404) thay vì "không có quyền" (403),
        // để tư vấn viên, nhân viên không biết trang đó tồn tại (Vân chốt 17/11).
        $isAdminRequest = fn (): bool => request()->is('admin', 'admin/*');
        $exceptions->map(fn (AuthorizationException $e) => $isAdminRequest() ? new NotFoundHttpException(previous: $e) : $e);
        $exceptions->map(fn (HttpException $e) => $e->getStatusCode() === 403 && $isAdminRequest() ? new NotFoundHttpException(previous: $e) : $e);
    })->create();
