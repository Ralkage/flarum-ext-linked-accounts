<?php

namespace Ralkage\LinkedAccounts\Throttler;

use Carbon\Carbon;
use Flarum\Http\RequestUtil;
use Psr\Http\Message\ServerRequestInterface;
use Ralkage\LinkedAccounts\Model\LinkedAccountLog;

/**
 * Throttle account linking attempts to prevent brute-force password guessing.
 *
 * After 5 failed attempts within 15 minutes, the user is blocked from
 * further link attempts until the window expires.
 */
class LinkAccountThrottler
{
    public static int $maxAttempts = 5;
    public static int $decayMinutes = 15;

    public function __invoke(ServerRequestInterface $request): ?bool
    {
        if ($request->getAttribute('routeName') !== 'linked-accounts.link') {
            return null;
        }

        $actor = RequestUtil::getActor($request);

        if (! $actor->id) {
            return null;
        }

        $recentFailures = LinkedAccountLog::where('parent_user_id', $actor->id)
            ->where('action', 'link_failed')
            ->where('created_at', '>=', Carbon::now()->subMinutes(self::$decayMinutes))
            ->count();

        if ($recentFailures >= self::$maxAttempts) {
            return true;
        }

        return null;
    }
}
