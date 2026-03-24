import Extend from 'flarum/common/extenders';
import app from 'flarum/admin/app';

export default [
  new Extend.Admin()
    .permission(
      () => ({
        icon: 'fas fa-users',
        label: app.translator.trans('ralkage-linked-accounts.admin.permissions.use'),
        permission: 'linkedAccounts.use',
      }),
      'start',
      95
    )
    .permission(
      () => ({
        icon: 'fas fa-user-plus',
        label: app.translator.trans('ralkage-linked-accounts.admin.permissions.create'),
        permission: 'linkedAccounts.create',
      }),
      'start',
      94
    )
    .permission(
      () => ({
        icon: 'fas fa-eye',
        label: app.translator.trans('ralkage-linked-accounts.admin.permissions.view_any'),
        permission: 'linkedAccounts.viewAny',
      }),
      'moderate',
      95
    ),
];
