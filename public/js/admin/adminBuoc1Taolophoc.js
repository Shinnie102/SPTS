(() => {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const optionsMeta = document.querySelector('meta[name="route-admin-lophoc-api-create-options"]');
    const step1Meta = document.querySelector('meta[name="route-admin-lophoc-api-create-step1"]');
    const semestersByYearMeta = document.querySelector('meta[name="route-admin-lophoc-api-semesters-by-year"]');
    const coursesByMajorMeta = document.querySelector('meta[name="route-admin-lophoc-api-courses-by-major"]');
    const majorsMeta = document.querySelector('meta[name="route-admin-lophoc-api-majors"]');

    if (!optionsMeta || !step1Meta) return;

    const optionsUrl = optionsMeta.content;
    const step1Url = step1Meta.content;
    const semestersByYearUrl = semestersByYearMeta?.content;
    const coursesByMajorUrl = coursesByMajorMeta?.content;
    const majorsUrl = majorsMeta?.content;

    const selects = {
        academicYear: document.getElementById('namhoc'),
        semester: document.getElementById('kyhoc'),
        faculty: document.getElementById('khoa'),
        major: document.getElementById('chuyennganh'),
        course: document.getElementById('hocphan'),
        timeSlot: document.getElementById('cahoc'),
        meetingDate: document.getElementById('lichhoc'),
        room: document.getElementById('phonghoc'),
    };

    const capacityInput = document.getElementById('suchua');
    const classCodeInput = document.querySelector('input[placeholder="Nhập mã lớp"]');
    const continueBtn = document.getElementById('tieptuc');

    function addOptions(select, items, formatter) {
        if (!select) return;
        const placeholderText = select.options[0]?.textContent || '-- Chọn --';
        select.innerHTML = '';
        const placeholder = placeholderText;
        const defaultOption = document.createElement('option');
        defaultOption.value = '';
        defaultOption.textContent = placeholder;
        select.appendChild(defaultOption);

        items.forEach(item => {
            const opt = document.createElement('option');
            const { value, label } = formatter(item);
            opt.value = value;
            opt.textContent = label;
            // Store extra data for class code generation
            if (item.code) opt.dataset.code = item.code;
            select.appendChild(opt);
        });
    }

    async function loadOptions() {
        const res = await fetch(optionsUrl, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
            }
        });
        const json = await res.json();
        if (!json.success) return;
        const data = json.data || {};

        addOptions(selects.academicYear, data.academic_years || [], (y) => ({ value: y.id, label: y.name }));
        // Học kỳ sẽ load khi chọn năm học
        addOptions(selects.semester, [], () => ({}));
        addOptions(selects.faculty, data.faculties || [], (f) => ({ value: f.id, label: f.name }));
        // Chuyên ngành sẽ load khi chọn khoa
        addOptions(selects.major, [], () => ({}));
        // Học phần sẽ load khi chọn chuyên ngành
        addOptions(selects.course, [], () => ({}));
        addOptions(selects.timeSlot, data.time_slots || [], (t) => ({ value: t.id, label: `${t.slot_code} (${t.start_time} - ${t.end_time})` }));
        addOptions(selects.room, data.rooms || [], (r) => ({ value: r.id, label: `${r.room_code} - ${r.room_name || ''} (Sức chứa: ${r.capacity || 'N/A'})` }));

        // Generate meeting dates (next 14 days) as button-style checkboxes
        if (selects.meetingDate) {
            const today = new Date();
            let html = '';
            for (let i = 0; i < 14; i++) {
                const d = new Date(today);
                d.setDate(today.getDate() + i);
                const label = d.toLocaleDateString('vi-VN');
                const value = d.toISOString().slice(0, 10);
                const dayName = ['CN', 'T2', 'T3', 'T4', 'T5', 'T6', 'T7'][d.getDay()];
                html += `<label style="display: flex; align-items: center; justify-content: center; gap: 8px; padding: 8px; border: 1px solid #ddd; border-radius: 3px; cursor: pointer; transition: all 0.2s; background: #f9f9f9;" class="date-checkbox-label">
                    <input type="checkbox" class="meeting-date-checkbox" value="${value}" data-label="${label}" style="cursor: pointer; width: 16px; height: 16px; margin: 0; flex-shrink: 0;" onchange="this.parentElement.style.background=this.checked?'#e3f2fd':'#f9f9f9'; this.parentElement.style.borderColor=this.checked?'#1976d2':'#ddd';">
                    <span style="font-size: 1rem; flex: 1;">
                        <div style="font-weight: 500; line-height: 1.2;">${label}</div>
                        <div style="font-size: 0.85rem; color: #666; line-height: 1;">${dayName}</div>
                    </span>
                </label>`;
            }
            selects.meetingDate.innerHTML = html;
        }
    }

    function getSelectedMeetingDates() {
        const checkboxes = document.querySelectorAll('.meeting-date-checkbox:checked');
        return Array.from(checkboxes).map(cb => ({
            value: cb.value,
            label: cb.dataset.label
        }));
    }

    function generateClassCode() {
        const courseOpt = selects.course?.selectedOptions[0];
        const semesterOpt = selects.semester?.selectedOptions[0];
        
        if (!courseOpt || !semesterOpt || !courseOpt.value || !semesterOpt.value) {
            return '';
        }

        const courseCode = courseOpt.dataset.code || 'COURSE';
        const semesterCode = semesterOpt.dataset.code || semesterOpt.textContent;
        
        // Format: CS101_HK1-2024_01 (course_semester_section)
        // Generate random 2-digit section number
        const section = String(Math.floor(Math.random() * 90) + 10);
        return `${courseCode}_${semesterCode}_${section}`;
    }

    function updateSummary() {
        const summary = document.querySelectorAll('#right .ketqua');
        if (!summary.length) return;
        summary[0].textContent = selects.academicYear?.selectedOptions[0]?.textContent || 'Chưa chọn';
        summary[1].textContent = selects.semester?.selectedOptions[0]?.textContent || 'Chưa chọn';
        summary[2].textContent = selects.faculty?.selectedOptions[0]?.textContent || 'Chưa chọn';
        summary[3].textContent = selects.major?.selectedOptions[0]?.textContent || 'Chưa chọn';
        summary[4].textContent = selects.course?.selectedOptions[0]?.textContent || 'Chưa chọn';
        summary[5].textContent = classCodeInput?.value || 'Chưa nhập';
        summary[6].textContent = selects.timeSlot?.selectedOptions[0]?.textContent || 'Chưa chọn';
        const selectedDates = getSelectedMeetingDates();
        summary[7].textContent = selectedDates.length > 0 ? `${selectedDates.length} ngày được chọn` : 'Chưa chọn';
        summary[8].textContent = selects.room?.selectedOptions[0]?.textContent || 'Chưa chọn';
        summary[9].textContent = capacityInput?.value || 'Chưa nhập';
    }

    function bindSummaryUpdates() {
        Object.values(selects).forEach(sel => {
            // Skip meetingDate as it's now a div with checkboxes
            if (sel === selects.meetingDate) return;
            sel?.addEventListener('change', updateSummary);
        });
        classCodeInput?.addEventListener('input', updateSummary);
        capacityInput?.addEventListener('input', updateSummary);
        // Listen to checkbox changes
        document.addEventListener('change', (e) => {
            if (e.target.classList.contains('meeting-date-checkbox')) {
                updateSummary();
            }
        });
    }

    async function submitStep1() {
        const selectedDates = getSelectedMeetingDates();
        const payload = {
            academic_year_id: selects.academicYear?.value,
            semester_id: selects.semester?.value,
            faculty_id: selects.faculty?.value,
            major_id: selects.major?.value,
            course_version_id: selects.course?.value,
            class_code: classCodeInput?.value,
            time_slot_id: selects.timeSlot?.value,
            meeting_dates: selectedDates.map(d => d.value),
            room_id: selects.room?.value,
            capacity: capacityInput?.value,
            academic_year_name: selects.academicYear?.selectedOptions[0]?.textContent || '',
            semester_name: selects.semester?.selectedOptions[0]?.textContent || '',
            faculty_name: selects.faculty?.selectedOptions[0]?.textContent || '',
            major_name: selects.major?.selectedOptions[0]?.textContent || '',
            course_name: selects.course?.selectedOptions[0]?.textContent || '',
            time_slot_label: selects.timeSlot?.selectedOptions[0]?.textContent || '',
            room_name: selects.room?.selectedOptions[0]?.textContent || '',
            meeting_dates_label: selectedDates.map(d => d.label).join(', '),
        };

        const requiredFields = ['class_code', 'course_version_id', 'semester_id', 'time_slot_id', 'room_id', 'capacity'];
        for (const key of requiredFields) {
            if (!payload[key]) {
                alert('Vui lòng điền đủ thông tin bắt buộc');
                return;
            }
        }

        if (selectedDates.length === 0) {
            alert('Vui lòng chọn ít nhất 1 lịch học');
            return;
        }

        const res = await fetch(step1Url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json',
            },
            body: JSON.stringify(payload),
        });
        const json = await res.json();
        if (res.ok && json.success) {
            window.location.href = '/admin/lop-hoc/tao-buoc-2';
        } else {
            alert(json.message || 'Không thể lưu bước 1');
        }
    }

    // Cascading dropdown handlers
    async function loadSemestersByYear(yearId) {
        if (!yearId || !semestersByYearUrl) return;
        const url = `${semestersByYearUrl}?academic_year_id=${yearId}`;
        const res = await fetch(url, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
            }
        });
        const json = await res.json();
        if (json.success) {
            addOptions(selects.semester, json.data || [], (s) => ({ value: s.id, label: s.name, code: s.name }));
        }
    }

    async function loadMajorsByFaculty(facultyId) {
        if (!facultyId || !majorsUrl) return;
        const url = `${majorsUrl}?faculty_id=${facultyId}`;
        const res = await fetch(url, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
            }
        });
        const json = await res.json();
        if (json.success) {
            addOptions(selects.major, json.data || [], (m) => ({ value: m.id, label: m.name }));
        }
    }

    async function loadCoursesByMajor(majorId) {
        if (!majorId || !coursesByMajorUrl) return;
        const url = `${coursesByMajorUrl}?major_id=${majorId}`;
        const res = await fetch(url, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
            }
        });
        const json = await res.json();
        if (json.success) {
            addOptions(selects.course, json.data || [], (c) => ({ value: c.id, label: `${c.name}`, code: c.code }));
        }
    }

    // Bind cascading events
    function bindCascadingDropdowns() {
        if (selects.academicYear) {
            selects.academicYear.addEventListener('change', (e) => {
                loadSemestersByYear(e.target.value);
                updateSummary();
            });
        }

        if (selects.faculty) {
            selects.faculty.addEventListener('change', (e) => {
                loadMajorsByFaculty(e.target.value);
                updateSummary();
            });
        }

        if (selects.major) {
            selects.major.addEventListener('change', (e) => {
                loadCoursesByMajor(e.target.value);
                updateSummary();
            });
        }

        // Auto-suggest class code when course or semester changes
        if (selects.course) {
            selects.course.addEventListener('change', () => {
                if (classCodeInput && !classCodeInput.value) {
                    classCodeInput.value = generateClassCode();
                    updateSummary();
                }
            });
        }

        if (selects.semester) {
            selects.semester.addEventListener('change', () => {
                if (classCodeInput && selects.course?.value && !classCodeInput.value) {
                    classCodeInput.value = generateClassCode();
                    updateSummary();
                }
            });
        }
    }

    document.addEventListener('DOMContentLoaded', () => {
        loadOptions();
        bindSummaryUpdates();
        bindCascadingDropdowns();
        continueBtn?.addEventListener('click', submitStep1);
    });
})();
