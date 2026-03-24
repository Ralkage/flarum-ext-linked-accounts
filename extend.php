<?php

namespace Ralkage\LinkedAccounts;

use Flarum\Api\Context;
use Flarum\Api\Resource;
use Flarum\Api\Schema;
use Flarum\Extend;

return [
    // Admin frontend
    (new Extend\Frontend('admin'))
        ->js(__DIR__.'/js/dist/admin.js')
        ->css(__DIR__.'/less/admin.less'),

    // Forum frontend
    (new Extend\Frontend('forum'))
        ->js(__DIR__.'/js/dist/forum.js')
        ->css(__DIR__.'/less/forum.less')
        ->route('/linked-accounts', 'linked-accounts'),

    // Localization
    new Extend\Locales(__DIR__.'/locale'),

    // Settings defaults
    (new Extend\Settings())
        ->default('ralkage-linked-accounts.max_accounts', 5)
        ->default('ralkage-linked-accounts.log_retention_days', 30)
        ->serializeToForum('linkedAccountsMaxAccounts', 'ralkage-linked-accounts.max_accounts', 'intval')
        ->serializeToForum('linkedAccountsLogRetentionDays', 'ralkage-linked-accounts.log_retention_days', 'intval'),

    // API Resources (Flarum 2.x pattern — replaces old serializer + controller routes)
    new Extend\ApiResource(Api\Resource\LinkedAccountResource::class),
    new Extend\ApiResource(Api\Resource\LinkedAccountLogResource::class),

    // Custom API routes that don't fit the resource CRUD pattern
    (new Extend\Routes('api'))
        ->post('/linked-accounts/create', 'linked-accounts.create', Api\Controller\CreateLinkedAccountController::class)
        ->post('/linked-accounts/link', 'linked-accounts.link', Api\Controller\LinkExistingAccountController::class)
        ->delete('/linked-account-logs', 'linked-account-logs.clear', Api\Controller\ClearLinkedAccountLogsController::class),

    // Forum routes (session-based account switching)
    (new Extend\Routes('forum'))
        ->post('/linked-accounts/switch', 'linked-accounts.switch', Controller\SwitchAccountController::class)
        ->post('/linked-accounts/revert', 'linked-accounts.revert', Controller\RevertAccountController::class),

    // Middleware to expose linked account session state
    (new Extend\Middleware('forum'))
        ->add(Middleware\LinkedAccountMiddleware::class),

    // Add linked account fields to the User API resource
    (new Extend\ApiResource(Resource\UserResource::class))
        ->fields(fn () => [
            Schema\Boolean::make('isLinkedChild')
                ->get(fn ($user) => (bool) $user->is_linked_child),

            Schema\Number::make('linkedParentId')
                ->get(fn ($user) => $user->linked_parent_id),

            Schema\Number::make('linkedChildrenCount')
                ->get(fn ($user) => (int) $user->linked_children_count),

            Schema\Boolean::make('canUseLinkedAccounts')
                ->get(function ($user, Context $context) {
                    if ($context->getActor()->id === $user->id) {
                        return $context->getActor()->hasPermission('linkedAccounts.use');
                    }
                    return null;
                }),

            Schema\Boolean::make('canCreateLinkedAccounts')
                ->get(function ($user, Context $context) {
                    if ($context->getActor()->id === $user->id) {
                        return $context->getActor()->hasPermission('linkedAccounts.create');
                    }
                    return null;
                }),
        ]),

    // Expose switched-account state to the forum frontend
    (new Extend\ApiResource(Resource\ForumResource::class))
        ->fields(fn () => [
            Schema\Number::make('linkedAccountParentId')
                ->get(fn () => Middleware\LinkedAccountMiddleware::$parentUserId),

            Schema\Str::make('linkedAccountParentName')
                ->get(fn () => Middleware\LinkedAccountMiddleware::$parentUserName),
        ]),

    // "Post as" — swap author on discussion/post creation
    (new Extend\Event())
        ->subscribe(Listener\PostAsListener::class),

    // Rate-limit account linking to prevent brute-force password guessing
    (new Extend\ThrottleApi())
        ->set('linkedAccountsLink', Throttler\LinkAccountThrottler::class),
];
