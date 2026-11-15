document.addEventListener('DOMContentLoaded', () => {
    const harvestQuantity = document.querySelector('#quantity_kg');
    const sellingPrice = document.querySelector('#selling_price_per_kg');
    const harvestEstimate = document.querySelector('[data-harvest-estimate]');
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
    updateHarvestEstimate();
});
