(() => {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const classSectionIdMeta = document.querySelector('meta[name="class-section-id"]');
    const classSectionId = classSectionIdMeta?.content || '';
    const isEditMode = !!classSectionId;
    
    const step1Meta = document.querySelector('meta[name="route-admin-lophoc-api-create-step1-get"]');
    const step2Meta = document.querySelector('meta[name="route-admin-lophoc-api-create-step2"]');
    const updateStep2Meta = document.querySelector('meta[name="route-admin-lophoc-api-update-step2"]');
    const detailMeta = document.querySelector('meta[name="route-admin-lophoc-api-detail"]');
    const lecturersMeta = document.querySelector('meta[name="route-admin-lophoc-api-lecturers"]');
    const studentsMeta = document.querySelector('meta[name="route-admin-lophoc-api-students"]');
    const facultiesMeta = document.querySelector('meta[name="route-admin-lophoc-api-faculties"]');
    const majorsByFacultyMeta = document.querySelector('meta[name="route-admin-lophoc-api-majors-by-faculty"]');

    if (!step1Meta || !step2Meta || !lecturersMeta || !studentsMeta || !facultiesMeta || !majorsByFacultyMeta) return;

    const step1Url = step1Meta.content;
    const step2Url = step2Meta.content;
    const updateStep2Url = updateStep2Meta?.content;
    const detailUrl = detailMeta?.content;
    const lecturersUrl = lecturersMeta.content;
    const studentsUrl = studentsMeta.content;
    const facultiesUrl = facultiesMeta.content;
    const majorsByFacultyUrl = majorsByFacultyMeta.content;

    const lecturerSelect = document.getElementById('giangvien');
    const facultySelect = document.getElementById('khoa');
    const majorSelect = document.getElementById('chuyennganh');
    const studentTableBody = document.querySelector('.student-table tbody');
    const paginationContainer = document.querySelector('.pagination');
    const selectedCountSpan = document.getElementById('dachon');
    const searchInput = document.getElementById('search-sinhvien');
    const selectedIds = new Set();
    let capacityLimitGlobal = null;
    let allStudents = [];
    let filteredStudents = [];
    let currentPage = 1;
    const PAGE_SIZE = 50;

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

    function populateSelect(selectEl, items, formatter) {
        if (!selectEl) return;
        selectEl.innerHTML = '';
        const placeholder = document.createElement('option');
        placeholder.value = '';
        placeholder.textContent = selectEl === majorSelect ? 'Tất cả Chuyên ngành' : '-- Chọn --';
        selectEl.appendChild(placeholder);
        items.forEach(item => {
            const { value, label } = formatter(item);
            const opt = document.createElement('option');
            opt.value = value;
            opt.textContent = label;
            selectEl.appendChild(opt);
        });
    }

    async function loadMajorsByFaculty(facultyId = 0) {
        const url = new URL(majorsByFacultyUrl, window.location.origin);
        if (facultyId) url.searchParams.set('faculty_id', facultyId);
        const json = await fetchJson(url.toString());

        if (json.success) {
            const majors = Array.isArray(json.data) ? json.data : (json.data?.majors || []);
            populateSelect(majorSelect, majors, (m) => ({ value: m.id, label: m.name }));
        } else {
            populateSelect(majorSelect, [], () => ({ value: '', label: '-- Chọn --' }));
        }

        // Reset filter khi đổi khoa
        await filterStudents();
    }

    async function loadStudents() {
        const url = new URL(studentsUrl, window.location.origin);
        const json = await fetchJson(url.toString());
        if (!json.success) return;
        allStudents = json.data || [];
        filterStudents();
    }

    async function filterStudents() {
        const keyword = searchInput?.value?.trim() || '';
        const facultyId = facultySelect?.value || '';
        const majorId = majorSelect?.value || '';

        let filtered = allStudents.slice();

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

        // Ẩn các sinh viên đã thuộc lớp (edit mode)
        if (isEditMode && selectedIds.size > 0) {
            filtered = filtered.filter(s => {
                const sid = Number(s.id ?? s.student_id ?? s.user_id ?? 0);
                return !selectedIds.has(sid);
            });
        }

        filteredStudents = filtered;
        currentPage = 1;
        renderStudents(filteredStudents);
        renderPagination(filteredStudents.length);
    }

    function renderStudents(list) {
        if (!studentTableBody) return;
        studentTableBody.innerHTML = '';
        const start = (currentPage - 1) * PAGE_SIZE;
        const pageItems = list.slice(start, start + PAGE_SIZE);
        pageItems.forEach(student => {
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
        // Apply selected state after rendering
        studentTableBody.querySelectorAll('input[type="checkbox"]').forEach(cb => {
            const id = Number(cb.dataset.id);
            if (selectedIds.has(id)) {
                cb.checked = true;
            }
        });
        updateSelectedCount();
    }

    function renderPagination(totalItems) {
        if (!paginationContainer) return;
        const totalPages = Math.max(1, Math.ceil(totalItems / PAGE_SIZE));
        currentPage = Math.min(currentPage, totalPages);

        let html = '';
        const disablePrev = currentPage === 1 ? 'disabled' : '';
        const disableNext = currentPage === totalPages ? 'disabled' : '';
        html += `<button class="page-btn ${disablePrev}" data-page="prev">‹</button>`;

        // Simple pagination: show up to 5 pages centered around current
        const pages = [];
        const start = Math.max(1, currentPage - 2);
        const end = Math.min(totalPages, start + 4);
        for (let p = start; p <= end; p++) pages.push(p);

        pages.forEach(p => {
            const active = p === currentPage ? 'active' : '';
            html += `<button class="page-btn ${active}" data-page="${p}">${p}</button>`;
        });

        html += `<button class="page-btn ${disableNext}" data-page="next">›</button>`;
        paginationContainer.innerHTML = html;

        paginationContainer.querySelectorAll('.page-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                const val = btn.dataset.page;
                if (val === 'prev' && currentPage > 1) currentPage--;
                else if (val === 'next' && currentPage < totalPages) currentPage++;
                else if (!isNaN(Number(val))) currentPage = Number(val);
                renderStudents(filteredStudents);
                renderPagination(totalItems);
            });
        });
    }

    function bindSelection() {
        const checkboxes = studentTableBody.querySelectorAll('input[type="checkbox"]');
        const headerCheckbox = document.querySelector('.student-table thead input[type="checkbox"]');

        // Khi click checkbox trong header
        if (headerCheckbox) {
            headerCheckbox.addEventListener('change', function () {
                checkboxes.forEach(cb => {
                    cb.checked = this.checked;
                    const id = Number(cb.dataset.id);
                    if (this.checked) {
                        selectedIds.add(id);
                    } else {
                        selectedIds.delete(id);
                    }
                });
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
                const id = Number(this.dataset.id);
                if (this.checked) {
                    selectedIds.add(id);
                } else {
                    selectedIds.delete(id);
                }
                updateSelectedCount();
            });
        });
    }

    function updateSelectedCount() {
        const total = capacityLimitGlobal ?? studentTableBody.querySelectorAll('input[type="checkbox"]').length;
        if (selectedCountSpan) selectedCountSpan.textContent = `${selectedIds.size}/${total || 0}`;
    }

    async function submitForm(capacityLimit) {
        const selected = Array.from(selectedIds);
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

        const url = isEditMode ? updateStep2Url : step2Url;
        const method = isEditMode ? 'PUT' : 'POST';
        
        const res = await fetch(url, {
            method: method,
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
            alert(isEditMode ? 'Cập nhật lớp học thành công' : 'Tạo lớp học thành công');
            window.location.href = isEditMode ? `/admin/lop-hoc/${classSectionId}/chi-tiet` : '/admin/lop-hoc';
        } else {
            alert(json.message || (isEditMode ? 'Cập nhật lớp thất bại' : 'Tạo lớp thất bại'));
        }
    }

    async function loadExistingData() {
        try {
            const res = await fetch(detailUrl, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                }
            });
            const json = await res.json();
            if (!json.success || !json.data) {
                alert('Không thể tải thông tin lớp học');
                return null;
            }
            
            const data = json.data;
            const lecturerId = data.class_info?.lecturer_id || null;
            const studentIds = data.students ? data.students.map(s => s.student_id) : [];
            return { classInfo: data.class_info, lecturerId, studentIds };
        } catch (error) {
            console.error('Error loading existing data:', error);
            alert('Lỗi khi tải thông tin lớp học');
            return null;
        }
    }

    document.addEventListener('DOMContentLoaded', async () => {
        let step1;
        let existing = null;
        
        if (isEditMode && detailUrl) {
            // Load existing data in edit mode
            existing = await loadExistingData();
            step1 = existing?.classInfo;
            // Update summary with existing data
            if (step1) {
                capacityLimitGlobal = step1.capacity || null;
                updateSummary({
                    academic_year_name: step1.academic_year_name,
                    semester_name: step1.semester_name,
                    faculty_name: step1.faculty_name,
                    major_name: step1.major_name,
                    course_name: step1.course_name,
                    class_code: step1.class_code,
                    time_slot_label: step1.time_slot_label,
                    room_name: step1.room_name,
                    capacity: step1.capacity,
                });
            }

            // Ghi nhận trước danh sách sinh viên đã thuộc lớp để ẩn khỏi danh sách chọn mới
            if (existing?.studentIds?.length) {
                selectedIds.clear();
                existing.studentIds.forEach(id => selectedIds.add(Number(id)));
            }
        } else {
            // Load from session in create mode
            step1 = await loadStep1();
            capacityLimitGlobal = step1?.capacity || null;
        }
        
        await loadLecturers();
        await loadFaculties();
        await loadMajorsByFaculty();
        await loadStudents();

        // Apply lecturer and selected students after lists are loaded
        if (isEditMode && existing) {
            if (existing.lecturerId && lecturerSelect) {
                lecturerSelect.value = existing.lecturerId;
            }
            if (existing.studentIds && existing.studentIds.length > 0) {
                // selectedIds đã được set trước loadStudents, chỉ cần đồng bộ checkboxes và đếm
                studentTableBody.querySelectorAll('input[type="checkbox"]').forEach(cb => {
                    const id = Number(cb.dataset.id);
                    if (selectedIds.has(id)) cb.checked = true;
                });
                updateSelectedCount();
            }
        }
        
        // Update button text for edit mode
        const completeBtn = document.getElementById('hoantat');
        if (isEditMode && completeBtn) {
            completeBtn.textContent = 'Cập nhật';
        }

        // Event listeners
        facultySelect?.addEventListener('change', function () {
            loadMajorsByFaculty(this.value);
        });

        majorSelect?.addEventListener('change', filterStudents);

        searchInput?.addEventListener('input', filterStudents);

        completeBtn?.addEventListener('click', () => submitForm(step1?.capacity));
        document.getElementById('quaylai')?.addEventListener('click', () => {
            if (isEditMode) {
                window.location.href = `/admin/lop-hoc/${classSectionId}/sua-buoc-1`;
            } else {
                window.location.href = '/admin/lop-hoc/tao-buoc-1';
            }
        });
    });
})();