<?php

namespace App\Services;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Request;

class PermissionService
{
    public const ROLE_SUPER_ADMIN = 'super_admin';
    public const ROLE_ADMIN = 'admin';
    public const ROLE_MANAGER = 'manager';
    public const ROLE_OPERATOR = 'operator';
    public const ROLE_VIEWER = 'viewer';

    protected const PERMISSIONS = [
        self::ROLE_SUPER_ADMIN => ['*'],
        self::ROLE_ADMIN => [
            'locale.view', 'locale.create', 'locale.update', 'locale.delete',
            'channel.view', 'channel.create', 'channel.update', 'channel.delete',
            'currency.view', 'currency.create', 'currency.update', 'currency.delete',
            'exchange_rate.view', 'exchange_rate.create', 'exchange_rate.update', 'exchange_rate.delete',
            'exchange_rate.activate', 'exchange_rate.deactivate',
            'exchange_rate.convert',
        ],
        self::ROLE_MANAGER => [
            'locale.view', 'locale.create', 'locale.update',
            'channel.view', 'channel.create', 'channel.update',
            'currency.view', 'currency.create', 'currency.update',
            'exchange_rate.view', 'exchange_rate.create', 'exchange_rate.update',
            'exchange_rate.activate', 'exchange_rate.deactivate',
            'exchange_rate.convert',
        ],
        self::ROLE_OPERATOR => [
            'locale.view',
            'channel.view',
            'currency.view',
            'exchange_rate.view',
            'exchange_rate.convert',
            'exchange_rate.update',
        ],
        self::ROLE_VIEWER => [
            'locale.view',
            'channel.view',
            'currency.view',
            'exchange_rate.view',
            'exchange_rate.convert',
        ],
    ];

    protected ?string $currentRole = null;
    protected ?string $currentUserId = null;

    public function getCurrentRole(): string
    {
        if ($this->currentRole !== null) {
            return $this->currentRole;
        }

        $role = Request::header('X-User-Role')
            ?: Request::input('user_role')
            ?: Config::get('app.default_role', self::ROLE_VIEWER);

        $this->currentRole = $this->normalizeRole($role);
        return $this->currentRole;
    }

    public function setCurrentRole(string $role): void
    {
        $this->currentRole = $this->normalizeRole($role);
    }

    public function getCurrentUserId(): ?string
    {
        if ($this->currentUserId !== null) {
            return $this->currentUserId;
        }

        $this->currentUserId = Request::header('X-User-Id') ?: Request::input('user_id');
        return $this->currentUserId;
    }

    public function setCurrentUserId(?string $userId): void
    {
        $this->currentUserId = $userId;
    }

    public function hasRole(string $role): bool
    {
        return $this->getCurrentRole() === $this->normalizeRole($role);
    }

    public function hasAnyRole(array $roles): bool
    {
        $current = $this->getCurrentRole();
        foreach ($roles as $role) {
            if ($current === $this->normalizeRole($role)) {
                return true;
            }
        }
        return false;
    }

    public function hasPermission(string $permission): bool
    {
        $role = $this->getCurrentRole();
        $permissions = self::PERMISSIONS[$role] ?? [];

        if (in_array('*', $permissions, true)) {
            return true;
        }

        if (in_array($permission, $permissions, true)) {
            return true;
        }

        $parts = explode('.', $permission);
        if (count($parts) >= 2) {
            $wildcard = $parts[0] . '.*';
            if (in_array($wildcard, $permissions, true)) {
                return true;
            }
        }

        return false;
    }

    public function hasAnyPermission(array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if ($this->hasPermission($permission)) {
                return true;
            }
        }
        return false;
    }

    public function requirePermission(string $permission): void
    {
        if (!$this->hasPermission($permission)) {
            throw new \App\Exceptions\ForbiddenException(
                'Permission denied: ' . $permission,
                [
                    'required_permission' => $permission,
                    'current_role' => $this->getCurrentRole(),
                    'user_id' => $this->getCurrentUserId(),
                ]
            );
        }
    }

    public function requireAnyPermission(array $permissions): void
    {
        if (!$this->hasAnyPermission($permissions)) {
            throw new \App\Exceptions\ForbiddenException(
                'Permission denied',
                [
                    'required_permissions' => $permissions,
                    'current_role' => $this->getCurrentRole(),
                    'user_id' => $this->getCurrentUserId(),
                ]
            );
        }
    }

    public function requireRole(string $role): void
    {
        if (!$this->hasRole($role)) {
            throw new \App\Exceptions\ForbiddenException(
                'Role required: ' . $role,
                [
                    'required_role' => $role,
                    'current_role' => $this->getCurrentRole(),
                    'user_id' => $this->getCurrentUserId(),
                ]
            );
        }
    }

    public function requireAnyRole(array $roles): void
    {
        if (!$this->hasAnyRole($roles)) {
            throw new \App\Exceptions\ForbiddenException(
                'One of the required roles is missing',
                [
                    'required_roles' => $roles,
                    'current_role' => $this->getCurrentRole(),
                    'user_id' => $this->getCurrentUserId(),
                ]
            );
        }
    }

    public function getAllPermissions(?string $role = null): array
    {
        $r = $role ? $this->normalizeRole($role) : $this->getCurrentRole();
        return self::PERMISSIONS[$r] ?? [];
    }

    public function getAllRoles(): array
    {
        return [
            self::ROLE_SUPER_ADMIN => 'Super Admin',
            self::ROLE_ADMIN => 'Admin',
            self::ROLE_MANAGER => 'Manager',
            self::ROLE_OPERATOR => 'Operator',
            self::ROLE_VIEWER => 'Viewer',
        ];
    }

    protected function normalizeRole(string $role): string
    {
        $role = strtolower(trim($role));
        return array_key_exists($role, self::PERMISSIONS) ? $role : self::ROLE_VIEWER;
    }
}
