<?php

namespace Ralkage\LinkedAccounts\Api\Resource;

use Flarum\Api\Context;
use Flarum\Api\Endpoint;
use Flarum\Api\Resource\AbstractDatabaseResource;
use Flarum\Api\Schema;
use Flarum\Api\Sort\SortColumn;
use Illuminate\Database\Eloquent\Builder;
use Ralkage\LinkedAccounts\Model\LinkedAccount;
use Ralkage\LinkedAccounts\Service\LinkedAccountService;
use Tobyz\JsonApiServer\Context as OriginalContext;

/**
 * @extends AbstractDatabaseResource<LinkedAccount>
 */
class LinkedAccountResource extends AbstractDatabaseResource
{
    public function __construct(
        protected LinkedAccountService $service,
    ) {
    }

    public function type(): string
    {
        return 'linked-accounts';
    }

    public function model(): string
    {
        return LinkedAccount::class;
    }

    public function scope(Builder $query, OriginalContext $context): void
    {
        $actor = $context->getActor();

        // Non-admins can only see their own linked accounts (as parent)
        if (! $actor->isAdmin() && ! $actor->hasPermission('linkedAccounts.viewAny')) {
            $query->where('parent_user_id', $actor->id);
        }
    }

    public function endpoints(): array
    {
        return [
            Endpoint\Index::make()
                ->authenticated()
                ->defaultInclude(['childUser', 'parentUser'])
                ->defaultSort('-createdAt')
                ->paginate(),

            Endpoint\Show::make()
                ->authenticated()
                ->defaultInclude(['childUser', 'parentUser']),

            Endpoint\Delete::make()
                ->authenticated()
                ->visible(function (LinkedAccount $link, Context $context) {
                    $actor = $context->getActor();

                    return $actor->isAdmin() || $actor->id === $link->parent_user_id;
                }),
        ];
    }

    public function fields(): array
    {
        return [
            Schema\Number::make('parentUserId')
                ->get(fn (LinkedAccount $link) => (int) $link->parent_user_id),

            Schema\Number::make('childUserId')
                ->get(fn (LinkedAccount $link) => (int) $link->child_user_id),

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

    public function deleting(object $model, OriginalContext $context): void
    {
        $actor = $context->getActor();

        // Log before deleting
        $this->service->writeLog(
            $model->parent_user_id,
            $model->child_user_id,
            'unlink',
            null
        );

        // Perform the unlink logic (updates child user fields and parent count)
        $this->service->unlinkAccount($actor, (int) $model->id);
    }

    /**
     * Override to prevent double-delete since unlinkAccount already deletes the record.
     */
    public function delete(object $model, OriginalContext $context): void
    {
        // Already deleted by unlinkAccount in deleting()
    }
}
