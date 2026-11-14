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
});
