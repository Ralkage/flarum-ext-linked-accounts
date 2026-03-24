import app from 'flarum/admin/app';
import ExtensionPage from 'flarum/admin/components/ExtensionPage';
import Button from 'flarum/common/components/Button';
import LoadingIndicator from 'flarum/common/components/LoadingIndicator';

export default class LinkedAccountsPage extends ExtensionPage {
    oninit(vnode) {
        super.oninit(vnode);
        this.activeTab = 'settings';
        this.logs = [];
        this.logsLoading = false;
        this.logsTotal = 0;
        this.logsPage = 0;
    }

    content() {
        return (
            <div className="LinkedAccountsPage">
                <div className="container">
                    <div className="LinkedAccountsPage-tabs">
                        <button
                            className={'LinkedAccountsPage-tab' + (this.activeTab === 'settings' ? ' active' : '')}
                            onclick={() => { this.activeTab = 'settings'; }}
                        >
                            <i className="fas fa-cog"></i> {app.translator.trans('ralkage-linked-accounts.admin.tabs.settings')}
                        </button>
                        <button
                            className={'LinkedAccountsPage-tab' + (this.activeTab === 'logs' ? ' active' : '')}
                            onclick={() => { this.activeTab = 'logs'; if (this.logs.length === 0) this.loadLogs(); }}
                        >
                            <i className="fas fa-history"></i> {app.translator.trans('ralkage-linked-accounts.admin.tabs.logs')}
                        </button>
                    </div>

                    <div className="LinkedAccountsPage-content">
                        {this.activeTab === 'settings' ? this.settingsContent() : this.logsContent()}
                    </div>
                </div>
            </div>
        );
    }

    settingsContent() {
        return (
            <div className="LinkedAccountsPage-settings">
                <div className="Form">
                    <div className="Form-group">
                        <label>{app.translator.trans('ralkage-linked-accounts.admin.settings.max_accounts')}</label>
                        <input
                            type="number"
                            className="FormControl"
                            min="0"
                            bidi={this.setting('ralkage-linked-accounts.max_accounts')}
                        />
                        <p className="helpText">
                            {app.translator.trans('ralkage-linked-accounts.admin.settings.max_accounts_help')}
                        </p>
                    </div>

                    <div className="Form-group">
                        <label>{app.translator.trans('ralkage-linked-accounts.admin.settings.log_retention_days')}</label>
                        <input
                            type="number"
                            className="FormControl"
                            min="0"
                            bidi={this.setting('ralkage-linked-accounts.log_retention_days')}
                        />
                        <p className="helpText">
                            {app.translator.trans('ralkage-linked-accounts.admin.settings.log_retention_days_help')}
                        </p>
                    </div>

                    {this.submitButton()}
                </div>
            </div>
        );
    }

    logsContent() {
        return (
            <div className="LinkedAccountsPage-logs">
                <div className="LinkedAccountsPage-logs-header">
                    <h3>
                        <i className="fas fa-history"></i> {app.translator.trans('ralkage-linked-accounts.admin.logs.title')}
                    </h3>
                    {this.logs.length > 0 && (
                        <Button
                            className="Button Button--danger"
                            icon="fas fa-trash"
                            onclick={() => this.clearLogs()}
                        >
                            {app.translator.trans('ralkage-linked-accounts.admin.logs.clear')}
                        </Button>
                    )}
                </div>

                {this.logsLoading ? (
                    <LoadingIndicator />
                ) : this.logs.length === 0 ? (
                    <div className="LinkedAccountsPage-empty">
                        <i className="fas fa-clipboard-list"></i>
                        <p>{app.translator.trans('ralkage-linked-accounts.admin.logs.empty')}</p>
                    </div>
                ) : (
                    <div>
                        <table className="LinkedAccountsPage-logs-table">
                            <thead>
                                <tr>
                                    <th>{app.translator.trans('ralkage-linked-accounts.admin.logs.parent')}</th>
                                    <th>{app.translator.trans('ralkage-linked-accounts.admin.logs.child')}</th>
                                    <th>{app.translator.trans('ralkage-linked-accounts.admin.logs.action')}</th>
                                    <th>{app.translator.trans('ralkage-linked-accounts.admin.logs.ip')}</th>
                                    <th>{app.translator.trans('ralkage-linked-accounts.admin.logs.date')}</th>
                                </tr>
                            </thead>
                            <tbody>
                                {this.logs.map(log => (
                                    <tr key={log.id}>
                                        <td>
                                            <span className="LogUser">
                                                {log.parentUser ? log.parentUser.displayName || log.parentUser.username : '#' + log.parentUserId}
                                            </span>
                                        </td>
                                        <td>
                                            <span className="LogUser">
                                                {log.childUser ? log.childUser.displayName || log.childUser.username : '#' + log.childUserId}
                                            </span>
                                        </td>
                                        <td>
                                            <span className={'LogAction LogAction--' + log.action}>{log.action}</span>
                                        </td>
                                        <td className="LogIp">{log.ipAddress || '\u2014'}</td>
                                        <td className="LogDate">{new Date(log.createdAt).toLocaleString()}</td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>

                        {this.logsTotal > (this.logsPage + 1) * 50 && (
                            <div className="LinkedAccountsPage-logs-loadMore">
                                <Button className="Button" onclick={() => this.loadMoreLogs()}>
                                    {app.translator.trans('ralkage-linked-accounts.admin.logs.load_more')}
                                </Button>
                            </div>
                        )}
                    </div>
                )}
            </div>
        );
    }

    loadLogs() {
        if (this.logsLoading) return;
        this.logsLoading = true;
        this.logsPage = 0;

        app.request({
            method: 'GET',
            url: app.forum.attribute('apiUrl') + '/linked-account-logs',
            params: { 'page[offset]': 0, 'page[limit]': 50 },
        }).then(response => {
            this.logs = this.parseLogResponse(response);
            this.logsTotal = response.meta?.total || 0;
            this.logsLoading = false;
            m.redraw();
        }).catch(() => {
            this.logsLoading = false;
            m.redraw();
        });
    }

    loadMoreLogs() {
        this.logsPage++;
        app.request({
            method: 'GET',
            url: app.forum.attribute('apiUrl') + '/linked-account-logs',
            params: { 'page[offset]': this.logsPage * 50, 'page[limit]': 50 },
        }).then(response => {
            this.logs = [...this.logs, ...this.parseLogResponse(response)];
            m.redraw();
        });
    }

    parseLogResponse(response) {
        return (response.data || []).map(item => ({
            id: item.id,
            ...item.attributes,
            parentUser: this.findIncluded(response, item.relationships?.parentUser?.data),
            childUser: this.findIncluded(response, item.relationships?.childUser?.data),
        }));
    }

    findIncluded(response, ref) {
        if (!ref || !response.included) return null;
        const found = response.included.find(i => i.type === ref.type && String(i.id) === String(ref.id));
        return found ? { id: found.id, ...found.attributes } : null;
    }

    clearLogs() {
        if (!confirm(app.translator.trans('ralkage-linked-accounts.admin.logs.clear_confirm'))) return;

        app.request({
            method: 'DELETE',
            url: app.forum.attribute('apiUrl') + '/linked-account-logs',
        }).then(() => {
            this.logs = [];
            this.logsTotal = 0;
            m.redraw();
        });
    }
}
