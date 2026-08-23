document.querySelectorAll("[data-tabs]").forEach(tabs => {
    const buttons = tabs.querySelectorAll("[role='tab']");
    const panels = tabs.querySelectorAll("[role='tabpanel']");

    buttons.forEach(button => {
        button.addEventListener("click", () => {
            buttons.forEach(tab => {
                const active = tab === button;
                tab.classList.toggle("is-active", active);
                tab.setAttribute("aria-selected", active);
                tab.tabIndex = active ? 0 : -1;
            });

            panels.forEach(panel => {
                panel.hidden =
                    panel.id !== button.getAttribute("aria-controls");
                panel.classList.toggle("is-active", !panel.hidden);
            });
        });
    });
});
