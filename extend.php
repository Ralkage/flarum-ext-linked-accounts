<?php

namespace Ralkage\LinkedAccounts;

use Flarum\Extend;
use Flarum\Api\Serializer\BasicUserSerializer;
use Flarum\Api\Serializer\ForumSerializer;

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

    // API routes
    (new Extend\Routes('api'))
        ->get('/linked-accounts', 'linked-accounts.index', Api\Controller\ListLinkedAccountsController::class)
        ->post('/linked-accounts/create', 'linked-accounts.create', Api\Controller\CreateLinkedAccountController::class)
        ->post('/linked-accounts/link', 'linked-accounts.link', Api\Controller\LinkExistingAccountController::class)
        ->delete('/linked-accounts/{id}', 'linked-accounts.delete', Api\Controller\UnlinkAccountController::class)
        ->get('/linked-account-logs', 'linked-account-logs.index', Api\Controller\ListLinkedAccountLogsController::class)
        ->delete('/linked-account-logs', 'linked-account-logs.clear', Api\Controller\ClearLinkedAccountLogsController::class),

    // Forum routes (session-based account switching)
    (new Extend\Routes('forum'))
        ->post('/linked-accounts/switch', 'linked-accounts.switch', Controller\SwitchAccountController::class)
        ->post('/linked-accounts/revert', 'linked-accounts.revert', Controller\RevertAccountController::class),

    // Middleware to expose linked account session state
    (new Extend\Middleware('forum'))
        ->add(Middleware\LinkedAccountMiddleware::class),

    // Add linked account attributes to user API responses
    (new Extend\ApiSerializer(BasicUserSerializer::class))
        ->attributes(function ($serializer, $user, $attributes) {
            $attributes['isLinkedChild'] = (bool) $user->is_linked_child;
            $attributes['linkedParentId'] = $user->linked_parent_id;
            $attributes['linkedChildrenCount'] = (int) $user->linked_children_count;

            // Only expose permission info for the current user
            if ($serializer->getActor()->id === $user->id) {
                $attributes['canUseLinkedAccounts'] = $serializer->getActor()->hasPermission('linkedAccounts.use');
                $attributes['canCreateLinkedAccounts'] = $serializer->getActor()->hasPermission('linkedAccounts.create');
            }

            return $attributes;
        }),

    // Expose switched-account state to the forum frontend
    (new Extend\ApiSerializer(ForumSerializer::class))
        ->attributes(function ($serializer, $model, $attributes) {
            $attributes['linkedAccountParentId'] = Middleware\LinkedAccountMiddleware::$parentUserId;
            $attributes['linkedAccountParentName'] = Middleware\LinkedAccountMiddleware::$parentUserName;
            return $attributes;
        }),

    // "Post as" — swap author on discussion/post creation
    (new Extend\Event())
        ->subscribe(Listener\PostAsListener::class),
];
