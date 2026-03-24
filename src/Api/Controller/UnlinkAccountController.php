<?php

namespace Ralkage\LinkedAccounts\Api\Controller;

use Flarum\Api\Controller\AbstractDeleteController;
use Flarum\Http\RequestUtil;
use Illuminate\Support\Arr;
use Psr\Http\Message\ServerRequestInterface;
use Ralkage\LinkedAccounts\Model\LinkedAccount;
use Ralkage\LinkedAccounts\Service\LinkedAccountService;

class UnlinkAccountController extends AbstractDeleteController
{
    protected $service;

    public function __construct(LinkedAccountService $service)
    {
        $this->service = $service;
    }

    protected function delete(ServerRequestInterface $request)
    {
        $actor = RequestUtil::getActor($request);
        $actor->assertRegistered();

        $id = Arr::get($request->getQueryParams(), 'id');

        $link = LinkedAccount::findOrFail($id);

        // Log before deleting
        $this->service->writeLog(
            $link->parent_user_id,
            $link->child_user_id,
            'unlink',
            $request->getServerParams()['REMOTE_ADDR'] ?? null
        );

        $this->service->unlinkAccount($actor, (int) $id);
    }
}
