import app from 'flarum/forum/app';
import Page from 'flarum/common/components/Page';
import Button from 'flarum/common/components/Button';
import LoadingIndicator from 'flarum/common/components/LoadingIndicator';
import IndexPage from 'flarum/forum/components/IndexPage';
import listItems from 'flarum/common/helpers/listItems';
import { switchAccount, revertAccount } from '../index';

export default class LinkedAccountsPage extends Page {
    oninit(vnode) {
        super.oninit(vnode);

        this.loading = true;
        this.accounts = [];

        this.createUsername = '';
        this.createEmail = '';
        this.createPassword = '';
        this.creating = false;

        this.linkIdentification = '';
        this.linkPassword = '';
        this.linking = false;

        this.activeForm = null;

        this.loadAccounts();
    }

    view() {
        const user = app.session.user;

        if (!user) {
            return (
                <div className="IndexPage">
                    <div className="container">
                        <p>Please log in to manage linked accounts.</p>
                    </div>
                </div>
            );
        }

        const isParent = !user.attribute('isLinkedChild');
        const parentId = app.forum.attribute('linkedAccountParentId');
        const parentName = app.forum.attribute('linkedAccountParentName');

        return (
            <div className="IndexPage">
                <div className="container">
                    <div className="sideNavContainer">
                        <nav className="IndexPage-nav sideNav">
                            <ul>{listItems(IndexPage.prototype.navItems().toArray())}</ul>
                        </nav>
                        <div className="IndexPage-results sideNavOffset">
                            <div className="LinkedAccountsPage">
                                {/* Switched account alert */}
                                {parentId && parentName && (
                                    <div className="Alert Alert--info LinkedAccountsPage-switchedAlert">
                                        <span className="Alert-body">
                                            <i className="fas fa-exchange-alt"></i>{' '}
                                            {app.translator.trans('ralkage-linked-accounts.forum.page.switched_notice', {
                                                name: <strong>{parentName}</strong>,
                                            })}
                                        </span>
                                        <Button
                                            className="Button Button--link Alert-control"
                                            icon="fas fa-undo"
                                            onclick={() => revertAccount()}
                                        >
                                            {app.translator.trans('ralkage-linked-accounts.forum.page.revert')}
                                        </Button>
                                    </div>
                                )}

                                {/* Child account info panel */}
                                {!isParent && (
                                    <div className="LinkedAccountsPage-childInfo">
                                        <p>
                                            {app.translator.trans('ralkage-linked-accounts.forum.page.child_info')}
                                        </p>
                                        <Button
                                            className="Button Button--primary"
                                            icon="fas fa-undo"
                                            onclick={() => switchAccount(user.attribute('linkedParentId'))}
                                        >
                                            {app.translator.trans('ralkage-linked-accounts.forum.page.switch_to_parent')}
                                        </Button>
                                    </div>
                                )}

                                {/* Main content — only parent accounts manage linked accounts */}
                                {isParent && (
                                    this.loading ? (
                                        <LoadingIndicator />
                                    ) : (
                                        <div>
                                            {this.accountsList()}

                                            {user.attribute('canCreateLinkedAccounts') && this.actionSection()}
                                        </div>
                                    )
                                )}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        );
    }

    accountsList() {
        if (this.accounts.length === 0 && !app.session.user.attribute('canCreateLinkedAccounts')) {
            return (
                <div className="LinkedAccountsPage-placeholder">
                    <p>{app.translator.trans('ralkage-linked-accounts.forum.page.no_accounts')}</p>
                </div>
            );
        }

        if (this.accounts.length === 0) return null;

        return (
            <div className="LinkedAccountsPage-accounts">
                <h3 className="LinkedAccountsPage-heading">
                    {app.translator.trans('ralkage-linked-accounts.forum.page.title')}
                    <span className="LinkedAccountsPage-count">{this.accounts.length}</span>
                </h3>
                <ul className="LinkedAccountsPage-list">
                    {this.accounts.map(account => this.accountItem(account))}
                </ul>
            </div>
        );
    }

