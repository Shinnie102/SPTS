const rows = document.querySelectorAll(".student-table tbody tr");
const pageBtns = document.querySelectorAll(".page-btn:not(.disabled)");
const rowsPerPage = 5;
let currentPage = 1;

function showPage(page) {
    currentPage = page;
    const start = (page - 1) * rowsPerPage;
    const end = start + rowsPerPage;

    rows.forEach((row, i) => {
        row.style.display = i >= start && i < end ? "" : "none";
    });

    document.querySelectorAll(".page-btn").forEach(btn => btn.classList.remove("active"));
    document.querySelectorAll(".page-btn")[page].classList.add("active");
}

pageBtns.forEach((btn, i) => {
    btn.addEventListener("click", () => showPage(i));
});

showPage(1);