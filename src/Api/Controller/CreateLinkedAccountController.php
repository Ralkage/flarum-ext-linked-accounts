<?php

namespace Ralkage\LinkedAccounts\Api\Controller;

use Flarum\Http\RequestUtil;
use Illuminate\Support\Arr;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Ralkage\LinkedAccounts\Model\LinkedAccount;
use Ralkage\LinkedAccounts\Service\LinkedAccountService;

class CreateLinkedAccountController implements RequestHandlerInterface
{
    public function __construct(
        protected LinkedAccountService $service,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $actor = RequestUtil::getActor($request);
        $actor->assertRegistered();

        $data = Arr::get($request->getParsedBody(), 'data.attributes', []);

        $child = $this->service->createSubAccount(
            $actor,
            Arr::get($data, 'username', ''),
            Arr::get($data, 'email', ''),
            Arr::get($data, 'password')
        );

        $this->service->writeLog(
            $actor->id,
            $child->id,
            'create',
            $request->getServerParams()['REMOTE_ADDR'] ?? null
        );

        $link = LinkedAccount::where('parent_user_id', $actor->id)
            ->where('child_user_id', $child->id)
            ->with(['childUser', 'parentUser'])
            ->firstOrFail();

        return new JsonResponse([
            'data' => [
                'type' => 'linked-accounts',
                'id' => (string) $link->id,
                'attributes' => [
                    'parentUserId' => (int) $link->parent_user_id,
                    'childUserId' => (int) $link->child_user_id,
                    'createdAt' => $link->created_at?->toIso8601String(),
                ],
                'relationships' => [
                    'parentUser' => [
                        'data' => ['type' => 'users', 'id' => (string) $link->parent_user_id],
                    ],
                    'childUser' => [
                        'data' => ['type' => 'users', 'id' => (string) $link->child_user_id],
                    ],
                ],
            ],
            'included' => array_filter([
                $link->parentUser ? [
                    'type' => 'users',
                    'id' => (string) $link->parentUser->id,
                    'attributes' => [
                        'username' => $link->parentUser->username,
                        'displayName' => $link->parentUser->display_name,
                        'avatarUrl' => $link->parentUser->avatar_url,
                    ],
                ] : null,
                $link->childUser ? [
                    'type' => 'users',
                    'id' => (string) $link->childUser->id,
                    'attributes' => [
                        'username' => $link->childUser->username,
                        'displayName' => $link->childUser->display_name,
                        'avatarUrl' => $link->childUser->avatar_url,
                    ],
                ] : null,
            ]),
        ], 201);
    }
}