    accountItem(account) {
        const child = account.childUser;
        if (!child) return null;

        const user = app.session.user;
        const isCurrentUser = String(child.id) === String(user.id());
        const initial = (child.displayName || child.username || '?').charAt(0).toUpperCase();

        return (
            <li className={'LinkedAccountsPage-account' + (isCurrentUser ? ' active' : '')} key={account.id}>
                <span className="LinkedAccountsPage-account-avatar">
                    {child.avatarUrl
                        ? <img src={child.avatarUrl} alt="" className="Avatar Avatar--medium" />
                        : <span className="Avatar Avatar--medium" style={{ backgroundColor: child.color || '@primary-color' }}>{initial}</span>
                    }
                </span>

                <span className="LinkedAccountsPage-account-main">
                    <span className="LinkedAccountsPage-account-name">
                        {child.displayName || child.username}
                    </span>
                    <span className="LinkedAccountsPage-account-info">
                        {app.translator.trans('ralkage-linked-accounts.forum.page.linked_on', {
                            date: new Date(account.createdAt).toLocaleDateString(),
                        })}
                        {isCurrentUser && (
                            <span className="LinkedAccountsPage-account-current">
                                {app.translator.trans('ralkage-linked-accounts.forum.page.current')}
                            </span>
                        )}
                    </span>
                </span>

                <span className="LinkedAccountsPage-account-controls">
                    {!isCurrentUser && (
                        <Button
                            className="Button Button--primary"
                            icon="fas fa-sign-in-alt"
                            onclick={() => switchAccount(child.id)}
                        >
                            {app.translator.trans('ralkage-linked-accounts.forum.page.switch')}
                        </Button>
                    )}
                    {!isCurrentUser && (
                        <Button
                            className="Button Button--icon Button--danger"
                            icon="fas fa-unlink"
                            onclick={() => this.unlinkAccount(account.id)}
                            title={app.translator.trans('ralkage-linked-accounts.forum.page.unlink')}
                        />
                    )}
                </span>
            </li>
        );
    }

    actionSection() {
        const maxAccounts = app.forum.attribute('linkedAccountsMaxAccounts') || 0;
        const currentCount = this.accounts.length;

        if (maxAccounts > 0 && currentCount >= maxAccounts) {
            return (
                <div className="Alert">
                    <span className="Alert-body">
                        <i className="fas fa-exclamation-triangle"></i>{' '}
                        {app.translator.trans('ralkage-linked-accounts.forum.page.max_reached', { max: maxAccounts })}
                    </span>
                </div>
            );
        }

        return (
            <div className="LinkedAccountsPage-add">
                {this.accounts.length === 0 && (
                    <div className="LinkedAccountsPage-placeholder">
                        <p>{app.translator.trans('ralkage-linked-accounts.forum.page.no_accounts')}</p>
                    </div>
                )}

                <div className="LinkedAccountsPage-addOptions">
                    <button
                        className={'LinkedAccountsPage-option' + (this.activeForm === 'create' ? ' active' : '')}
                        onclick={() => { this.activeForm = this.activeForm === 'create' ? null : 'create'; }}
                    >
                        <span className="LinkedAccountsPage-option-icon">
                            <i className="fas fa-user-plus"></i>
                        </span>
                        <span className="LinkedAccountsPage-option-label">
                            <strong>{app.translator.trans('ralkage-linked-accounts.forum.page.create_new')}</strong>
                            <span>{app.translator.trans('ralkage-linked-accounts.forum.page.create_description')}</span>
                        </span>
                    </button>

                    <button
                        className={'LinkedAccountsPage-option' + (this.activeForm === 'link' ? ' active' : '')}
                        onclick={() => { this.activeForm = this.activeForm === 'link' ? null : 'link'; }}
                    >
                        <span className="LinkedAccountsPage-option-icon">
                            <i className="fas fa-link"></i>
                        </span>
                        <span className="LinkedAccountsPage-option-label">
                            <strong>{app.translator.trans('ralkage-linked-accounts.forum.page.link_existing')}</strong>
                            <span>{app.translator.trans('ralkage-linked-accounts.forum.page.link_description')}</span>
                        </span>
                    </button>
                </div>

                {this.activeForm === 'create' && this.createForm()}
                {this.activeForm === 'link' && this.linkForm()}
            </div>
        );
    }

    createForm() {
        return (
            <div className="LinkedAccountsPage-form Form">
                <div className="Form-group">
                    <label>{app.translator.trans('ralkage-linked-accounts.forum.page.username')}</label>
                    <input
                        type="text"
                        className="FormControl"
                        value={this.createUsername}
                        oninput={e => { this.createUsername = e.target.value; }}
                        placeholder={app.translator.trans('ralkage-linked-accounts.forum.page.username_placeholder')}
                    />
                </div>

                <div className="Form-group">
                    <label>{app.translator.trans('ralkage-linked-accounts.forum.page.email')}</label>
                    <input
                        type="email"
                        className="FormControl"
                        value={this.createEmail}
                        oninput={e => { this.createEmail = e.target.value; }}
                        placeholder={app.translator.trans('ralkage-linked-accounts.forum.page.email_placeholder')}
                    />
                </div>

                <div className="Form-group">
                    <label>{app.translator.trans('ralkage-linked-accounts.forum.page.password')}</label>
                    <input
                        type="password"
                        className="FormControl"
                        value={this.createPassword}
                        oninput={e => { this.createPassword = e.target.value; }}
                        placeholder={app.translator.trans('ralkage-linked-accounts.forum.page.password_placeholder')}
                    />
                </div>

                <div className="Form-group">
                    <Button
                        className="Button Button--primary"
                        loading={this.creating}
                        disabled={!this.createUsername || !this.createEmail}
                        onclick={() => this.submitCreate()}
                        icon="fas fa-plus"
                    >
                        {app.translator.trans('ralkage-linked-accounts.forum.page.create_submit')}
                    </Button>
                    <Button
                        className="Button Button--link"
                        onclick={() => { this.activeForm = null; }}
                    >
                        Cancel
                    </Button>
                </div>
            </div>
        );
    }

