<?php

return [

    'table_names' => [
        'permissions' => 'permissions',
        'roles' => 'roles',
        'model_has_permissions' => 'model_has_permissions',
        'model_has_roles' => 'model_has_roles',
        'role_has_permissions' => 'role_has_permissions',
    ],

    'column_names' => [
        'model_morph_key' => 'model_id',
        'role_pivot_key' => 'role_id',
        'permission_pivot_key' => 'permission_id',
        'team_foreign_key' => 'team_id',
    ],

    'teams' => false,

    'cache' => [
        'store' => 'default',
        'key' => 'spatie.permission.cache',
        'expiration_time' => \DateInterval::createFromDateString('24 hours'),
    ],

    'register_permission_checker_middleware' => true,

    'register_route_checker_middleware' => true,

    'register_panel' => false,

    'display_permission_in_exception' => false,

    'display_role_in_exception' => false,

    'enable_wildcard_permission' => false,

    'cache_expiration_time' => \DateInterval::createFromDateString('24 hours'),

    'default_guard' => 'web',

    'interactive' => true,

    'testing' => false,

    'guard_name' => 'web',

];
