<?php

namespace Ralkage\LinkedAccounts\Api\Controller;

use Flarum\Api\Controller\AbstractCreateController;
use Flarum\Http\RequestUtil;
use Illuminate\Support\Arr;
use Psr\Http\Message\ServerRequestInterface;
use Tobscure\JsonApi\Document;
use Ralkage\LinkedAccounts\Api\Serializer\LinkedAccountSerializer;
use Ralkage\LinkedAccounts\Model\LinkedAccount;
use Ralkage\LinkedAccounts\Service\LinkedAccountService;

class CreateLinkedAccountController extends AbstractCreateController
{
    public $serializer = LinkedAccountSerializer::class;

    public $include = ['childUser', 'parentUser'];

    protected $service;

    public function __construct(LinkedAccountService $service)
    {
        $this->service = $service;
    }

    protected function data(ServerRequestInterface $request, Document $document)
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

        return LinkedAccount::where('parent_user_id', $actor->id)
            ->where('child_user_id', $child->id)
            ->with(['childUser', 'parentUser'])
            ->firstOrFail();
    }
}
