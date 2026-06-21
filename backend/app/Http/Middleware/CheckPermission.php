<?php

namespace App\Http\Middleware;

use Closure;
use App\Services\PermissionService;
use App\Exceptions\ForbiddenException;

class CheckPermission
{
    protected PermissionService $permissionService;

    public function __construct(PermissionService $permissionService)
    {
        $this->permissionService = $permissionService;
    }

    public function handle($request, Closure $next, ...$permissions)
    {
        if (empty($permissions)) {
            return $next($request);
        }

        $permissionList = explode('|', $permissions[0]);

        if (count($permissionList) === 1) {
            $this->permissionService->requirePermission($permissionList[0]);
        } else {
            $this->permissionService->requireAnyPermission($permissionList);
        }

        return $next($request);
    }
}
