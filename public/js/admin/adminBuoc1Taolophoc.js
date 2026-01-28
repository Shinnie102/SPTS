(() => {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const classSectionIdMeta = document.querySelector('meta[name="class-section-id"]');
    const classSectionId = classSectionIdMeta?.content || '';
    const isEditMode = !!classSectionId;
    
    const optionsMeta = document.querySelector('meta[name="route-admin-lophoc-api-create-options"]');
    const step1Meta = document.querySelector('meta[name="route-admin-lophoc-api-create-step1"]');
    const updateStep1Meta = document.querySelector('meta[name="route-admin-lophoc-api-update-step1"]');
    const detailMeta = document.querySelector('meta[name="route-admin-lophoc-api-detail"]');
    const semestersByYearMeta = document.querySelector('meta[name="route-admin-lophoc-api-semesters-by-year"]');
    const coursesByMajorMeta = document.querySelector('meta[name="route-admin-lophoc-api-courses-by-major"]');
    const majorsMeta = document.querySelector('meta[name="route-admin-lophoc-api-majors"]');

    if (!optionsMeta || !step1Meta) return;

    const optionsUrl = optionsMeta.content;
    const step1Url = step1Meta.content;
    const updateStep1Url = updateStep1Meta?.content;
    const detailUrl = detailMeta?.content;
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
        addOptions(selects.semester, [], () => ({}));
        addOptions(selects.faculty, data.faculties || [], (f) => ({ value: f.id, label: f.name }));
        addOptions(selects.major, [], () => ({}));
        addOptions(selects.course, [], () => ({}));
        addOptions(selects.timeSlot, data.time_slots || [], (t) => ({ value: t.id, label: `${t.slot_code} (${t.start_time} - ${t.end_time})` }));
        addOptions(selects.room, data.rooms || [], (r) => ({ value: r.id, label: `${r.room_code} - ${r.room_name || ''} (Sức chứa: ${r.capacity || 'N/A'})` }));

        // --- BẮT ĐẦU: UI chọn lịch học thông minh ---
        if (selects.meetingDate) {
            // Inject UI chọn thứ + khoảng ngày
            const lichhocContainer = selects.meetingDate;
            lichhocContainer.innerHTML = '';
            const weekdays = [
                { label: 'Thứ 2', value: 1 },
                { label: 'Thứ 3', value: 2 },
                { label: 'Thứ 4', value: 3 },
                { label: 'Thứ 5', value: 4 },
                { label: 'Thứ 6', value: 5 },
                { label: 'Thứ 7', value: 6 },
                { label: 'Chủ nhật', value: 0 }
            ];
            const ui = document.createElement('div');
            ui.style.marginBottom = '10px';
            ui.innerHTML = `
                <div style="display:flex;gap:18px;flex-wrap:nowrap;margin-bottom:12px;justify-content:flex-start;align-items:center;">
                    ${weekdays.map(w => `<label style='display:flex;align-items:center;gap:6px;font-size:1.08em;font-weight:500;white-space:nowrap;'><input type='checkbox' class='weekday-picker' value='${w.value}' style='width:1.2em;height:1.2em;accent-color:#0284C7;'>${w.label}</label>`).join('')}
                </div>
                <div style="display:flex;gap:8px;align-items:center;margin-bottom:8px;">
                    <span>Từ ngày:</span><input type='date' id='auto-date-start' style='padding:2px 4px;'>
                    <span>Đến ngày:</span><input type='date' id='auto-date-end' style='padding:2px 4px;'>
                    <button type='button' id='auto-generate-dates' style='padding:2px 10px;border-radius:3px;border:1px solid #1976d2;background:#1976d2;color:#fff;cursor:pointer;'>Tạo lịch</button>
                </div>
                <div id='auto-preview-dates' style='font-size:0.95em;color:#1976d2;margin-bottom:8px;'></div>
                <div id='auto-checkbox-list' style='display:grid;grid-template-columns:repeat(3,1fr);gap:6px;'></div>
            `;
            lichhocContainer.appendChild(ui);

            // Preview + render lịch học
            function generateDates() {
                const checkedWeekdays = Array.from(ui.querySelectorAll('.weekday-picker:checked')).map(cb => +cb.value);
                const start = ui.querySelector('#auto-date-start').value;
                const end = ui.querySelector('#auto-date-end').value;
                if (!checkedWeekdays.length || !start || !end) return [];
                const startDate = new Date(start);
                const endDate = new Date(end);
                let cur = new Date(startDate);
                const result = [];
                while (cur <= endDate) {
                    if (checkedWeekdays.includes(cur.getDay())) {
                        result.push(new Date(cur));
                    }
                    cur.setDate(cur.getDate() + 1);
                }
                return result;
            }

            function renderPreview() {
                const dates = generateDates();
                const preview = ui.querySelector('#auto-preview-dates');
                if (!dates.length) {
                    preview.textContent = '';
                    return;
                }
                preview.innerHTML = '<b>Các buổi học sẽ diễn ra:</b><br>' + dates.map(d => {
                    const dayName = ['CN','T2','T3','T4','T5','T6','T7'][d.getDay()];
                    return `${dayName} – ${d.toLocaleDateString('vi-VN')}`;
                }).join('<br>');
            }

            ui.querySelectorAll('.weekday-picker, #auto-date-start, #auto-date-end').forEach(el => {
                el.addEventListener('change', renderPreview);
            });

            // Khi bấm "Tạo lịch" sẽ render checkbox ngày học
            ui.querySelector('#auto-generate-dates').addEventListener('click', () => {
                const dates = generateDates();
                const checkboxList = ui.querySelector('#auto-checkbox-list');
                checkboxList.innerHTML = '';
                dates.forEach(d => {
                    const value = d.toISOString().slice(0,10);
                    const label = d.toLocaleDateString('vi-VN');
                    const dayName = ['CN','T2','T3','T4','T5','T6','T7'][d.getDay()];
                    const wrapper = document.createElement('label');
                    wrapper.style.display = 'flex';
                    wrapper.style.alignItems = 'center';
                    wrapper.style.justifyContent = 'center';
                    wrapper.style.gap = '8px';
                    wrapper.style.padding = '8px';
                    wrapper.style.border = '1px solid #ddd';
                    wrapper.style.borderRadius = '3px';
                    wrapper.style.cursor = 'pointer';
                    wrapper.style.transition = 'all 0.2s';
                    wrapper.style.background = '#f9f9f9';
                    wrapper.classList.add('date-checkbox-label');
                    const input = document.createElement('input');
                    input.type = 'checkbox';
                    input.className = 'meeting-date-checkbox';
                    input.value = value;
                    input.dataset.label = label;
                    input.checked = true;
                    input.style.cursor = 'pointer';
                    input.style.width = '16px';
                    input.style.height = '16px';
                    input.style.margin = '0';
                    input.style.flexShrink = '0';
                    input.onchange = function () {
                        this.parentElement.style.background = this.checked ? '#e3f2fd' : '#f9f9f9';
                        this.parentElement.style.borderColor = this.checked ? '#1976d2' : '#ddd';
                    };
                    const span = document.createElement('span');
                    span.style.fontSize = '1rem';
                    span.style.flex = '1';
                    span.innerHTML = `<div style="font-weight: 500; line-height: 1.2;">${label}</div><div style="font-size: 0.85rem; color: #666; line-height: 1;">${dayName}</div>`;
                    wrapper.appendChild(input);
                    wrapper.appendChild(span);
                    checkboxList.appendChild(wrapper);
                });
                renderPreview();
            });
        }
    }

    function getSelectedMeetingDates() {
        const checkboxes = document.querySelectorAll('.meeting-date-checkbox:checked');
        return Array.from(checkboxes).map(cb => ({
            value: cb.value,
            label: cb.dataset.label
        }));
    }

    function ensureMeetingDateCheckbox(dateValue) {
        // Tạo checkbox nếu ngày không nằm trong 14 ngày được render sẵn
        const container = selects.meetingDate;
        if (!container) return;
        const existing = container.querySelector(`input.meeting-date-checkbox[value="${dateValue}"]`);
        if (existing) return existing;

        const d = new Date(dateValue);
        const labelText = d.toLocaleDateString('vi-VN');
        const dayName = ['CN', 'T2', 'T3', 'T4', 'T5', 'T6', 'T7'][d.getDay()];

        const wrapper = document.createElement('label');
        wrapper.style.display = 'flex';
        wrapper.style.alignItems = 'center';
        wrapper.style.justifyContent = 'center';
        wrapper.style.gap = '8px';
        wrapper.style.padding = '8px';
        wrapper.style.border = '1px solid #ddd';
        wrapper.style.borderRadius = '3px';
        wrapper.style.cursor = 'pointer';
        wrapper.style.transition = 'all 0.2s';
        wrapper.style.background = '#e3f2fd';
        wrapper.style.borderColor = '#1976d2';
        wrapper.classList.add('date-checkbox-label');

        const input = document.createElement('input');
        input.type = 'checkbox';
        input.className = 'meeting-date-checkbox';
        input.value = dateValue;
        input.dataset.label = labelText;
        input.checked = true;
        input.style.cursor = 'pointer';
        input.style.width = '16px';
        input.style.height = '16px';
        input.style.margin = '0';
        input.style.flexShrink = '0';
        input.onchange = function () {
            this.parentElement.style.background = this.checked ? '#e3f2fd' : '#f9f9f9';
            this.parentElement.style.borderColor = this.checked ? '#1976d2' : '#ddd';
        };

        const span = document.createElement('span');
        span.style.fontSize = '1rem';
        span.style.flex = '1';
        span.innerHTML = `<div style="font-weight: 500; line-height: 1.2;">${labelText}</div><div style="font-size: 0.85rem; color: #666; line-height: 1;">${dayName}</div>`;

        wrapper.appendChild(input);
        wrapper.appendChild(span);
        container.appendChild(wrapper);
        return input;
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

        const res = await fetch(isEditMode ? updateStep1Url : step1Url, {
            method: isEditMode ? 'PUT' : 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json',
            },
            body: JSON.stringify(payload),
        });
        const json = await res.json();
        if (res.ok && json.success) {
            if (isEditMode) {
                window.location.href = `/admin/lop-hoc/${classSectionId}/chi-tiet`;
            } else {
                window.location.href = '/admin/lop-hoc/tao-buoc-2';
            }
        } else {
            alert(json.message || `Không thể ${isEditMode ? 'cập nhật' : 'lưu'} bước 1`);
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
            const majors = Array.isArray(json.data) ? json.data : (json.data?.majors || []);
            addOptions(selects.major, majors, (m) => ({ value: m.id, label: m.name }));
        } else {
            addOptions(selects.major, [], () => ({ value: '', label: '-- Chọn --' }));
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
                return;
            }
            
            const data = json.data.class_info || json.data;
            if (!data) {
                alert('Không thể tải thông tin lớp học');
                return;
            }
            
            // Set academic year first, then trigger semester load
            if (data.academic_year_id) {
                selects.academicYear.value = data.academic_year_id;
                await loadSemestersByYear(data.academic_year_id);
            }
            
            // Set semester
            if (data.semester_id) {
                selects.semester.value = data.semester_id;
            }
            
            // Set faculty first, then trigger major load
            if (data.faculty_id) {
                selects.faculty.value = data.faculty_id;
                await loadMajorsByFaculty(data.faculty_id);
            }
            
            // Set major, then trigger course load
            if (data.major_id) {
                selects.major.value = data.major_id;
                await loadCoursesByMajor(data.major_id);
            }
            
            // Set course version
            if (data.course_version_id) {
                selects.course.value = data.course_version_id;
            }
            
            // Set class code
            if (data.class_code && classCodeInput) {
                classCodeInput.value = data.class_code;
            }
            
            // Set time slot
            if (data.time_slot_id) {
                selects.timeSlot.value = data.time_slot_id;
            }
            
            // Set room
            if (data.room_id) {
                selects.room.value = data.room_id;
            }
            
            // Set capacity
            if (data.capacity && capacityInput) {
                capacityInput.value = data.capacity;
            }
            
            // Set meeting dates - ensure checkboxes exist and check them
            if (json.data.meetings && json.data.meetings.length > 0) {
                const existingDates = json.data.meetings.map(m => m.meeting_date);
                existingDates.forEach(dateVal => {
                    const cb = ensureMeetingDateCheckbox(dateVal) || document.querySelector(`.meeting-date-checkbox[value="${dateVal}"]`);
                    if (cb) {
                        cb.checked = true;
                        cb.parentElement.style.background = '#e3f2fd';
                        cb.parentElement.style.borderColor = '#1976d2';
                    }
                });
            }
            
            updateSummary();
        } catch (error) {
            console.error('Error loading existing data:', error);
            alert('Lỗi khi tải thông tin lớp học');
        }
    }

    document.addEventListener('DOMContentLoaded', async () => {
        await loadOptions();
        bindSummaryUpdates();
        bindCascadingDropdowns();
        
        // Update button text for edit mode
        if (isEditMode && continueBtn) {
            continueBtn.textContent = 'Cập nhật';
        }
        
        // Load existing data in edit mode
        if (isEditMode && detailUrl) {
            await loadExistingData();
        }
        
        continueBtn?.addEventListener('click', submitStep1);
    });
})();
