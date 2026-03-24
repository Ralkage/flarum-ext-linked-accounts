<?php

namespace Ralkage\LinkedAccounts\Api\Controller;

use Flarum\Http\RequestUtil;
use Laminas\Diactoros\Response\EmptyResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Ralkage\LinkedAccounts\Model\LinkedAccountLog;

class ClearLinkedAccountLogsController implements RequestHandlerInterface
{
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $actor = RequestUtil::getActor($request);
        $actor->assertAdmin();

        LinkedAccountLog::query()->delete();

        return new EmptyResponse(204);
    }
}
