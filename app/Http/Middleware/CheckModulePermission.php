<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckModulePermission
{
    /** @var list<string> */
    private const SKIP_ROUTES = [
        'logout',
    ];

    /** @var array<string, array{0: string, 1: string}> */
    private const ROUTE_MAP = [
        'dashboard' => ['dashboard', 'view'],
        'assistant.chat' => ['dashboard', 'view'],
        'assistant.confirm' => ['dashboard', 'view'],
        'assistant.cancel' => ['dashboard', 'view'],
        'invoices.issue' => ['invoices', 'update'],
        'invoices.pay' => ['invoices', 'update'],
        'invoices.print' => ['invoices', 'view'],
        'settings.company' => ['settings', 'view'],
        'settings.company.update' => ['settings', 'update'],
        'reports.party-ledger' => ['reports', 'view'],
        'reports.party-ledger.accounts' => ['reports', 'view'],
        'reports.cash-register' => ['reports', 'view'],
        'reports.journal-report' => ['reports', 'view'],
        'reports.show' => ['reports', 'view'],
        'cash-vouchers.print' => ['cash-vouchers', 'view'],
        'purchase-orders.print' => ['purchase-orders', 'view'],
        'purchase-returns.print' => ['purchase-returns', 'view'],
        'sales-returns.print' => ['sales-returns', 'view'],
        'bank-accounts.balance' => ['bank-accounts', 'view'],
    ];

    /** @var array<string, string> */
    private const ACTION_MAP = [
        'index' => 'view',
        'show' => 'view',
        'create' => 'create',
        'store' => 'create',
        'edit' => 'update',
        'update' => 'update',
        'destroy' => 'delete',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || $user->isAdmin()) {
            return $next($request);
        }

        $routeName = $request->route()?->getName();

        if (! $routeName || in_array($routeName, self::SKIP_ROUTES, true)) {
            return $next($request);
        }

        [$module, $action] = $this->resolve($routeName);

        if ($module === null || $action === null) {
            return $next($request);
        }

        if ($user->canModule($module, $action)) {
            return $next($request);
        }

        abort(403, 'You do not have permission to access this page.');
    }

    /**
     * @return array{0: ?string, 1: ?string}
     */
    private function resolve(string $routeName): array
    {
        if (isset(self::ROUTE_MAP[$routeName])) {
            return self::ROUTE_MAP[$routeName];
        }

        $parts = explode('.', $routeName);

        if (count($parts) < 2) {
            return [null, null];
        }

        $module = $parts[0];
        $routeAction = $parts[1];

        if ($module === 'reports') {
            return ['reports', 'view'];
        }
        if ($module === 'settings') {
            return ['settings', $routeAction === 'update' || $routeAction === 'select' ? 'update' : 'view'];
        }

        return [$module, self::ACTION_MAP[$routeAction] ?? 'view'];
    }
}
