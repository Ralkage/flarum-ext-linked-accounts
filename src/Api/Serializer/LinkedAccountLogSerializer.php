<?php

namespace Ralkage\LinkedAccounts\Api\Serializer;

use Flarum\Api\Serializer\AbstractSerializer;
use Flarum\Api\Serializer\BasicUserSerializer;

class LinkedAccountLogSerializer extends AbstractSerializer
{
    protected $type = 'linked-account-logs';

    protected function getDefaultAttributes($model)
    {
        return [
            'parentUserId'  => (int) $model->parent_user_id,
            'childUserId'   => (int) $model->child_user_id,
            'action'        => $model->action,
            'ipAddress'     => $model->ip_address,
            'createdAt'     => $this->formatDate($model->created_at),
        ];
    }

    protected function parentUser($model)
    {
        return $this->hasOne($model, BasicUserSerializer::class);
    }

    protected function childUser($model)
    {
        return $this->hasOne($model, BasicUserSerializer::class);
    }
}
