const dialog = () => document.querySelector('#app-dialog');

const appendText = (parent, tag, text, className = '') => {
    const node = document.createElement(tag);
    if (className) node.className = className;
    node.textContent = text;
    parent.append(node);
    return node;
};

const showDialog = ({ title, message = '', impact = '', confirmLabel = 'Continue', danger = false, details = null }) => {
    const element = dialog();
    if (!element) return Promise.resolve(false);
    const heading = element.querySelector('[data-dialog-title]');
    const content = element.querySelector('[data-dialog-content]');
    const actions = element.querySelector('[data-dialog-actions]');
    heading.textContent = title;
    content.replaceChildren();
    actions.replaceChildren();
    if (message) appendText(content, 'p', message, 'mb-3');
    if (impact) appendText(content, 'p', impact, 'dialog-impact');
    if (details) renderAuditDetails(content, details);

    return new Promise((resolve) => {
        const finish = (value) => {
            if (settled) return;
            settled = true;
            if (element.open) element.close();
            resolve(value);
        };
        let settled = false;
        const cancel = document.createElement('button');
        cancel.type = 'button'; cancel.className = 'button'; cancel.textContent = confirmLabel ? 'Cancel' : 'Close';
        cancel.addEventListener('click', () => finish(false), { once: true });
        actions.append(cancel);
        if (confirmLabel) {
            const accept = document.createElement('button');
            accept.type = 'button'; accept.className = danger ? 'button is-danger' : 'button is-primary';
            accept.textContent = confirmLabel;
            accept.addEventListener('click', () => finish(true), { once: true });
            actions.append(accept);
        }
        element.addEventListener('cancel', (event) => { event.preventDefault(); finish(false); }, { once: true });
        element.addEventListener('close', () => {
            if (!settled) { settled = true; resolve(false); }
        }, { once: true });
        element.showModal();
        (danger ? cancel : actions.lastElementChild)?.focus();
    });
};

const renderAuditDetails = (content, details) => {
    const fields = details.after || details.before || details;
    const section = (label, values) => {
        if (!values || typeof values !== 'object') return;
        appendText(content, 'h3', label, 'title is-6 mt-4 mb-2');
        const table = document.createElement('div'); table.className = 'dialog-detail-list';
        const ignored = /password|secret|token|hash/i;
        Object.entries(values).forEach(([key, value]) => {
            if (ignored.test(key) || value === null || typeof value === 'object') return;
            const row = document.createElement('div'); row.className = 'dialog-detail-row';
            appendText(row, 'strong', key.replaceAll('_', ' '));
            appendText(row, 'span', String(value));
            table.append(row);
        });
        content.append(table);
    };
    if (details.before && details.after) {
        section('Before', details.before);
        section('After', details.after);
    } else if (details.status && typeof details.status === 'object') {
        section('Status change', details.status);
    } else {
        section(details.before ? 'Deleted record details' : 'Record details', fields);
    }
    if (details.affected && Object.values(details.affected).some((count) => Number(count) > 0)) {
        const labels = Object.entries(details.affected).filter(([, count]) => Number(count) > 0)
            .map(([name, count]) => `${count} ${name}`).join(', ');
        appendText(content, 'p', `Also deleted: ${labels}.`, 'dialog-impact mt-4');
    }
    if (Object.hasOwn(details, 'retained_audit_logs')) {
        appendText(content, 'p', `${details.retained_audit_logs} audit log entries were retained; their user association was cleared.`, 'dialog-impact mt-4');
    }
    if (details.sessions_revoked) {
        appendText(content, 'p', 'The deleted account can no longer access the system. Existing sessions are rejected on their next request.', 'dialog-impact mt-4');
    }
};

window.showAppConfirm = (options) => showDialog(options);

document.addEventListener('click', async (event) => {
    const detailButton = event.target.closest('[data-audit-open]');
    if (detailButton) {
        let details = {};
        try { details = JSON.parse(detailButton.dataset.auditDetails || '{}'); } catch { /* malformed historical payload */ }
        await showDialog({ title: detailButton.dataset.auditTitle || 'Audit change details', details, confirmLabel: '' });
    }
    if (event.target.closest('[data-dialog-close]')) dialog()?.close();
    if (event.target.closest('[data-notice-close]')) event.target.closest('[data-app-notice]')?.remove();
});

document.addEventListener('submit', async (event) => {
    const form = event.target.closest('form[data-app-confirm]');
    if (!form || form.dataset.confirmed === '1') return;
    event.preventDefault();
    const ok = await showDialog({
        title: form.dataset.confirmTitle || 'Confirm action',
        message: form.dataset.confirmMessage || 'Continue with this action?',
        impact: form.dataset.confirmImpact || '',
        confirmLabel: form.dataset.confirmLabel || 'Delete',
        danger: true,
    });
    if (!ok || form.dataset.confirmBlocked === '1') return;
    form.dataset.confirmed = '1';
    const marker = form.querySelector('[name="confirm_delete"]');
    if (marker) marker.value = '1';
    form.requestSubmit(event.submitter || undefined);
});

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-app-notice]').forEach((notice) => {
        if (notice.dataset.noticeType !== 'danger') setTimeout(() => notice.remove(), 7000);
    });
});
