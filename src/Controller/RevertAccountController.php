<?php

namespace Ralkage\LinkedAccounts\Controller;

use Flarum\Foundation\ValidationException;
use Flarum\Http\RememberAccessToken;
use Flarum\Http\Rememberer;
use Flarum\Http\RequestUtil;
use Flarum\Http\SessionAccessToken;
use Flarum\Http\SessionAuthenticator;
use Flarum\Http\UrlGenerator;
use Flarum\User\User;
use Laminas\Diactoros\Response\RedirectResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Ralkage\LinkedAccounts\Service\LinkedAccountService;

class RevertAccountController implements RequestHandlerInterface
{
    protected $authenticator;
    protected $service;
    protected $url;
    protected $rememberer;

    public function __construct(
        SessionAuthenticator $authenticator,
        LinkedAccountService $service,
        UrlGenerator $url,
        Rememberer $rememberer
    ) {
        $this->authenticator = $authenticator;
        $this->service = $service;
        $this->url = $url;
        $this->rememberer = $rememberer;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $actor = RequestUtil::getActor($request);
        $session = $request->getAttribute('session');

        if (!$actor || !$actor->id) {
            throw new ValidationException(['error' => 'You must be logged in.']);
        }

        $parentId = $session->get('linked_account_parent_id');

        if (!$parentId) {
            throw new ValidationException(['error' => 'You are not currently switched to a linked account.']);
        }

        $parentUser = User::findOrFail($parentId);

        // Log the revert action before switching
        $this->service->writeLog(
            $parentId,
            $actor->id,
            'revert',
            $request->getServerParams()['REMOTE_ADDR'] ?? null
        );

        // Create a new session token for the parent user
        $token = SessionAccessToken::generate($parentUser->id);

        // Switch back to parent
        $this->authenticator->logIn($session, $token);

        // Clear the linked account session data
        $session->remove('linked_account_parent_id');
        $session->remove('linked_account_parent_name');

        $response = new RedirectResponse($this->url->to('forum')->base());

        // Set remember cookie back to the parent user
        $rememberToken = RememberAccessToken::generate($parentUser->id);

        return $this->rememberer->remember($response, $rememberToken);
    }
}
