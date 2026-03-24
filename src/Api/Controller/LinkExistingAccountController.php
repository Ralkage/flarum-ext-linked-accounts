<?php

namespace Ralkage\LinkedAccounts\Api\Controller;

use Flarum\Api\Controller\AbstractCreateController;
use Flarum\Foundation\ValidationException;
use Flarum\Http\RequestUtil;
use Illuminate\Support\Arr;
use Psr\Http\Message\ServerRequestInterface;
use Tobscure\JsonApi\Document;
use Ralkage\LinkedAccounts\Api\Serializer\LinkedAccountSerializer;
use Ralkage\LinkedAccounts\Model\LinkedAccount;
use Ralkage\LinkedAccounts\Service\LinkedAccountService;

class LinkExistingAccountController extends AbstractCreateController
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
        $ip = $request->getServerParams()['REMOTE_ADDR'] ?? null;

        try {
            $child = $this->service->linkExistingAccount(
                $actor,
                Arr::get($data, 'identification', ''),
                Arr::get($data, 'password', '')
            );
        } catch (ValidationException $e) {
            $errors = $e->getAttributes();
            if (isset($errors['password']) || isset($errors['identification'])) {
                $this->service->writeLog($actor->id, 0, 'link_failed', $ip);
            }
            throw $e;
        }

        $this->service->writeLog(
            $actor->id,
            $child->id,
            'link',
            $ip
        );

        return LinkedAccount::where('parent_user_id', $actor->id)
            ->where('child_user_id', $child->id)
            ->with(['childUser', 'parentUser'])
            ->firstOrFail();
    }
}
