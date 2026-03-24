import app from 'flarum/forum/app';
import { extend } from 'flarum/common/extend';
import HeaderSecondary from 'flarum/forum/components/HeaderSecondary';
import SessionDropdown from 'flarum/forum/components/SessionDropdown';
import LinkButton from 'flarum/common/components/LinkButton';
import Button from 'flarum/common/components/Button';
import DiscussionComposer from 'flarum/forum/components/DiscussionComposer';
import ReplyComposer from 'flarum/forum/components/ReplyComposer';
import LinkedAccountsPage from './components/LinkedAccountsPage';

// ---- Linked accounts cache for "Post as" dropdown ----
let linkedAccountsCache = null;

function getLinkedAccounts() {
    if (linkedAccountsCache !== null) return Promise.resolve(linkedAccountsCache);

    return app.request({
        method: 'GET',
        url: app.forum.attribute('apiUrl') + '/linked-accounts',
    }).then(response => {
        linkedAccountsCache = (response.data || []).map(item => {
            const ref = item.relationships?.childUser?.data;
            if (!ref || !response.included) return null;
            const found = response.included.find(i => i.type === ref.type && String(i.id) === String(ref.id));
            return found ? { id: found.id, ...found.attributes } : null;
        }).filter(Boolean);
        return linkedAccountsCache;
    }).catch(() => {
        linkedAccountsCache = [];
        return [];
    });
}

// Invalidate cache when accounts change (e.g. after create/link/unlink)
export function clearLinkedAccountsCache() {
    linkedAccountsCache = null;
}

// ---- "Post as" composer integration ----
function addPostAsToComposer(ComposerClass) {
    extend(ComposerClass.prototype, 'oninit', function () {
        const user = app.session.user;
        if (user && user.attribute('linkedChildrenCount') > 0 && !user.attribute('isLinkedChild')) {
            this.postAsAccounts = [];
            this.postAsUserId = null;
            getLinkedAccounts().then(accounts => {
                this.postAsAccounts = accounts;
                m.redraw();
            });
        }
    });

    extend(ComposerClass.prototype, 'headerItems', function (items) {
        if (!this.postAsAccounts || this.postAsAccounts.length === 0) return;

        const user = app.session.user;

        items.add('post-as',
            <div className="PostAs">
                <label className="PostAs-label">
                    <i className="fas fa-user-edit"></i>{' '}
                    {app.translator.trans('ralkage-linked-accounts.forum.composer.post_as')}
                </label>
                <select
                    className="FormControl PostAs-select"
                    value={this.postAsUserId || ''}
                    onchange={e => { this.postAsUserId = e.target.value || null; }}
                >
                    <option value="">{user.displayName()}</option>
                    {this.postAsAccounts.map(account => (
                        <option value={account.id} key={account.id}>
                            {account.displayName || account.username}
                        </option>
                    ))}
                </select>
            </div>,
            5
        );
    });

    extend(ComposerClass.prototype, 'data', function (data) {
        if (this.postAsUserId) {
            data.postAsUserId = parseInt(this.postAsUserId, 10);
        }
    });
}

// ---- Main initializer ----
app.initializers.add('ralkage-linked-accounts', () => {
    // Register the management page route
    app.routes['linked-accounts'] = {
        path: '/linked-accounts',
        component: LinkedAccountsPage,
    };

    // Add "Linked Accounts" link to the user session dropdown menu
    extend(SessionDropdown.prototype, 'items', function (items) {
        const user = app.session.user;
        if (!user) return;

        if (user.attribute('canUseLinkedAccounts') || user.attribute('isLinkedChild')) {
            items.add('linked-accounts',
                <LinkButton href={app.route('linked-accounts')} icon="fas fa-users-cog">
                    {app.translator.trans('ralkage-linked-accounts.forum.nav.linked_accounts')}
                </LinkButton>,
                -90
            );
        }
    });

    // Show a "Revert to Parent" button in the header when the user has switched accounts
    extend(HeaderSecondary.prototype, 'items', function (items) {
        const parentId = app.forum.attribute('linkedAccountParentId');
        const parentName = app.forum.attribute('linkedAccountParentName');

        if (parentId && parentName) {
            items.add('linked-account-revert',
                <Button
                    className="Button LinkedAccount-revertBtn"
                    icon="fas fa-undo"
                    onclick={() => revertAccount()}
                >
                    {app.translator.trans('ralkage-linked-accounts.forum.revert_to', { name: parentName })}
                </Button>,
                15
            );
        }
    });

    // Add "Post as" dropdown to discussion and reply composers
    addPostAsToComposer(DiscussionComposer);
    addPostAsToComposer(ReplyComposer);
});

/**
 * Submit a form to switch the session to a different linked account.
 */
export function switchAccount(userId) {
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = app.forum.attribute('baseUrl') + '/linked-accounts/switch';

    const csrfInput = document.createElement('input');
    csrfInput.type = 'hidden';
    csrfInput.name = 'csrfToken';
    csrfInput.value = app.session.csrfToken;
    form.appendChild(csrfInput);

    const userInput = document.createElement('input');
    userInput.type = 'hidden';
    userInput.name = 'userId';
    userInput.value = userId;
    form.appendChild(userInput);

    document.body.appendChild(form);
    form.submit();
}

/**
 * Submit a form to revert back to the parent account.
 */
export function revertAccount() {
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = app.forum.attribute('baseUrl') + '/linked-accounts/revert';

    const csrfInput = document.createElement('input');
    csrfInput.type = 'hidden';
    csrfInput.name = 'csrfToken';
    csrfInput.value = app.session.csrfToken;
    form.appendChild(csrfInput);

    document.body.appendChild(form);
    form.submit();
}
