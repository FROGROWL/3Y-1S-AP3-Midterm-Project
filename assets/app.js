document.addEventListener('DOMContentLoaded', () => {
    const menuToggles = document.querySelectorAll('.navbar-burger');

    menuToggles.forEach((toggle) => {
        toggle.addEventListener('click', () => {
            toggle.classList.toggle('is-active');

            const menu = document.getElementById(toggle.dataset.target);
            menu?.classList.toggle('is-active');
        });
    });
});
