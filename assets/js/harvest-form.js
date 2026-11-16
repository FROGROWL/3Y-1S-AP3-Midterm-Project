const initializeHarvestForm = () => {
    const harvestQuantity = document.querySelector('#quantity_kg');
    const sellingPrice = document.querySelector('#selling_price_per_kg');
    const harvestEstimate = document.querySelector('[data-harvest-estimate]');
    const plantingSelect = document.querySelector('[data-planting-select]');
    const harvestDate = document.querySelector('[data-harvest-date]');
    const updateHarvestDateMinimum = () => {
        if (!plantingSelect || !harvestDate) return;
        const plantingDate = plantingSelect.selectedOptions[0]?.dataset.plantingDate;
        if (!plantingDate) {
            harvestDate.removeAttribute('min');
            return;
        }
        const earliestHarvest = new Date(`${plantingDate}T00:00:00`);
        earliestHarvest.setDate(earliestHarvest.getDate() + 1);
        harvestDate.min = [
            earliestHarvest.getFullYear(),
            String(earliestHarvest.getMonth() + 1).padStart(2, '0'),
            String(earliestHarvest.getDate()).padStart(2, '0'),
        ].join('-');
    };
    const updateHarvestEstimate = () => {
        if (!harvestQuantity || !sellingPrice || !harvestEstimate) return;
        const quantity = Number(harvestQuantity.value);
        const price = Number(sellingPrice.value);
        harvestEstimate.value = harvestQuantity.value !== '' && sellingPrice.value !== ''
            && Number.isFinite(quantity) && Number.isFinite(price)
            ? (quantity * price).toFixed(2)
            : '';
    };
    harvestQuantity?.addEventListener('input', updateHarvestEstimate);
    sellingPrice?.addEventListener('input', updateHarvestEstimate);
    plantingSelect?.addEventListener('change', updateHarvestDateMinimum);
    updateHarvestDateMinimum();
    updateHarvestEstimate();
};

document.addEventListener('DOMContentLoaded', initializeHarvestForm, { once: true });
document.addEventListener('agri:page-updated', initializeHarvestForm);
