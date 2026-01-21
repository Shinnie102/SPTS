(() => {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const step1Meta = document.querySelector('meta[name="route-admin-lophoc-api-create-step1-get"]');
    const step2Meta = document.querySelector('meta[name="route-admin-lophoc-api-create-step2"]');
    const lecturersMeta = document.querySelector('meta[name="route-admin-lophoc-api-lecturers"]');
    const studentsMeta = document.querySelector('meta[name="route-admin-lophoc-api-students"]');
    const facultiesMeta = document.querySelector('meta[name="route-admin-lophoc-api-faculties"]');
    const majorsByFacultyMeta = document.querySelector('meta[name="route-admin-lophoc-api-majors-by-faculty"]');

    if (!step1Meta || !step2Meta || !lecturersMeta || !studentsMeta || !facultiesMeta || !majorsByFacultyMeta) return;

    const step1Url = step1Meta.content;
    const step2Url = step2Meta.content;
    const lecturersUrl = lecturersMeta.content;
    const studentsUrl = studentsMeta.content;
    const facultiesUrl = facultiesMeta.content;
    const majorsByFacultyUrl = majorsByFacultyMeta.content;

    const lecturerSelect = document.getElementById('giangvien');
    const facultySelect = document.getElementById('khoa');
    const majorSelect = document.getElementById('chuyennganh');
    const studentTableBody = document.querySelector('.student-table tbody');
    const selectedCountSpan = document.getElementById('dachon');
    const searchInput = document.getElementById('search-sinhvien');

    async function fetchJson(url) {
        const res = await fetch(url, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
            }
        });
        return res.json();
    }

    function updateSummary(step1) {
        const summary = document.querySelectorAll('#right .ketqua');
        if (!summary.length || !step1) return;
        summary[0].textContent = step1.academic_year_name || 'Chưa chọn';
        summary[1].textContent = step1.semester_name || 'Chưa chọn';
        summary[2].textContent = step1.faculty_name || 'Chưa chọn';
        summary[3].textContent = step1.major_name || 'Chưa chọn';
        summary[4].textContent = step1.course_name || 'Chưa chọn';
        summary[5].textContent = step1.class_code || 'Chưa nhập';
        summary[6].textContent = step1.time_slot_label || 'Chưa chọn';
        summary[7].textContent = step1.meeting_dates ? step1.meeting_dates.length + ' ngày được chọn' : 'Chưa chọn';
        summary[8].textContent = step1.room_name || 'Chưa chọn';
        summary[9].textContent = step1.capacity ? step1.capacity : 'Chưa nhập';
    }

    async function loadStep1() {
        const json = await fetchJson(step1Url);
        if (json.success && json.data) {
            updateSummary(json.data);
            return json.data;
        }
        alert('Chưa có dữ liệu bước 1. Vui lòng nhập lại.');
        window.location.href = '/admin/lop-hoc/tao-buoc-1';
        return null;
    }

    async function loadLecturers() {
        const json = await fetchJson(lecturersUrl);
        if (!json.success) return;
        lecturerSelect.innerHTML = '<option value="">-- Chọn giảng viên --</option>';
        json.data.forEach(item => {
            const opt = document.createElement('option');
            opt.value = item.id;
            opt.textContent = `${item.full_name} (${item.code_user || ''})`;
            lecturerSelect.appendChild(opt);
        });
    }

    async function loadFaculties() {
        const json = await fetchJson(facultiesUrl);
        if (!json.success) return;
        facultySelect.innerHTML = '<option value="">Tất cả Khoa/Viên</option>';
        json.data.forEach(item => {
            const opt = document.createElement('option');
            opt.value = item.id;
            opt.textContent = item.name;
            facultySelect.appendChild(opt);
        });
    }

    async function loadMajorsByFaculty(facultyId = 0) {
        const url = new URL(majorsByFacultyUrl, window.location.origin);
        if (facultyId) url.searchParams.set('faculty_id', facultyId);
        const json = await fetchJson(url.toString());
        if (!json.success) return;
        
        majorSelect.innerHTML = '<option value="">Tất cả Chuyên ngành</option>';
        json.data.forEach(item => {
            const opt = document.createElement('option');
            opt.value = item.id;
            opt.textContent = item.name;
            majorSelect.appendChild(opt);
        });

        // Reset filter khi đổi khoa
        await filterStudents();
    }

    async function loadStudents(keyword = '') {
        const url = new URL(studentsUrl, window.location.origin);
        if (keyword) url.searchParams.set('keyword', keyword);
        const json = await fetchJson(url.toString());
        if (!json.success) return;
        renderStudents(json.data || []);
    }

    async function filterStudents() {
        const keyword = searchInput?.value?.trim() || '';
        const facultyId = facultySelect?.value || '';
        const majorId = majorSelect?.value || '';

        // Lấy tất cả sinh viên
        const json = await fetchJson(studentsUrl);
        if (!json.success) return;

        let filtered = json.data || [];

        // Lọc theo keyword
        if (keyword) {
            filtered = filtered.filter(s => 
                (s.full_name || '').toLowerCase().includes(keyword.toLowerCase()) ||
                (s.code_user || '').toLowerCase().includes(keyword.toLowerCase())
            );
        }

        // Lọc theo faculty (qua major)
        if (facultyId) {
            const majorsByFaculty = await fetchJson(
                new URL(majorsByFacultyUrl, window.location.origin)
                    .toString() + '?faculty_id=' + facultyId
            );
            if (majorsByFaculty.success) {
                const majorIds = majorsByFaculty.data.map(m => m.id);
                filtered = filtered.filter(s => majorIds.includes(s.major_id || 0));
            }
        }

        // Lọc theo major
        if (majorId) {
            filtered = filtered.filter(s => (s.major_id || 0).toString() === majorId.toString());
        }

        renderStudents(filtered);
    }

    function renderStudents(list) {
        if (!studentTableBody) return;
        studentTableBody.innerHTML = '';
        list.forEach(student => {
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td><input type="checkbox" data-id="${student.id}"></td>
                <td>${student.code_user || ''}</td>
                <td>${student.full_name || ''}</td>
                <td>${student.major_name || student.major || ''}</td>
                <td>${student.major_name || student.major || ''}</td>
            `;
            studentTableBody.appendChild(tr);
        });
        bindSelection();
        updateSelectedCount();
    }

    function bindSelection() {
        const checkboxes = studentTableBody.querySelectorAll('input[type="checkbox"]');
        const headerCheckbox = document.querySelector('.student-table thead input[type="checkbox"]');

        // Khi click checkbox trong header
        if (headerCheckbox) {
            headerCheckbox.addEventListener('change', function () {
                checkboxes.forEach(cb => cb.checked = this.checked);
                updateSelectedCount();
            });
        }

        // Khi click checkbox trong body
        checkboxes.forEach(cb => {
            cb.addEventListener('change', function () {
                // Cập nhật trạng thái header checkbox
                if (headerCheckbox) {
                    const allChecked = Array.from(checkboxes).every(c => c.checked);
                    const someChecked = Array.from(checkboxes).some(c => c.checked);
                    headerCheckbox.checked = allChecked;
                    headerCheckbox.indeterminate = someChecked && !allChecked;
                }
                updateSelectedCount();
            });
        });
    }

    function updateSelectedCount() {
        const selected = studentTableBody.querySelectorAll('input[type="checkbox"]:checked');
        const total = studentTableBody.querySelectorAll('input[type="checkbox"]').length;
        if (selectedCountSpan) selectedCountSpan.textContent = `${selected.length}/${total}`;
    }

    async function submitForm(capacityLimit) {
        const selected = Array.from(studentTableBody.querySelectorAll('input[type="checkbox"]:checked'))
            .map(cb => Number(cb.dataset.id));
        if (!selected.length) {
            alert('Vui lòng chọn sinh viên');
            return;
        }
        if (capacityLimit && selected.length > capacityLimit) {
            alert('Số lượng sinh viên vượt quá sức chứa');
            return;
        }
        const lecturerId = lecturerSelect?.value;
        if (!lecturerId) {
            alert('Vui lòng chọn giảng viên');
            return;
        }

        const res = await fetch(step2Url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                lecturer_id: Number(lecturerId),
                student_ids: selected,
            })
        });
        const json = await res.json();
        if (res.ok && json.success) {
            alert('Tạo lớp học thành công');
            window.location.href = '/admin/lop-hoc';
        } else {
            alert(json.message || 'Tạo lớp thất bại');
        }
    }

    document.addEventListener('DOMContentLoaded', async () => {
        const step1 = await loadStep1();
        await loadLecturers();
        await loadFaculties();
        await loadMajorsByFaculty();
        await loadStudents();

        // Event listeners
        facultySelect?.addEventListener('change', function () {
            loadMajorsByFaculty(this.value);
        });

        majorSelect?.addEventListener('change', filterStudents);

        searchInput?.addEventListener('input', filterStudents);

        document.getElementById('hoantat')?.addEventListener('click', () => submitForm(step1?.capacity));
        document.getElementById('quaylai')?.addEventListener('click', () => {
            window.location.href = '/admin/lop-hoc/tao-buoc-1';
        });
    });
})();