document.addEventListener("DOMContentLoaded", () => {
    fetch("/student/dashboard/api/data")
        .then(res => res.json())
        .then(data => {
            const overview = data.overview;

            document.getElementById("value-gpa-total").innerText =
                overview.gpaTotal ?? "--";

            document.getElementById("value-gpa-semester").innerText =
                overview.gpaSemester ?? "--";

            document.getElementById("value-credit").innerText =
                overview.totalCredits ?? "--";

            document.getElementById("value-attendance").innerText =
                (overview.attendanceRate ?? "--") + "%";
        })
        .catch(err => {
            console.error("Dashboard API error:", err);
        });
});
