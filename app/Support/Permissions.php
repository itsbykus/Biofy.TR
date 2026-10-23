<?php

namespace App\Support;

/** Staff permission catalogue (RBAC). Each key is also registered as a Gate ability. */
final class Permissions
{
    public const ALL = [
        'admin.access' => 'Access the administration panel',
        'users.view' => 'View users and pages',
        'users.manage' => 'Suspend/reactivate users, assign packages, moderate pages',
        'users.delete' => 'Permanently delete user accounts',
        'roles.assign' => 'Assign staff roles (superadmin only)',
        'packages.manage' => 'Manage packages, feature catalogue and themes',
        'content.manage' => 'Manage CMS pages, menus, e-mail templates and announcements',
        'settings.manage' => 'Change platform settings',
        'media.manage' => 'Manage all uploaded files',
        'domains.manage' => 'Manage custom domains',
        'messages.view' => 'Read contact messages',
        'audit.view' => 'View audit logs and security events',
        'backups.manage' => 'Create, download and restore backups',
        'system.manage' => 'System health, updates and maintenance',
    ];

    /** Default permissions of the built-in moderator role. */
    public const MODERATOR = ['admin.access', 'users.view', 'users.manage', 'messages.view', 'audit.view', 'domains.manage'];
}
