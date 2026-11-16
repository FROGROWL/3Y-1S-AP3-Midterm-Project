const pageContent = document.getElementById('page-content');

if (pageContent) {
    let activeRequest;

    const syncNotice = (nextDocument) => {
        document.querySelectorAll('[data-app-notice]').forEach((notice) => notice.remove());
        const nextNotice = nextDocument.querySelector('[data-app-notice]');
        if (nextNotice) {
            document.body.append(nextNotice.cloneNode(true));
            window.initializeAppNotices?.();
        }
    };

    const loadPage = async (url, addHistory = true, paginationIndex = null, request = {}) => {
        activeRequest?.abort();
        activeRequest = new AbortController();
        pageContent.setAttribute('aria-busy', 'true');
        const loadingNotice = document.createElement('div');
        loadingNotice.className = 'app-page-loading';
        loadingNotice.setAttribute('role', 'status');
        loadingNotice.setAttribute('aria-live', 'polite');
        loadingNotice.textContent = request.method === 'POST' ? 'Saving changes…' : 'Updating page…';
        document.body.append(loadingNotice);

        try {
            const response = await fetch(url, {
                ...request,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    ...(request.headers || {}),
                },
                signal: activeRequest.signal,
            });

            if (response.redirected && new URL(response.url).searchParams.get('page') === 'login') {
                window.location.assign(response.url);
                return;
            }

            const responseDocument = new DOMParser().parseFromString(
                await response.text(),
                'text/html',
            );
            const nextPageContent = responseDocument.getElementById('page-content');

            if (!nextPageContent) {
                throw new Error(`Page request failed with status ${response.status}`);
            }

            pageContent.innerHTML = nextPageContent.innerHTML;
            syncNotice(responseDocument);
            pageContent.removeAttribute('aria-busy');
            document.dispatchEvent(new Event('agri:page-updated'));

            const finalUrl = request.method === 'POST' ? response.url : url;
            if (addHistory) window.history.pushState({}, '', finalUrl);

            const nextTitle = responseDocument.querySelector('title')?.textContent;
            if (nextTitle) document.title = nextTitle;

            if (paginationIndex !== null) {
                pageContent.querySelectorAll('.pagination')[paginationIndex]?.scrollIntoView({
                    block: 'nearest',
                    behavior: 'smooth',
                });
            }
        } catch (error) {
            if (error.name !== 'AbortError') {
                window.location.assign(request.method === 'POST' ? window.location.href : url);
            }
        } finally {
            pageContent.removeAttribute('aria-busy');
            loadingNotice.remove();
        }
    };

    const isInternalPageUrl = (url) => {
        const destination = new URL(url, window.location.href);
        const currentPath = window.location.pathname.endsWith('/')
            ? `${window.location.pathname}index.php`
            : window.location.pathname;
        const destinationPath = destination.pathname.endsWith('/')
            ? `${destination.pathname}index.php`
            : destination.pathname;
        return destination.origin === window.location.origin
            && destinationPath === currentPath
            && destinationPath.endsWith('/index.php');
    };

    document.addEventListener('click', (event) => {
        const link = event.target instanceof Element ? event.target.closest('a[href]') : null;
        if (!link || link.target || link.hasAttribute('download')) return;
        if (link.getAttribute('href')?.startsWith('#')) return;

        const destination = new URL(link.href, window.location.href);
        if (!isInternalPageUrl(destination.href)) return;
        if (link.hasAttribute('disabled') || link.getAttribute('aria-disabled') === 'true') return;
        if (destination.href === window.location.href) return;

        event.preventDefault();
        const paginationNavs = Array.from(pageContent.querySelectorAll('.pagination'));
        const paginationIndex = paginationNavs.indexOf(link.closest('.pagination'));
        loadPage(destination.href, true, paginationIndex >= 0 ? paginationIndex : null);
    });

    document.addEventListener('submit', (event) => {
        if (event.defaultPrevented) return;
        const form = event.target instanceof HTMLFormElement ? event.target : null;
        if (!form || !pageContent.contains(form)) return;
        if (form.matches('[action*="page=logout"]')) return;

        const method = (form.method || 'GET').toUpperCase();
        const action = new URL(form.action || window.location.href, window.location.href);
        if (!isInternalPageUrl(action.href)) return;
        if (action.searchParams.get('page') === 'login' || action.searchParams.get('page') === 'logout') return;

        event.preventDefault();
        if (method === 'GET') {
            const query = new URLSearchParams(new FormData(form, event.submitter));
            action.search = query.toString();
            loadPage(action.href);
            return;
        }

        const formData = new FormData(form, event.submitter);
        loadPage(action.href, true, null, { method, body: formData });
    });

    window.addEventListener('popstate', () => loadPage(window.location.href, false));
}
