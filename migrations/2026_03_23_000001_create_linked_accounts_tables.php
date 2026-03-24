<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\Builder;

return [
    'up' => function (Builder $schema) {
        $schema->create('linked_accounts', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('parent_user_id');
            $table->unsignedInteger('child_user_id');
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['parent_user_id', 'child_user_id']);
            $table->index('child_user_id');

            $table->foreign('parent_user_id')
                ->references('id')->on('users')
                ->onDelete('cascade');
            $table->foreign('child_user_id')
                ->references('id')->on('users')
                ->onDelete('cascade');
        });

        $schema->create('linked_account_logs', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('parent_user_id');
            $table->unsignedInteger('child_user_id');
            $table->string('action', 25);
            $table->string('ip_address', 45)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index('created_at');
            $table->index('parent_user_id');
        });

        if (!$schema->hasColumn('users', 'is_linked_child')) {
            $schema->table('users', function (Blueprint $table) {
                $table->boolean('is_linked_child')->default(false);
                $table->unsignedInteger('linked_parent_id')->nullable();
                $table->unsignedInteger('linked_children_count')->default(0);
            });
        }
    },

    'down' => function (Builder $schema) {
        $schema->dropIfExists('linked_account_logs');
        $schema->dropIfExists('linked_accounts');

        if ($schema->hasColumn('users', 'is_linked_child')) {
            $schema->table('users', function (Blueprint $table) {
                $table->dropColumn(['is_linked_child', 'linked_parent_id', 'linked_children_count']);
            });
        }
    },
];