    linkForm() {
        return (
            <div className="LinkedAccountsPage-form Form">
                <div className="Form-group">
                    <label>{app.translator.trans('ralkage-linked-accounts.forum.page.identification')}</label>
                    <input
                        type="text"
                        className="FormControl"
                        value={this.linkIdentification}
                        oninput={e => { this.linkIdentification = e.target.value; }}
                        placeholder={app.translator.trans('ralkage-linked-accounts.forum.page.identification_placeholder')}
                    />
                </div>

                <div className="Form-group">
                    <label>{app.translator.trans('ralkage-linked-accounts.forum.page.password')}</label>
                    <input
                        type="password"
                        className="FormControl"
                        value={this.linkPassword}
                        oninput={e => { this.linkPassword = e.target.value; }}
                        placeholder={app.translator.trans('ralkage-linked-accounts.forum.page.link_password_placeholder')}
                    />
                </div>

                <div className="Form-group">
                    <Button
                        className="Button Button--primary"
                        loading={this.linking}
                        disabled={!this.linkIdentification || !this.linkPassword}
                        onclick={() => this.submitLink()}
                        icon="fas fa-link"
                    >
                        {app.translator.trans('ralkage-linked-accounts.forum.page.link_submit')}
                    </Button>
                    <Button
                        className="Button Button--link"
                        onclick={() => { this.activeForm = null; }}
                    >
                        Cancel
                    </Button>
                </div>
            </div>
        );
    }

    // --- Data operations ---

    loadAccounts() {
        this.loading = true;

        app.request({
            method: 'GET',
            url: app.forum.attribute('apiUrl') + '/linked-accounts',
        }).then(response => {
            this.accounts = this.parseResponse(response);
            this.loading = false;
            m.redraw();
        }).catch(() => {
            this.loading = false;
            m.redraw();
        });
    }

    parseResponse(response) {
        return (response.data || []).map(item => ({
            id: item.id,
            ...item.attributes,
            childUser: this.findIncluded(response, item.relationships?.childUser?.data),
            parentUser: this.findIncluded(response, item.relationships?.parentUser?.data),
        }));
    }

    findIncluded(response, ref) {
        if (!ref || !response.included) return null;
        const found = response.included.find(i => i.type === ref.type && String(i.id) === String(ref.id));
        return found ? { id: found.id, ...found.attributes } : null;
    }

    submitCreate() {
        this.creating = true;

        app.request({
            method: 'POST',
            url: app.forum.attribute('apiUrl') + '/linked-accounts/create',
            body: {
                data: {
                    attributes: {
                        username: this.createUsername,
                        email: this.createEmail,
                        password: this.createPassword || undefined,
                    },
                },
            },
        }).then(() => {
            this.createUsername = '';
            this.createEmail = '';
            this.createPassword = '';
            this.activeForm = null;
            this.creating = false;
            this.loadAccounts();
            app.alerts.show({ type: 'success' }, app.translator.trans('ralkage-linked-accounts.forum.page.create_success'));
        }).catch(error => {
            this.creating = false;
            m.redraw();
            const msg = error.response?.errors?.[0]?.detail || 'An error occurred.';
            app.alerts.show({ type: 'error' }, msg);
        });
    }

    submitLink() {
        this.linking = true;

        app.request({
            method: 'POST',
            url: app.forum.attribute('apiUrl') + '/linked-accounts/link',
            body: {
                data: {
                    attributes: {
                        identification: this.linkIdentification,
                        password: this.linkPassword,
                    },
                },
            },
        }).then(() => {
            this.linkIdentification = '';
            this.linkPassword = '';
            this.activeForm = null;
            this.linking = false;
            this.loadAccounts();
            app.alerts.show({ type: 'success' }, app.translator.trans('ralkage-linked-accounts.forum.page.link_success'));
        }).catch(error => {
            this.linking = false;
            m.redraw();
            const msg = error.response?.errors?.[0]?.detail || 'An error occurred.';
            app.alerts.show({ type: 'error' }, msg);
        });
    }

    unlinkAccount(linkId) {
        if (!confirm(app.translator.trans('ralkage-linked-accounts.forum.page.unlink_confirm'))) return;

        app.request({
            method: 'DELETE',
            url: app.forum.attribute('apiUrl') + '/linked-accounts/' + linkId,
        }).then(() => {
            this.loadAccounts();
            app.alerts.show({ type: 'success' }, app.translator.trans('ralkage-linked-accounts.forum.page.unlink_success'));
        }).catch(error => {
            const msg = error.response?.errors?.[0]?.detail || 'An error occurred.';
            app.alerts.show({ type: 'error' }, msg);
        });
    }
}
