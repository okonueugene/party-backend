<?php

namespace App\Enums;

enum AdminRole: string
{
    case SUPER_ADMIN = 'super_admin';
    case ADMIN = 'admin';
    case MODERATOR = 'moderator';
    case CONTENT_MANAGER = 'content_manager';
    case ANALYST = 'analyst';

    /**
     * Get role display name
     */
    public function label(): string
    {
        return match($this) {
            self::SUPER_ADMIN => 'Super Admin',
            self::ADMIN => 'Admin',
            self::MODERATOR => 'Moderator',
            self::CONTENT_MANAGER => 'Content Manager',
            self::ANALYST => 'Analyst',
        };
    }

    /**
     * Get role description
     */
    public function description(): string
    {
        return match($this) {
            self::SUPER_ADMIN => 'Full system access with all permissions',
            self::ADMIN => 'Manage users, posts, and moderate content',
            self::MODERATOR => 'Review and moderate flagged content',
            self::CONTENT_MANAGER => 'Manage posts and user content',
            self::ANALYST => 'View analytics and generate reports',
        };
    }

    /**
     * Get default permissions for this role
     */
    public function defaultPermissions(): array
    {
        return match($this) {
            self::SUPER_ADMIN => [
                'users.view', 'users.create', 'users.edit', 'users.delete', 'users.suspend',
                'posts.view', 'posts.edit', 'posts.delete', 'posts.restore',
                'moderation.view', 'moderation.review', 'moderation.action',
                'analytics.view', 'analytics.export',
                'settings.view', 'settings.edit',
                'admins.view', 'admins.create', 'admins.edit', 'admins.delete',
            ],
            self::ADMIN => [
                'users.view', 'users.edit', 'users.suspend',
                'posts.view', 'posts.edit', 'posts.delete',
                'moderation.view', 'moderation.review', 'moderation.action',
                'analytics.view', 'analytics.export',
            ],
            self::MODERATOR => [
                'users.view',
                'posts.view', 'posts.delete',
                'moderation.view', 'moderation.review', 'moderation.action',
            ],
            self::CONTENT_MANAGER => [
                'users.view',
                'posts.view', 'posts.edit', 'posts.delete',
                'analytics.view',
            ],
            self::ANALYST => [
                'users.view',
                'posts.view',
                'analytics.view', 'analytics.export',
            ],
        };
    }

    /**
     * Get all available roles
     */
    public static function all(): array
    {
        return [
            self::SUPER_ADMIN,
            self::ADMIN,
            self::MODERATOR,
            self::CONTENT_MANAGER,
            self::ANALYST,
        ];
    }

    /**
     * Get all role values
     */
    public static function values(): array
    {
        return array_map(fn($case) => $case->value, self::cases());
    }
}