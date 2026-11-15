const initializeReportFilters = () => {
    const reportScope = document.querySelector('#scope');
    const reportGrouping = document.querySelector('#group_by');
    const updateReportGrouping = () => {
        if (reportScope && reportGrouping) {
            reportGrouping.disabled = reportScope.value !== 'crops';
        }
    };
    reportScope?.addEventListener('change', updateReportGrouping);
    updateReportGrouping();
};

document.addEventListener('DOMContentLoaded', initializeReportFilters, { once: true });
document.addEventListener('agri:page-updated', initializeReportFilters);
