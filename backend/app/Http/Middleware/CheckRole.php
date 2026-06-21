<?php

namespace App\Http\Middleware;

use Closure;
use App\Services\PermissionService;
use App\Exceptions\ForbiddenException;

class CheckRole
{
    protected PermissionService $permissionService;

    public function __construct(PermissionService $permissionService)
    {
        $this->permissionService = $permissionService;
    }

    public function handle($request, Closure $next, ...$roles)
    {
        if (empty($roles)) {
            return $next($request);
        }

        $roleList = explode('|', $roles[0]);

        if (count($roleList) === 1) {
            $this->permissionService->requireRole($roleList[0]);
        } else {
            $this->permissionService->requireAnyRole($roleList);
        }

        return $next($request);
    }
}
