<?php

namespace Ralkage\LinkedAccounts\Api\Controller;

use Flarum\Api\Controller\AbstractListController;
use Flarum\Http\RequestUtil;
use Psr\Http\Message\ServerRequestInterface;
use Tobscure\JsonApi\Document;
use Ralkage\LinkedAccounts\Api\Serializer\LinkedAccountSerializer;
use Ralkage\LinkedAccounts\Model\LinkedAccount;

class ListLinkedAccountsController extends AbstractListController
{
    public $serializer = LinkedAccountSerializer::class;

    public $include = ['childUser', 'parentUser'];

    protected function data(ServerRequestInterface $request, Document $document)
    {
        $actor = RequestUtil::getActor($request);
        $actor->assertRegistered();

        // Child accounts don't manage linked accounts — they can only
        // switch back to their parent via the session-based revert flow.
        if ($actor->is_linked_child) {
            return collect();
        }

        return LinkedAccount::where('parent_user_id', $actor->id)
            ->with(['childUser', 'parentUser'])
            ->orderBy('created_at', 'desc')
            ->get();
    }
}
