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
use Illuminate\Support\Arr;
use Laminas\Diactoros\Response\RedirectResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Ralkage\LinkedAccounts\Service\LinkedAccountService;

class SwitchAccountController implements RequestHandlerInterface
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
        $body = $request->getParsedBody();
        $targetUserId = (int) Arr::get($body, 'userId');

        if (!$actor || !$actor->id) {
            throw new ValidationException(['error' => 'You must be logged in.']);
        }

        // Validate a link exists between actor and target
        $link = $this->service->validateCanSwitch($actor, $targetUserId);

        $targetUser = User::findOrFail($targetUserId);

        // Determine the root parent
        $isActorParent = ((int) $link->parent_user_id === (int) $actor->id);
        $parentId = $isActorParent ? $actor->id : (int) $link->parent_user_id;
        $parentUser = $isActorParent ? $actor : User::findOrFail($link->parent_user_id);

        // Store parent info in session before switching
        $session->put('linked_account_parent_id', $parentId);
        $session->put('linked_account_parent_name', $parentUser->display_name);

        // Create a new session token for the target user
        $token = SessionAccessToken::generate($targetUser->id);

        // Switch session to the target user
        $this->authenticator->logIn($session, $token);

        // Log the switch action
        $this->service->writeLog(
            $parentId,
            $targetUserId,
            'switch',
            $request->getServerParams()['REMOTE_ADDR'] ?? null
        );

        $response = new RedirectResponse($this->url->to('forum')->base());

        // CRITICAL: Create a remember token for the target user and set the
        // remember cookie. Without this, RememberFromCookie middleware would
        // restore the parent's session on every subsequent request, because
        // the old remember cookie still points to the parent.
        $rememberToken = RememberAccessToken::generate($targetUser->id);

        return $this->rememberer->remember($response, $rememberToken);
    }
}
