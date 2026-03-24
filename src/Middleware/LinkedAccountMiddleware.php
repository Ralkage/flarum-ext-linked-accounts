<?php

namespace Ralkage\LinkedAccounts\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class LinkedAccountMiddleware implements MiddlewareInterface
{
    /**
     * The parent user ID if the current user has switched from a parent account.
     * Null if not switched (i.e., logged in directly).
     */
    public static ?int $parentUserId = null;

    /**
     * The parent user's display name if currently switched.
     */
    public static ?string $parentUserName = null;

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        // Reset static state for each request
        self::$parentUserId = null;
        self::$parentUserName = null;

        $session = $request->getAttribute('session');

        if ($session && $session->has('linked_account_parent_id')) {
            self::$parentUserId = (int) $session->get('linked_account_parent_id');
            self::$parentUserName = $session->get('linked_account_parent_name');
        }

        return $handler->handle($request);
    }
}
