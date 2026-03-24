import app from 'flarum/admin/app';
import LinkedAccountsPage from './components/LinkedAccountsPage';

export { default as extend } from './extend';

app.initializers.add('ralkage-linked-accounts', () => {
    app.registry
        .for('ralkage-linked-accounts')
        .registerPage(LinkedAccountsPage);
});
