<?php

namespace Ralkage\LinkedAccounts\Model;

use Flarum\Database\AbstractModel;
use Flarum\User\User;

class LinkedAccount extends AbstractModel
{
    protected $table = 'linked_accounts';

    public $timestamps = false;

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function parentUser()
    {
        return $this->belongsTo(User::class, 'parent_user_id');
    }

    public function childUser()
    {
        return $this->belongsTo(User::class, 'child_user_id');
    }
}
