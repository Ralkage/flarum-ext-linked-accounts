<?php

namespace Ralkage\LinkedAccounts\Api\Controller;

use Flarum\Api\Controller\AbstractDeleteController;
use Flarum\Http\RequestUtil;
use Psr\Http\Message\ServerRequestInterface;
use Ralkage\LinkedAccounts\Model\LinkedAccountLog;

class ClearLinkedAccountLogsController extends AbstractDeleteController
{
    protected function delete(ServerRequestInterface $request)
    {
        $actor = RequestUtil::getActor($request);
        $actor->assertAdmin();

        LinkedAccountLog::query()->delete();
    }
}
