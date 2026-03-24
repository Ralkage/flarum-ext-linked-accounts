<?php

namespace Ralkage\LinkedAccounts\Service;

use Carbon\Carbon;
use Flarum\Foundation\ValidationException;
use Flarum\Settings\SettingsRepositoryInterface;
use Flarum\User\User;
use Illuminate\Support\Str;
use Ralkage\LinkedAccounts\Model\LinkedAccount;
use Ralkage\LinkedAccounts\Model\LinkedAccountLog;

class LinkedAccountService
{
    protected $settings;

    public function __construct(SettingsRepositoryInterface $settings)
    {
        $this->settings = $settings;
    }

    public function getMaxAccounts(): int
    {
        return (int) $this->settings->get('ralkage-linked-accounts.max_accounts', 5);
    }

    public function createSubAccount(User $parent, string $username, string $email, ?string $password): User
    {
        $this->validateCanCreate($parent);

        if (empty($username)) {
            throw new ValidationException(['username' => 'Username is required.']);
        }
        if (empty($email)) {
            throw new ValidationException(['email' => 'Email is required.']);
        }

        if (User::where('username', $username)->exists()) {
            throw new ValidationException(['username' => 'This username is already taken.']);
        }
        if (User::where('email', $email)->exists()) {
            throw new ValidationException(['email' => 'This email is already in use.']);
        }

        $child = User::register($username, $email, $password ?: Str::random(20));
        $child->is_email_confirmed = true;
        $child->is_linked_child = true;
        $child->linked_parent_id = $parent->id;
        $child->save();

        $link = new LinkedAccount();
        $link->parent_user_id = $parent->id;
        $link->child_user_id = $child->id;
        $link->created_at = Carbon::now();
        $link->save();

        $this->updateChildrenCount($parent);

        return $child;
    }

    public function linkExistingAccount(User $parent, string $identification, string $password): User
    {
        $this->validateCanCreate($parent);

        if (empty($identification) || empty($password)) {
            throw new ValidationException(['identification' => 'Username/email and password are required.']);
        }

        $child = User::where('username', $identification)
            ->orWhere('email', $identification)
            ->first();

        if (!$child) {
            throw new ValidationException(['identification' => 'No account found with that username or email.']);
        }

        if (!$child->checkPassword($password)) {
            throw new ValidationException(['password' => 'Incorrect password.']);
        }

        if ($child->id === $parent->id) {
            throw new ValidationException(['identification' => 'You cannot link your own account.']);
        }

        if ($child->is_linked_child) {
            throw new ValidationException(['identification' => 'This account is already linked to another parent account.']);
        }

        if (LinkedAccount::where('parent_user_id', $parent->id)->where('child_user_id', $child->id)->exists()) {
            throw new ValidationException(['identification' => 'This account is already linked.']);
        }

        // Prevent linking a parent account that has its own children
        if ($child->linked_children_count > 0) {
            throw new ValidationException(['identification' => 'Cannot link an account that has its own linked accounts. Unlink those first.']);
        }

        $child->is_linked_child = true;
        $child->linked_parent_id = $parent->id;
        $child->save();

        $link = new LinkedAccount();
        $link->parent_user_id = $parent->id;
        $link->child_user_id = $child->id;
        $link->created_at = Carbon::now();
        $link->save();

        $this->updateChildrenCount($parent);

        return $child;
    }

    public function unlinkAccount(User $actor, int $linkId): void
    {
        $link = LinkedAccount::findOrFail($linkId);

        if ($link->parent_user_id !== $actor->id && !$actor->isAdmin()) {
            throw new ValidationException(['error' => 'You do not have permission to unlink this account.']);
        }

        $child = User::find($link->child_user_id);
        if ($child) {
            $child->is_linked_child = false;
            $child->linked_parent_id = null;
            $child->save();
        }

        $parentId = $link->parent_user_id;
        $link->delete();

        $parent = User::find($parentId);
        if ($parent) {
            $this->updateChildrenCount($parent);
        }
    }

    public function writeLog(int $parentUserId, int $childUserId, string $action, ?string $ipAddress): void
    {
        $log = new LinkedAccountLog();
        $log->parent_user_id = $parentUserId;
        $log->child_user_id = $childUserId;
        $log->action = $action;
        $log->ip_address = $ipAddress;
        $log->created_at = Carbon::now();
        $log->save();
    }

    public function validateCanSwitch(User $actor, int $targetUserId): LinkedAccount
    {
        $link = LinkedAccount::where(function ($query) use ($actor, $targetUserId) {
            // Parent switching to child
            $query->where('parent_user_id', $actor->id)
                ->where('child_user_id', $targetUserId);
        })->orWhere(function ($query) use ($actor, $targetUserId) {
            // Child switching to parent
            $query->where('child_user_id', $actor->id)
                ->where('parent_user_id', $targetUserId);
        })->first();

        if (!$link) {
            throw new ValidationException(['error' => 'No linked account relationship found.']);
        }

        return $link;
    }

    protected function validateCanCreate(User $parent): void
    {
        if ($parent->is_linked_child) {
            throw new ValidationException(['error' => 'Child accounts cannot create linked accounts.']);
        }

        if (!$parent->hasPermission('linkedAccounts.use')) {
            throw new ValidationException(['error' => 'You do not have permission to use linked accounts.']);
        }

        if (!$parent->hasPermission('linkedAccounts.create')) {
            throw new ValidationException(['error' => 'You do not have permission to create linked accounts.']);
        }

        $maxAccounts = $this->getMaxAccounts();
        if ($maxAccounts > 0) {
            $currentCount = LinkedAccount::where('parent_user_id', $parent->id)->count();
            if ($currentCount >= $maxAccounts) {
                throw new ValidationException([
                    'error' => "You have reached the maximum number of linked accounts ({$maxAccounts}).",
                ]);
            }
        }
    }

    protected function updateChildrenCount(User $parent): void
    {
        $parent->linked_children_count = LinkedAccount::where('parent_user_id', $parent->id)->count();
        $parent->save();
    }
}
