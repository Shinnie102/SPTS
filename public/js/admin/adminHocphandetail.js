// Xử lý thay đổi màu select enrollment status
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

// ====== Data loading for admin Chi tiết Lớp học page ======
(function () {
    const csrfTokenMeta = document.querySelector('meta[name="csrf-token"]');
    const apiShowRouteMeta = document.querySelector('meta[name="route-admin-lophoc-api-show"]');
    const deleteEnrollmentRouteMeta = document.querySelector('meta[name="route-admin-lophoc-api-delete-enrollment"]');
    const classSectionIdMeta = document.querySelector('meta[name="class-section-id"]');

    if (!apiShowRouteMeta || !classSectionIdMeta || !deleteEnrollmentRouteMeta) {
        console.error('Missing meta tags for API route or class section ID');
        return;
    }

    const csrfToken = csrfTokenMeta ? csrfTokenMeta.getAttribute('content') : '';
    const apiShowRoute = apiShowRouteMeta.getAttribute('content');
    const deleteEnrollmentRoute = deleteEnrollmentRouteMeta.getAttribute('content');
    const classSectionId = classSectionIdMeta.getAttribute('content');

    if (!classSectionId || classSectionId === '__ID__') {
        console.error('Invalid class section ID');
        return;
    }

    // Replace __ID__ placeholder with actual ID
    const apiUrl = apiShowRoute.replace('__ID__', classSectionId);

    // State
    let allStudents = [];
    let currentPage = 1;
    const studentsPerPage = 50;

    /**
     * Show toast notification
     */
    function showToast(message, type = 'success') {
        const toast = document.getElementById('toast');
        if (!toast) return;
        
        const isSuccess = type === 'success';
        toast.style.backgroundColor = isSuccess ? '#2e7d32' : '#c62828';
        toast.textContent = message;
        toast.style.display = 'block';
        
        clearTimeout(showToast.timer);
        showToast.timer = setTimeout(() => {
            toast.style.display = 'none';
        }, 3000);
    }

    /**
     * Load class section detail from API
     */
    async function loadClassDetail() {
        try {
            const response = await fetch(apiUrl, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                }
            });

            if (!response.ok) {
                throw new Error('Failed to load class detail');
            }

            const json = await response.json();
            
            if (json.success && json.data) {
                renderClassInfo(json.data);
                allStudents = json.data.students || [];
                renderStudents(1);
            } else {
                showToast(json.message || 'Không thể tải dữ liệu', 'error');
            }
        } catch (err) {
            console.error('Error loading class detail:', err);
            showToast('Lỗi khi tải dữ liệu lớp học', 'error');
        }
    }

    /**
     * Render class information
     */
    function renderClassInfo(data) {
        // Basic info
        document.getElementById('class-code').textContent = data.class_code || 'N/A';
        document.getElementById('academic-year').textContent = data.academic_year_name || 'N/A';
        document.getElementById('course-name').textContent = data.course_name || 'N/A';
        document.getElementById('semester').textContent = data.semester_name || 'N/A';
        document.getElementById('faculty').textContent = data.faculty_name || 'N/A';
        document.getElementById('status').textContent = data.status_name || 'N/A';
        document.getElementById('major').textContent = data.major_name || 'N/A';
        document.getElementById('lecturer').textContent = data.lecturer_name || 'N/A';
        
        // Schedule info
        document.getElementById('time-slot').textContent = data.time_slot_label || 'Chưa có lịch';
        document.getElementById('schedule').textContent = data.schedule_label || 'Chưa có lịch';
        document.getElementById('room').textContent = data.room_label || 'Chưa có phòng';
        
        // Capacity info
        const capacity = data.capacity || 0;
        const currentStudents = data.current_students || 0;
        document.getElementById('capacity').textContent = `${capacity} sinh viên`;
        document.getElementById('current-students').textContent = `${currentStudents} sinh viên`;
        document.getElementById('grading-scheme').textContent = data.grading_scheme_name || 'Chưa có sơ đồ điểm';
        
        // Update student count in table header
        document.getElementById('student-count').textContent = currentStudents;
    }

    /**
     * Render students table with pagination
     */
    function renderStudents(page) {
        currentPage = page;
        
        const start = (page - 1) * studentsPerPage;
        const end = start + studentsPerPage;
        const studentsToShow = allStudents.slice(start, end);
        
        const tbody = document.getElementById('student-table-body');
        if (!tbody) return;
        
        if (studentsToShow.length === 0) {
            tbody.innerHTML = '<tr><td colspan="8" style="text-align: center; padding: 20px;">Không có sinh viên nào</td></tr>';
            renderPagination(0);
            return;
        }
        
        tbody.innerHTML = '';
        
        studentsToShow.forEach((student, index) => {
            const stt = start + index + 1;
            const row = document.createElement('tr');
            
            row.innerHTML = `
                <td>${stt}</td>
                <td>${student.student_code || 'N/A'}</td>
                <td>${student.student_name || 'N/A'}</td>
                <td>${student.faculty_name || 'N/A'}</td>
                <td>${student.major_name || 'N/A'}</td>
                <td class="${student.academic_status_class || 'good'}">${student.academic_status_name || 'N/A'}</td>
                <td>
                    <select class="status ${student.enrollment_class || 'studying'}" data-enrollment-id="${student.enrollment_id}">
                        <option value="1" ${student.enrollment_status_id == 1 ? 'selected' : ''}>Chờ học</option>
                        <option value="2" ${student.enrollment_status_id == 2 ? 'selected' : ''}>Đang học</option>
                        <option value="3" ${student.enrollment_status_id == 3 ? 'selected' : ''}>Đã rút</option>
                        <option value="4" ${student.enrollment_status_id == 4 ? 'selected' : ''}>Đã đạt</option>
                        <option value="5" ${student.enrollment_status_id == 5 ? 'selected' : ''}>Không đạt</option>
                    </select>
                </td>
                <td><i class="fa-solid fa-trash delete" data-enrollment-id="${student.enrollment_id}" data-student-name="${student.student_name}" style="cursor:pointer; color:#c62828;"></i></td>
            `;
            
            tbody.appendChild(row);
        });
        
        // Bind select change event
        bindEnrollmentStatusChange();
        
        // Render pagination
        renderPagination(allStudents.length);
    }

    /**
     * Bind enrollment status change event
     */
    function bindEnrollmentStatusChange() {
        document.querySelectorAll(".status").forEach(select => {
            select.addEventListener("change", function() {
                this.classList.remove("studying", "stopped", "warning", "completed", "failed");
                
                const value = this.value;
                // 1=Chờ học, 2=Đang học, 3=Đã rút, 4=Đã đạt, 5=Không đạt
                if (value == "2") { // Đang học - studying
                    this.classList.add("studying");
                } else if (value == "3") { // Đã rút - stopped
                    this.classList.add("stopped");
                } else if (value == "4") { // Đã đạt - completed
                    this.classList.add("completed");
                } else if (value == "5") { // Không đạt - failed
                    this.classList.add("failed");
                } else { // Chờ học - warning
                    this.classList.add("warning");
                }
                
                // TODO: Call API to update enrollment status
                // const enrollmentId = this.dataset.enrollmentId;
                // updateEnrollmentStatus(enrollmentId, value);
            });
        });
    }

    /**
     * Render pagination controls
     */
    function renderPagination(totalStudents) {
        const totalPages = Math.ceil(totalStudents / studentsPerPage);
        const paginationDiv = document.getElementById('pagination');
        
        if (!paginationDiv) return;
        
        if (totalPages <= 1) {
            paginationDiv.innerHTML = '';
            return;
        }
        
        let html = '';
        
        // Previous button
        html += `<button class="page-btn ${currentPage === 1 ? 'disabled' : ''}" data-page="${currentPage - 1}" ${currentPage === 1 ? 'disabled' : ''}>‹</button>`;
        
        // Page numbers
        const maxVisible = 5;
        let startPage = Math.max(1, currentPage - Math.floor(maxVisible / 2));
        let endPage = Math.min(totalPages, startPage + maxVisible - 1);
        
        if (endPage - startPage < maxVisible - 1) {
            startPage = Math.max(1, endPage - maxVisible + 1);
        }
        
        for (let i = startPage; i <= endPage; i++) {
            html += `<button class="page-btn ${i === currentPage ? 'active' : ''}" data-page="${i}">${i}</button>`;
        }
        
        // Next button
        html += `<button class="page-btn ${currentPage === totalPages ? 'disabled' : ''}" data-page="${currentPage + 1}" ${currentPage === totalPages ? 'disabled' : ''}>›</button>`;
        
        paginationDiv.innerHTML = html;
        
        // Bind pagination click events
        paginationDiv.querySelectorAll('.page-btn:not(.disabled)').forEach(btn => {
            btn.addEventListener('click', function() {
                const page = parseInt(this.dataset.page);
                if (page > 0 && page <= totalPages) {
                    renderStudents(page);
                }
            });
        });
    }

    /**
     * Delete student from class
     */
    async function deleteEnrollment(enrollmentId, studentName) {
        if (!confirm(`Bạn có chắc chắn muốn xóa sinh viên "${studentName}" khỏi lớp học này?`)) {
            return;
        }
        try {
            const url = deleteEnrollmentRoute.replace('__ENROLLMENT__', enrollmentId);
            const response = await fetch(url, {
                method: 'DELETE',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                }
            });
            const json = await response.json();
            if (response.ok && json.success) {
                showToast('Đã xóa sinh viên khỏi lớp', 'success');
                await loadClassDetail();
            } else {
                showToast(json.message || 'Không thể xóa sinh viên', 'error');
            }
        } catch (err) {
            console.error('Error deleting enrollment:', err);
            showToast('Lỗi khi xóa sinh viên', 'error');
        }
    }

    /**
     * Bind delete icon click event
     */
    function bindDeleteEvent() {
        document.getElementById('student-table-body')?.addEventListener('click', function(e) {
            const deleteIcon = e.target.closest('.delete');
            if (deleteIcon) {
                const enrollmentId = deleteIcon.dataset.enrollmentId;
                const studentName = deleteIcon.dataset.studentName;
                deleteEnrollment(enrollmentId, studentName);
            }
        });
    }

    // Initialize
    document.addEventListener('DOMContentLoaded', function() {
        loadClassDetail();
        bindDeleteEvent();
    });
})();

// Add CSS animation for spinner
if (!document.getElementById('spinner-animation')) {
    const style = document.createElement('style');
    style.id = 'spinner-animation';
    style.textContent = `
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    `;
    document.head.appendChild(style);
}
