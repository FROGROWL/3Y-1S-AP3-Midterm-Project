document.addEventListener('DOMContentLoaded', () => {
    const root = document.documentElement;
    const menuToggles = document.querySelectorAll('.navbar-burger');
    const themeToggles = document.querySelectorAll('.theme-toggle');

    const getSavedTheme = () => {
        try {
            return localStorage.getItem('agri-info-theme');
        } catch {
            return null;
        }
    };

    const setTheme = (theme) => {
        const isDark = theme === 'dark';
        root.dataset.theme = isDark ? 'dark' : 'light';

        themeToggles.forEach((toggle) => {
            const label = toggle.querySelector('[data-theme-label]');
            const icon = toggle.querySelector('[data-theme-icon]');
            const nextTheme = isDark ? 'light' : 'dark';
            const actionLabel = `Switch to ${nextTheme} theme`;

            toggle.setAttribute('aria-label', actionLabel);
            toggle.setAttribute('title', actionLabel);
            toggle.setAttribute('aria-pressed', String(isDark));

            if (label) {
                label.textContent = isDark ? 'Light theme' : 'Dark theme';
            }

            if (icon) {
                icon.textContent = isDark ? '\u2600' : '\u263e';
            }
        });
    };

    const savedTheme = getSavedTheme();
    const initialTheme = savedTheme === 'dark' || savedTheme === 'light'
        ? savedTheme
        : (window.matchMedia?.('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');

    setTheme(initialTheme);

    themeToggles.forEach((toggle) => {
        toggle.addEventListener('click', () => {
            const nextTheme = root.dataset.theme === 'dark' ? 'light' : 'dark';
            setTheme(nextTheme);

            try {
                localStorage.setItem('agri-info-theme', nextTheme);
            } catch {
                // Theme still works for this page if browser storage is unavailable.
            }
        });
    });

    menuToggles.forEach((toggle) => {
        toggle.addEventListener('click', () => {
            toggle.classList.toggle('is-active');

            const menu = document.getElementById(toggle.dataset.target);
            const isExpanded = menu?.classList.toggle('is-active') ?? false;
            toggle.setAttribute('aria-expanded', String(isExpanded));
        });
    });

    const plantingSelect = document.querySelector('[data-planting-select]');
    const plantingDetails = document.querySelector('[data-planting-details]');

    if (plantingSelect && plantingDetails) {
        const detailFields = plantingDetails.querySelectorAll('[data-planting-value]');
        const dataKeys = {
            crop: 'plantingCrop',
            farm: 'plantingFarm',
            planted: 'plantingPlanted',
            expected: 'plantingExpected',
            status: 'plantingStatus',
        };

        const updatePlantingDetails = () => {
            const selectedOption = plantingSelect.selectedOptions[0];

            if (!selectedOption?.value) {
                plantingDetails.hidden = true;
                detailFields.forEach((field) => {
                    field.value = '';
                });
                return;
            }

            detailFields.forEach((field) => {
                field.value = selectedOption.dataset[dataKeys[field.dataset.plantingValue]] || '';
            });
            plantingDetails.hidden = false;
        };

        plantingSelect.addEventListener('change', updatePlantingDetails);
        updatePlantingDetails();
    }

    const farmSelect = document.querySelector('#farm_id');
    const farmDetails = document.querySelector('[data-farm-details]');
    const areaInput = document.querySelector('#area_planted');
    const plantingStatusForArea = document.querySelector('[data-planting-status-select]');
    if (farmSelect && farmDetails) {
        const farmFields = farmDetails.querySelectorAll('[data-farm-value]');
        const updateFarmDetails = () => {
            const option = farmSelect.selectedOptions[0];
            if (!option?.value) {
                farmDetails.hidden = true;
                farmFields.forEach((field) => { field.value = ''; });
                return;
            }
            const total = Number(option.dataset.farmArea || 0);
            const used = Number(option.dataset.farmUsed || 0);
            const statusCode = plantingStatusForArea?.selectedOptions[0]?.dataset.statusCode;
            const reservedArea = ['planted', 'growing', 'ready'].includes(statusCode)
                ? Number(areaInput?.value || 0)
                : 0;
            const values = {
                location: option.dataset.farmLocation || '',
                area: total.toFixed(2),
                soil: option.dataset.farmSoil || '',
                irrigation: option.dataset.farmIrrigation || '',
                remaining: Math.max(0, total - used - reservedArea).toFixed(2),
            };
            farmFields.forEach((field) => { field.value = values[field.dataset.farmValue] || ''; });
            farmDetails.hidden = false;
        };
        farmSelect.addEventListener('change', updateFarmDetails);
        plantingStatusForArea?.addEventListener('change', updateFarmDetails);
        areaInput?.addEventListener('input', updateFarmDetails);
        updateFarmDetails();
    }

    const varietySelect = document.querySelector('#variety_id');
    const plantingDate = document.querySelector('#planting_date');
    const expectedDate = document.querySelector('[data-expected-harvest-date]');
    const statusSelect = document.querySelector('[data-planting-status-select]');
    const updatePlantingSchedule = () => {
        if (!varietySelect || !plantingDate || !expectedDate) return;
        const days = Number(varietySelect.selectedOptions[0]?.dataset.growingDays || 0);
        const start = plantingDate.value ? new Date(`${plantingDate.value}T00:00:00`) : null;
        if (!days || !start || Number.isNaN(start.getTime())) {
            expectedDate.value = '';
            if (statusSelect) statusSelect.disabled = true;
            return;
        }
        const harvest = new Date(start);
        harvest.setDate(harvest.getDate() + days);
        expectedDate.value = `${harvest.getFullYear()}-${String(harvest.getMonth() + 1).padStart(2, '0')}-${String(harvest.getDate()).padStart(2, '0')}`;
        if (statusSelect) {
            const today = new Date();
            today.setHours(0, 0, 0, 0);
            const futurePlanting = start > today;
            const daysUntilHarvest = Math.ceil((harvest - today) / 86400000);
            const allowed = futurePlanting ? ['planted', 'cancelled']
                : daysUntilHarvest > 20 ? ['planted', 'growing', 'cancelled']
                    : ['ready', 'harvested', 'cancelled'];
            let selectedAllowed = false;
            Array.from(statusSelect.options).forEach((option) => {
                const visible = !option.value || allowed.includes(option.dataset.statusCode);
                option.hidden = !visible;
                option.disabled = !visible;
                if (option.selected && visible) selectedAllowed = true;
            });
            if (!selectedAllowed) statusSelect.value = '';
            statusSelect.disabled = false;
        }
    };
    varietySelect?.addEventListener('change', updatePlantingSchedule);
    plantingDate?.addEventListener('change', updatePlantingSchedule);
    updatePlantingSchedule();
});
