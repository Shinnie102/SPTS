document.querySelectorAll(".status").forEach(select => {
    select.addEventListener("change", () => {
        select.classList.remove("studying", "stopped", "warning");

        if (select.value === "study") {
            select.classList.add("studying");
        } else {
            select.classList.add("stopped");
        }
    });
});
