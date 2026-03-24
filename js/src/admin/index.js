import app from 'flarum/admin/app';
import LinkedAccountsPage from './components/LinkedAccountsPage';

app.initializers.add('ralkage-linked-accounts', () => {
    app.extensionData
        .for('ralkage-linked-accounts')
        .registerPage(LinkedAccountsPage)
        .registerPermission(
            {
                icon: 'fas fa-users',
                label: app.translator.trans('ralkage-linked-accounts.admin.permissions.use'),
                permission: 'linkedAccounts.use',
            },
            'start'
        )
        .registerPermission(
            {
                icon: 'fas fa-user-plus',
                label: app.translator.trans('ralkage-linked-accounts.admin.permissions.create'),
                permission: 'linkedAccounts.create',
            },
            'start'
        )
        .registerPermission(
            {
                icon: 'fas fa-eye',
                label: app.translator.trans('ralkage-linked-accounts.admin.permissions.view_any'),
                permission: 'linkedAccounts.viewAny',
            },
            'moderate'
        );
});
