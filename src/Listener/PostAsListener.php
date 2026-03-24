<?php

namespace Ralkage\LinkedAccounts\Listener;

use Flarum\Discussion\Event\Saving as DiscussionSaving;
use Flarum\Post\Event\Saving as PostSaving;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Arr;
use Ralkage\LinkedAccounts\Model\LinkedAccount;

class PostAsListener
{
    public function subscribe(Dispatcher $events): void
    {
        $events->listen(DiscussionSaving::class, [$this, 'onDiscussionSaving']);
        $events->listen(PostSaving::class, [$this, 'onPostSaving']);
    }

    /**
     * When creating a new discussion, swap the author if "Post as" was used.
     */
    public function onDiscussionSaving(DiscussionSaving $event): void
    {
        // Only act on new discussions (no ID yet)
        if ($event->discussion->exists) {
            return;
        }

        $postAsUserId = (int) Arr::get($event->data, 'attributes.postAsUserId', 0);

        if ($postAsUserId && $postAsUserId !== $event->actor->id) {
            $this->validateAndSwap($event->actor->id, $postAsUserId, function ($targetUserId) use ($event) {
                $event->discussion->user_id = $targetUserId;
            });
        }
    }

    /**
     * When creating a new post, swap the author if "Post as" was used.
     */
    public function onPostSaving(PostSaving $event): void
    {
        // Only act on new posts (no ID yet)
        if ($event->post->exists) {
            return;
        }

        $postAsUserId = (int) Arr::get($event->data, 'attributes.postAsUserId', 0);

        if ($postAsUserId && $postAsUserId !== $event->actor->id) {
            $this->validateAndSwap($event->actor->id, $postAsUserId, function ($targetUserId) use ($event) {
                $event->post->user_id = $targetUserId;
            });
        }
    }

    /**
     * Validate that the actor is the parent of the target user, then execute the swap.
     */
    protected function validateAndSwap(int $actorId, int $targetUserId, callable $apply): void
    {
        $link = LinkedAccount::where('parent_user_id', $actorId)
            ->where('child_user_id', $targetUserId)
            ->first();

        if ($link) {
            $apply($targetUserId);
        }
    }
}
