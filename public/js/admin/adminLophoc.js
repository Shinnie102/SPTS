document.querySelectorAll(".fake-select").forEach(select => {
    const selected = select.querySelector(".selected");
    const options = select.querySelector(".options");
    const hidden = select.querySelector("input");

    selected.addEventListener("click", () => {
        document.querySelectorAll(".options").forEach(o => {
            if (o !== options) o.style.display = "none";
        });
        options.style.display = options.style.display === "block" ? "none" : "block";
    });

    select.querySelectorAll(".option").forEach(opt => {
        opt.addEventListener("click", () => {
            selected.textContent = opt.textContent;
            hidden.value = opt.dataset.value;
            options.style.display = "none";
        });
    });
});

document.addEventListener("click", e => {
    if (!e.target.closest(".fake-select")) {
        document.querySelectorAll(".options").forEach(o => o.style.display = "none");
    }
});

