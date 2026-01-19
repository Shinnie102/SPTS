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


// ====== Data loading for admin Lớp học page ======
(function () {
    const csrfTokenMeta = document.querySelector('meta[name="csrf-token"]');
    const apiIndexMeta = document.querySelector('meta[name="route-admin-lophoc-api-index"]');
    const apiFiltersMeta = document.querySelector('meta[name="route-admin-lophoc-api-filters"]');
    const apiMajorsMeta = document.querySelector('meta[name="route-admin-lophoc-api-majors"]');
    const detailPrefixMeta = document.querySelector('meta[name="route-admin-lophoc-detail-prefix"]');

    if (!apiIndexMeta || !apiFiltersMeta) {
        // Not on the target page; safely return.
        return;
    }

    const csrfToken = csrfTokenMeta ? csrfTokenMeta.getAttribute('content') : '';
    const apiIndexUrl = apiIndexMeta.getAttribute('content');
    const apiFiltersUrl = apiFiltersMeta.getAttribute('content');
    const apiMajorsUrl = apiMajorsMeta ? apiMajorsMeta.getAttribute('content') : '';
    const detailPrefix = detailPrefixMeta ? detailPrefixMeta.getAttribute('content') : '';

    async function loadClassSections(page = 1) {
        try {
            const keyword = (document.getElementById('timkiem')?.value || '').trim();
            const facultyId = document.getElementById('khoa-filter')?.value || '';
            const majorId = document.getElementById('major-filter')?.value || '';
            const semesterId = document.getElementById('semester-filter')?.value || '';

            const params = new URLSearchParams({
                keyword: keyword,
                faculty_id: facultyId,
                major_id: majorId,
                semester_id: semesterId,
                page: String(page),
                per_page: '15',
            });

            const response = await fetch(`${apiIndexUrl}?${params.toString()}`, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                }
            });

            if (!response.ok) throw new Error('Network response was not ok');
            const json = await response.json();
            if (json.success) {
                renderTable(json.data);
            } else {
                console.error('API Error:', json.message || 'Unknown error');
            }
        } catch (err) {
            console.error('Error loading class sections:', err);
        }
    }

    function getStatusBadgeClass(statusId) {
        switch (Number(statusId)) {
            case 1: return 'active';      // ONGOING
            case 2: return 'pending';     // COMPLETED
            case 3: return 'closed';      // CANCELLED
            default: return 'default';
        }
    }

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
        }, 2500);
    }

    function renderTable(classData) {
        const tbody = document.getElementById('class-table-body');
        if (!tbody) return;

        tbody.innerHTML = '';
        if (!Array.isArray(classData) || classData.length === 0) {
            tbody.innerHTML = '<tr><td colspan="7" style="text-align: center; padding: 20px;">Không có dữ liệu</td></tr>';
            return;
        }

        classData.forEach(cls => {
            const row = document.createElement('tr');
            const current = Number(cls.current_students ?? 0);
            const capacity = Number(cls.capacity ?? 0);
            const capacityText = `${current}/${capacity}`;
            const statusBadgeClass = cls.badge_class || getStatusBadgeClass(cls.status_id);

            row.innerHTML = `
                <td>${cls.class_code ?? ''}</td>
                <td>${cls.course_name ?? ''}</td>
                <td>${cls.semester_code ?? ''}</td>
                <td>${capacityText}</td>
                <td>${cls.lecturer_name ?? ''}</td>
                <td><span class="badge ${statusBadgeClass}">${cls.status_name ?? ''}</span></td>
                <td class="action">
                    <i class="fa-solid fa-pen-to-square edit" data-id="${cls.id}" title="Chỉnh sửa"></i>
                    <i class="fa-solid fa-trash delete" data-id="${cls.id}" title="Xóa"></i>
                </td>
            `;
            tbody.appendChild(row);
        });
    }

    async function loadFilterOptions() {
        try {
            const response = await fetch(apiFiltersUrl, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                }
            });
            if (!response.ok) throw new Error('Network response was not ok');
            const json = await response.json();
            if (json.success) populateFilterOptions(json.data);
        } catch (err) {
            console.error('Error loading filter options:', err);
        }
    }

    function getOptionsContainerByInputName(name) {
        const input = document.querySelector(`.fake-select input[name="${name}"]`);
        return input?.closest('.fake-select')?.querySelector('.options') || null;
    }

    function populateFilterOptions(options) {
        // Faculties
        const facultyOptions = getOptionsContainerByInputName('khoa');
        if (facultyOptions && Array.isArray(options?.faculties)) {
            options.faculties.forEach(faculty => {
                const option = document.createElement('div');
                option.className = 'option';
                option.dataset.value = faculty.id;
                option.textContent = faculty.name;
                option.addEventListener('click', async function () {
                    const hidden = document.getElementById('khoa-filter');
                    if (hidden) hidden.value = faculty.id;
                    const selected = option.closest('.fake-select')?.querySelector('.selected');
                    if (selected) selected.textContent = faculty.name;
                    await refreshMajors(faculty.id);
                    loadClassSections(1);
                });
                facultyOptions.appendChild(option);
            });
            // Bind "Tất cả khoa" to clear and reload
            const allFaculty = facultyOptions.querySelector('.option[data-value=""]');
            if (allFaculty) {
                allFaculty.addEventListener('click', async function () {
                    const hidden = document.getElementById('khoa-filter');
                    if (hidden) hidden.value = '';
                    const selected = allFaculty.closest('.fake-select')?.querySelector('.selected');
                    if (selected) selected.textContent = 'Tất cả khoa';
                    await refreshMajors('');
                    loadClassSections(1);
                });
            }
        }

        // Majors
        const majorOptions = getOptionsContainerByInputName('chuyennganh');
        if (majorOptions && Array.isArray(options?.majors)) {
            options.majors.forEach(major => {
                const option = document.createElement('div');
                option.className = 'option';
                option.dataset.value = major.id;
                option.textContent = major.name;
                option.addEventListener('click', function () {
                    const hidden = document.getElementById('major-filter');
                    if (hidden) hidden.value = major.id;
                    const selected = option.closest('.fake-select')?.querySelector('.selected');
                    if (selected) selected.textContent = major.name;
                    loadClassSections(1);
                });
                majorOptions.appendChild(option);
            });
        }

        // Semesters
        const semesterOptions = getOptionsContainerByInputName('hocky');
        if (semesterOptions && Array.isArray(options?.semesters)) {
            options.semesters.forEach(semester => {
                const option = document.createElement('div');
                option.className = 'option';
                option.dataset.value = semester.id;
                option.textContent = semester.name;
                option.addEventListener('click', function () {
                    const hidden = document.getElementById('semester-filter');
                    if (hidden) hidden.value = semester.id;
                    const selected = option.closest('.fake-select')?.querySelector('.selected');
                    if (selected) selected.textContent = semester.name;
                    loadClassSections(1);
                });
                semesterOptions.appendChild(option);
            });
        }
    }

    async function refreshMajors(facultyId) {
        try {
            if (!apiMajorsUrl) return;
            const url = new URL(apiMajorsUrl, window.location.origin);
            url.searchParams.set('faculty_id', String(facultyId || ''));
            const response = await fetch(url.toString(), {
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken },
            });
            if (!response.ok) throw new Error('Network response was not ok');
            const json = await response.json();
            if (json.success) {
                const container = getOptionsContainerByInputName('chuyennganh');
                if (container) {
                    container.innerHTML = '';
                    const all = document.createElement('div');
                    all.className = 'option';
                    all.dataset.value = '';
                    all.textContent = 'Tất cả chuyên ngành';
                    all.addEventListener('click', function () {
                        const hidden = document.getElementById('major-filter');
                        if (hidden) hidden.value = '';
                        const selected = all.closest('.fake-select')?.querySelector('.selected');
                        if (selected) selected.textContent = 'Tất cả chuyên ngành';
                        loadClassSections(1);
                    });
                    container.appendChild(all);

                    (json.data?.majors || []).forEach(major => {
                        const option = document.createElement('div');
                        option.className = 'option';
                        option.dataset.value = major.id;
                        option.textContent = major.name;
                        option.addEventListener('click', function () {
                            const hidden = document.getElementById('major-filter');
                            if (hidden) hidden.value = major.id;
                            const selected = option.closest('.fake-select')?.querySelector('.selected');
                            if (selected) selected.textContent = major.name;
                            loadClassSections(1);
                        });
                        container.appendChild(option);
                    });
                }
            }
        } catch (err) {
            console.error('Error refreshing majors:', err);
        }
    }

    function bindSearch() {
        const input = document.getElementById('timkiem');
        if (!input) return;
        let timer;
        input.addEventListener('input', function () {
            clearTimeout(timer);
            timer = setTimeout(() => loadClassSections(1), 300);
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        loadFilterOptions();
        loadClassSections(1);
        bindSearch();
    });

    // Delegate click on edit icon to navigate to detail page
    const tbody = document.getElementById('class-table-body');
    if (tbody && detailPrefix) {
        tbody.addEventListener('click', function (e) {
            const editIcon = e.target.closest('.edit');
            if (editIcon && editIcon.dataset.id) {
                const id = editIcon.dataset.id;
                window.location.href = `${detailPrefix}/${id}/chi-tiet`;
            }
            const deleteIcon = e.target.closest('.delete');
            if (deleteIcon && deleteIcon.dataset.id) {
                const id = deleteIcon.dataset.id;
                if (!confirm('Bạn có chắc chắn muốn xóa lớp học phần này? Hành động không thể hoàn tác.')) {
                    return;
                }
                fetch(`${apiIndexUrl}/${id}`, {
                    method: 'DELETE',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                    }
                }).then(async (res) => {
                    const data = await res.json().catch(() => ({}));
                    if (res.ok && data.success) {
                        showToast('Đã xóa lớp học phần thành công.', 'success');
                        loadClassSections(1);
                    } else {
                        showToast(data.message || 'Xóa không thành công.', 'error');
                    }
                }).catch(() => showToast('Không thể kết nối máy chủ.', 'error'));
            }
        });
    }
})();

