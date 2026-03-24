import Extend from 'flarum/common/extenders';
import LinkedAccountsPage from './components/LinkedAccountsPage';

export default [
  new Extend.Routes()
    .add('linked-accounts', '/linked-accounts', LinkedAccountsPage),
];
