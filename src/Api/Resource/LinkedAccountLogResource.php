<?php

namespace Ralkage\LinkedAccounts\Api\Resource;

use Flarum\Api\Context;
use Flarum\Api\Endpoint;
use Flarum\Api\Resource\AbstractDatabaseResource;
use Flarum\Api\Schema;
use Flarum\Api\Sort\SortColumn;
use Illuminate\Database\Eloquent\Builder;
use Ralkage\LinkedAccounts\Model\LinkedAccountLog;
use Tobyz\JsonApiServer\Context as OriginalContext;

/**
 * @extends AbstractDatabaseResource<LinkedAccountLog>
 */
class LinkedAccountLogResource extends AbstractDatabaseResource
{
    public function type(): string
    {
        return 'linked-account-logs';
    }

    public function model(): string
    {
        return LinkedAccountLog::class;
    }

    public function scope(Builder $query, OriginalContext $context): void
    {
        // Only admins can view logs
        $actor = $context->getActor();
        if (! $actor->isAdmin()) {
            $query->whereRaw('0 = 1');
        }
    }

    public function endpoints(): array
    {
        return [
            Endpoint\Index::make()
                ->authenticated()
                ->visible(fn (Context $context) => $context->getActor()->isAdmin())
                ->defaultInclude(['parentUser', 'childUser'])
                ->defaultSort('-createdAt')
                ->paginate(50),
        ];
    }

    public function fields(): array
    {
        return [
            Schema\Number::make('parentUserId')
                ->get(fn (LinkedAccountLog $log) => (int) $log->parent_user_id),

            Schema\Number::make('childUserId')
                ->get(fn (LinkedAccountLog $log) => (int) $log->child_user_id),

            Schema\Str::make('action')
                ->get(fn (LinkedAccountLog $log) => $log->action),

            Schema\Str::make('ipAddress')
                ->get(fn (LinkedAccountLog $log) => $log->ip_address),

            Schema\DateTime::make('createdAt'),

            Schema\Relationship\ToOne::make('parentUser')
                ->type('users')
                ->includable(),

            Schema\Relationship\ToOne::make('childUser')
                ->type('users')
                ->includable(),
        ];
    }

    public function sorts(): array
    {
        return [
            SortColumn::make('createdAt'),
        ];
    }
}
