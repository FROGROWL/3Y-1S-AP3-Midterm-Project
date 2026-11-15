const pageContent = document.getElementById('page-content');

if (pageContent) {
    let activeRequest;

    const loadPage = async (url, addHistory = true, paginationIndex = null) => {
        activeRequest?.abort();
        activeRequest = new AbortController();
        pageContent.setAttribute('aria-busy', 'true');

        try {
            const response = await fetch(url, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                signal: activeRequest.signal,
            });

            if (!response.ok) {
                throw new Error(`Page request failed with status ${response.status}`);
            }

            if (response.redirected && new URL(response.url).searchParams.get('page') === 'login') {
                window.location.assign(response.url);
                return;
            }

            const documentFragment = new DOMParser().parseFromString(
                await response.text(),
                'text/html',
            );
            const nextPageContent = documentFragment.getElementById('page-content');

            if (!nextPageContent) {
                throw new Error('The requested page did not contain its content region.');
            }

            pageContent.innerHTML = nextPageContent.innerHTML;
            pageContent.removeAttribute('aria-busy');
            document.dispatchEvent(new Event('agri:page-updated'));

            if (addHistory) {
                window.history.pushState({}, '', url);
            }

            if (paginationIndex !== null) {
                pageContent.querySelectorAll('.pagination')[paginationIndex]?.scrollIntoView({
                    block: 'nearest',
                    behavior: 'smooth',
                });
            }
        } catch (error) {
            if (error.name !== 'AbortError') {
                window.location.assign(url);
            }
        } finally {
            pageContent.removeAttribute('aria-busy');
        }
    };

    document.addEventListener('click', (event) => {
        const link = event.target instanceof Element
            ? event.target.closest('.pagination a[href]')
            : null;

        if (!link || link.hasAttribute('disabled') || link.getAttribute('aria-disabled') === 'true') {
            return;
        }

        const destination = new URL(link.href, window.location.href);
        if (destination.origin !== window.location.origin) {
            return;
        }

        event.preventDefault();
        const paginationNavs = Array.from(pageContent.querySelectorAll('.pagination'));
        const paginationIndex = paginationNavs.indexOf(link.closest('.pagination'));
        loadPage(destination.href, true, paginationIndex);
    });

    window.addEventListener('popstate', () => {
        loadPage(window.location.href, false);
    });
}
