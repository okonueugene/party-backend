<?php

namespace App\Enums;

enum Permission: string
{
    // User Management
    case USERS_VIEW = 'users.view';
    case USERS_CREATE = 'users.create';
    case USERS_EDIT = 'users.edit';
    case USERS_DELETE = 'users.delete';
    case USERS_SUSPEND = 'users.suspend';

    // Post Management
    case POSTS_VIEW = 'posts.view';
    case POSTS_CREATE = 'posts.create';
    case POSTS_EDIT = 'posts.edit';
    case POSTS_DELETE = 'posts.delete';
    case POSTS_RESTORE = 'posts.restore';

    // Moderation
    case MODERATION_VIEW = 'moderation.view';
    case MODERATION_REVIEW = 'moderation.review';
    case MODERATION_ACTION = 'moderation.action';

    // Analytics
    case ANALYTICS_VIEW = 'analytics.view';
    case ANALYTICS_EXPORT = 'analytics.export';

    // Settings
    case SETTINGS_VIEW = 'settings.view';
    case SETTINGS_EDIT = 'settings.edit';

    // Admin Management
    case ADMINS_VIEW = 'admins.view';
    case ADMINS_CREATE = 'admins.create';
    case ADMINS_EDIT = 'admins.edit';
    case ADMINS_DELETE = 'admins.delete';

    /**
     * Get permission label
     */
    public function label(): string
    {
        return str_replace('.', ' ', ucwords($this->value, '.'));
    }

    /**
     * Get permission category
     */
    public function category(): string
    {
        return explode('.', $this->value)[0];
    }

    /**
     * Group permissions by category
     */
    public static function grouped(): array
    {
        $grouped = [];
        foreach (self::cases() as $permission) {
            $category = $permission->category();
            $grouped[$category][] = $permission;
        }
        return $grouped;
    }
}