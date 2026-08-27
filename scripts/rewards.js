document.querySelectorAll(".quantity-control").forEach(control => {
    const input = control.querySelector('input[type="number"]');
    const decreaseButton = control.querySelector('[data-action="decrease"]');
    const increaseButton = control.querySelector('[data-action="increase"]');

    const updateQuantity = change => {
        const minimum = Number(input.min) || 1;
        const maximum = Number(input.max) || Infinity;
        const current = Number(input.value) || minimum;
        input.value = Math.min(maximum, Math.max(minimum, current + change));
    };

    decreaseButton.addEventListener("click", () => updateQuantity(-1));
    increaseButton.addEventListener("click", () => updateQuantity(1));
});
