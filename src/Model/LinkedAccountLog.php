<?php

namespace Ralkage\LinkedAccounts\Model;

use Flarum\Database\AbstractModel;
use Flarum\User\User;

class LinkedAccountLog extends AbstractModel
{
    protected $table = 'linked_account_logs';

    public $timestamps = false;

    protected $casts = [
        'created_at' => 'datetime',
    ];

    protected $fillable = [
        'parent_user_id',
        'child_user_id',
        'action',
        'ip_address',
        'created_at',
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
