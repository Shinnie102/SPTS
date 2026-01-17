// ===== MODAL MANAGEMENT (GLOBAL SCOPE) =====
const modalOverlay = document.getElementById('modalOverlay');
let currentModal = null;

function openModal(selector) {
    // Đóng modal hiện tại nếu có
    if (currentModal) {
        currentModal.classList.remove('active');
    }
    
    const modal = document.querySelector(selector);
    if (modal) {
        modal.classList.add('active');
        modalOverlay.classList.add('active');
        currentModal = modal;
        
        // CHỈ reset form nếu đây là modal thêm mới (không phải modal edit)
        // Modal edit sẽ có id input chứa giá trị
        const isEditModal = modal.classList.contains('chitiethocphan');
        const hasDataToEdit = isEditModal && document.getElementById('edit-course-id')?.value;
        
        if (!hasDataToEdit) {
            // Reset form khi mở modal thêm mới
            const inputs = modal.querySelectorAll('input:not([type="hidden"]), select');
            inputs.forEach(input => {
                if (input.tagName === 'SELECT') {
                    input.selectedIndex = 0;
                } else if (!input.hasAttribute('disabled')) {
                    input.value = '';
                }
            });
        }
        
        // Validate button state khi mở modal
        validateModalButtons(modal);
    }
}

function closeModal() {
    if (currentModal) {
        currentModal.classList.remove('active');
        currentModal = null;
    }
    modalOverlay.classList.remove('active');
}

function validateModalButtons(modal) {
    if (!modal) return;

    // Lấy tất cả input và select có required
    const requiredFields = modal.querySelectorAll('input[required], select[required]');
    
    // Kiểm tra tất cả field có span (*)
    const fieldsWithAsterisk = [];
    modal.querySelectorAll('.infor span').forEach(span => {
        if (span.textContent.trim() === '(*)') {
            const infoLabel = span.closest('.infor');
            const nextInput = infoLabel.nextElementSibling;
            if (nextInput && (nextInput.tagName === 'INPUT' || nextInput.tagName === 'SELECT')) {
                fieldsWithAsterisk.push(nextInput);
            }
        }
    });

    // Update button states
    const editBtn = modal.querySelector('.Chinhsua');
    const addBtn = modal.querySelector('.them');
    
    // Kiểm tra xem có field nào trống không
    const hasEmptyField = fieldsWithAsterisk.some(field => !field.value || field.value.trim() === '');

    if (editBtn) {
        if (hasEmptyField) {
            editBtn.disabled = true;
            editBtn.style.opacity = '0.5';
            editBtn.style.cursor = 'not-allowed';
        } else {
            editBtn.disabled = false;
            editBtn.style.opacity = '1';
            editBtn.style.cursor = 'pointer';
        }
    }

    if (addBtn) {
        if (hasEmptyField) {
            addBtn.disabled = true;
            addBtn.style.opacity = '0.5';
            addBtn.style.cursor = 'not-allowed';
        } else {
            addBtn.disabled = false;
            addBtn.style.opacity = '1';
            addBtn.style.cursor = 'pointer';
        }
    }
}

document.addEventListener('DOMContentLoaded', function() {
    // ===== MODAL SETUP =====
    const frameNewElements = document.querySelectorAll('.frame-new');

    // Không cho phép close modal bằng overlay click
    modalOverlay.addEventListener('click', function(e) {
        // Chỉ đóng khi click vào overlay, không phải vào modal
        if (e.target === modalOverlay) {
            // Không cho phép đóng
        }
    });

    // ===== MODAL EVENT LISTENERS =====
    frameNewElements.forEach(frame => {
        // Close modal when clicking X button
        const closeBtn = frame.querySelector('.fa-xmark');
        if (closeBtn) {
            closeBtn.addEventListener('click', closeModal);
        }
        
        // Close modal when clicking Hủy button
        const cancelBtn = frame.querySelector('.Huy');
        if (cancelBtn) {
            cancelBtn.addEventListener('click', closeModal);
        }

        // Validate on input change
        const inputs = frame.querySelectorAll('input, select');
        inputs.forEach(input => {
            input.addEventListener('input', function() {
                validateModalButtons(frame);
            });
            input.addEventListener('change', function() {
                validateModalButtons(frame);
            });
        });

        // Handle Chỉnh sửa and Thêm buttons
        const editBtn = frame.querySelector('.Chinhsua');
        const addBtn = frame.querySelector('.them');

        if (editBtn) {
            editBtn.addEventListener('click', function(e) {
                if (!this.disabled) {
                    // Gọi API hoặc xử lý lưu dữ liệu ở đây
                    console.log('Chỉnh sửa dữ liệu');
                    closeModal();
                }
                e.preventDefault();
            });
        }

        if (addBtn) {
            addBtn.addEventListener('click', function(e) {
                e.preventDefault();
                
                if (this.disabled) return;
                
                // Kiểm tra modal nào đang mở
                if (frame.classList.contains('tao-khoa')) {
                    // Modal thêm khoa/viện
                    handleAddFaculty();
                } else if (frame.classList.contains('them-nganh')) {
                    // Modal thêm chuyên ngành
                    handleAddMajor();
                } else {
                    // Các modal khác - xử lý sau
                    console.log('Thêm dữ liệu mới');
                    closeModal();
                }
            });
        }
    });

    // ===== BUTTON EVENT LISTENERS =====
    // Nút Thêm Khoa/Viện
    const addKhoavienBtn = document.getElementById('add-khoavien');
    if (addKhoavienBtn) {
        addKhoavienBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            openModal('.tao-khoa');
        });
    }

    // Nút Thêm Chuyên ngành
    const addChuyennganh = document.getElementById('them-chuyennganh');
    if (addChuyennganh) {
        // Tìm tất cả button có id="them-chuyennganh"
        document.querySelectorAll('#them-chuyennganh').forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.stopPropagation();
                openModal('.them-nganh');
            });
        });
    }

    // Nút Thêm Học phần
    const addHocphanBtn = document.getElementById('add-hocphan');
    if (addHocphanBtn) {
        addHocphanBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            loadCourseFaculties();
            loadCourseGradingSchemes();
            openModal('.themhocphan');
        });
    }

    // Cascading dropdown: Faculty → Major trong modal Course
    const courseFacultySelect = document.getElementById('course-faculty-select');
    if (courseFacultySelect) {
        courseFacultySelect.addEventListener('change', function(e) {
            const facultyId = e.target.value;
            loadCourseMajorsByFaculty(facultyId);
        });
    }

    // Validate course code khi người dùng nhập
    const courseCodeInput = document.getElementById('course-code-input');
    if (courseCodeInput) {
        let checkTimeout;
        courseCodeInput.addEventListener('input', function(e) {
            const code = e.target.value.trim().toUpperCase();
            e.target.value = code;
            
            // Clear previous timeout
            clearTimeout(checkTimeout);
            
            // Nếu mã quá ngắn, không check
            if (code.length < 3) {
                document.getElementById('course-code-validation').innerHTML = '';
                return;
            }
            
            // Debounce: chỉ check sau 500ms người dùng ngừng gõ
            checkTimeout = setTimeout(() => {
                checkCourseCode(code);
            }, 500);
        });
    }

    // Nút Submit Course
    const submitCourseBtn = document.getElementById('submit-course-btn');
    if (submitCourseBtn) {
        submitCourseBtn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            handleAddCourse();
        });
    }

    // Nút Update Course
    const updateCourseBtn = document.getElementById('update-course-btn');
    if (updateCourseBtn) {
        updateCourseBtn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            handleUpdateCourse();
        });
    }

    // Cascading dropdown: Faculty → Major trong modal Edit Course
    const editCourseFacultySelect = document.getElementById('edit-course-faculty-select');
    if (editCourseFacultySelect) {
        editCourseFacultySelect.addEventListener('change', function(e) {
            const facultyId = e.target.value;
            loadEditCourseMajorsByFaculty(facultyId);
        });
    }

    // Nút Edit - sẽ được re-bind sau khi load courses
    // (sử dụng event delegation ở dưới)

    // ===== MENU TOGGLE =====
    const menuItems = document.querySelectorAll('#top-content .menu-in');
    const frameKhoaVien = document.querySelector('.frame-KhoaVien');
    const frameHocphan = document.querySelector('#frame-hocphan');

    menuItems.forEach(function(menuItem, index) {
        menuItem.addEventListener('click', function() {
            // Xóa active-nav từ tất cả menu items
            menuItems.forEach(item => item.classList.remove('active-nav'));
            // Thêm active-nav cho menu item được click
            this.classList.add('active-nav');

            if (index === 0) {
                // Click vào "Khoa/Viện"
                frameKhoaVien.style.display = 'block';
                frameHocphan.style.display = 'none';
            } else {
                // Click vào "Học phần"
                frameKhoaVien.style.display = 'none';
                frameHocphan.style.display = 'block';
            }
        });
    });

    // ===== KHUNG-KHOAVIEN ACCORDION =====
    initAccordion();

    // ===== SEARCH FUNCTIONALITY =====
    const searchInput = document.getElementById('tim-kiem');
    if (searchInput) {
        searchInput.addEventListener('input', function(e) {
            const searchTerm = e.target.value.toLowerCase();
            
            // Tìm kiếm trong Khoa/Viện
            document.querySelectorAll('.khung-khoavien').forEach(item => {
                const tenKhoa = item.querySelector('.ten-khoa');
                const maKhoa = item.querySelector('.ma-khoa');
                
                if (tenKhoa && maKhoa) {
                    const text = (tenKhoa.textContent + ' ' + maKhoa.textContent).toLowerCase();
                    const khoanganhContainer = item.closest('.khung-khoanganh');
                    
                    if (text.includes(searchTerm) || searchTerm === '') {
                        khoanganhContainer.style.display = 'block';
                    } else {
                        khoanganhContainer.style.display = 'none';
                    }
                }
            });

            // Tìm kiếm trong bảng Học phần
            document.querySelectorAll('.course-table tbody tr').forEach(row => {
                const cells = row.querySelectorAll('td');
                let text = '';
                cells.forEach(cell => {
                    text += cell.textContent.toLowerCase() + ' ';
                });
                
                if (text.includes(searchTerm) || searchTerm === '') {
                    row.style.display = 'table-row';
                } else {
                    row.style.display = 'none';
                }
            });
        });
    }

});

// ===== ACCORDION FUNCTION (CAN BE CALLED MULTIPLE TIMES) =====
function initAccordion() {
    const khoavienElements = document.querySelectorAll('.khung-khoavien');

    khoavienElements.forEach(function(khoavien) {
        // Remove old listener if exists (prevent duplicate)
        khoavien.replaceWith(khoavien.cloneNode(true));
    });
    
    // Re-query after clone
    document.querySelectorAll('.khung-khoavien').forEach(function(khoavien) {
        khoavien.addEventListener('click', function(e) {
            // Nếu click vào icon trash, không toggle accordion
            if (e.target.closest('.fa-trash')) {
                return;
            }
            
            // Lấy icon trong khung-khoavien
            const icon = this.querySelector('.khoa-vien .fa-solid');
            
            // Lấy phần tử khung-chuyennganh gần nhất (anh em)
            const khoanganhContainer = this.closest('.khung-khoanganh');
            const khungChuyennganh = khoanganhContainer.querySelector('.khung-chuyennganh');

            // Đổi icon
            if (icon.classList.contains('fa-angle-right')) {
                icon.classList.remove('fa-angle-right');
                icon.classList.add('fa-angle-down');
                // Hiển thị khung-chuyennganh với animation
                khungChuyennganh.classList.add('active');
            } else {
                icon.classList.remove('fa-angle-down');
                icon.classList.add('fa-angle-right');
                // Ẩn khung-chuyennganh với animation
                khungChuyennganh.classList.remove('active');
            }
        });
    });
}

// ===== FAKE SELECT DROPDOWN =====
function initFakeSelect() {
    document.querySelectorAll(".fake-select").forEach(select => {
        const selected = select.querySelector(".selected");
        const options = select.querySelector(".options");
        const hiddenInput = select.querySelector("input");

        // Remove old listeners by cloning
        const newSelected = selected.cloneNode(true);
        selected.replaceWith(newSelected);

        newSelected.addEventListener("click", () => {
            document.querySelectorAll(".options").forEach(o => {
                if (o !== options) o.style.display = "none";
            });
            options.style.display = options.style.display === "block" ? "none" : "block";
        });

        select.querySelectorAll(".option").forEach(option => {
            const newOption = option.cloneNode(true);
            option.replaceWith(newOption);
            
            newOption.addEventListener("click", () => {
                newSelected.innerHTML = newOption.textContent + ' <i class="fa-solid fa-angle-down"></i>';
                hiddenInput.value = newOption.dataset.value;
                options.style.display = "none";
            });
        });
    });
}

// Init on page load
initFakeSelect();

document.addEventListener("click", e => {
    if (!e.target.closest(".fake-select")) {
        document.querySelectorAll(".options").forEach(o => o.style.display = "none");
    }
});

// ========================================
// API INTEGRATION CODE
// ========================================

// ============ CSRF TOKEN ============
const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

// ============ STATE MANAGEMENT ============
let currentTabAPI = 'faculty'; // 'faculty' hoặc 'course'
let currentFacultyId = null;
let facultiesData = [];
let coursesData = [];

// ============ API INITIALIZATION ============
document.addEventListener('DOMContentLoaded', function() {
    initTabSwitchingAPI();
    initSearchHandlerAPI();
    initFilterHandlers(); // Thêm filter event handlers
    loadFaculties();
});

// ============ FILTER EVENT HANDLERS ============
function initFilterHandlers() {
    // Event delegation cho faculty filter
    document.addEventListener('click', function(e) {
        const facultyOption = e.target.closest('.fake-select[data-name="khoa"] .option');
        if (facultyOption && currentTabAPI === 'course') {
            const facultyId = facultyOption.dataset.value;
            const keyword = document.getElementById('tim-kiem')?.value.trim() || '';
            
            console.log(`Faculty option clicked: ${facultyId}`);
            
            // Load courses và majors
            setTimeout(() => {
                loadCourses(keyword, facultyId, '', 1);
                loadMajorsByFaculty(facultyId);
            }, 100); // Delay nhỏ để đợi initFakeSelect update hidden input
        }
    });
    
    // Event delegation cho major filter
    document.addEventListener('click', function(e) {
        const majorOption = e.target.closest('.fake-select[data-name="chuyen-nganh"] .option');
        if (majorOption && currentTabAPI === 'course') {
            const majorId = majorOption.dataset.value;
            const facultySelect = document.querySelector('.fake-select[data-name="khoa"] input[type="hidden"]');
            const keyword = document.getElementById('tim-kiem')?.value.trim() || '';
            const facultyId = facultySelect ? facultySelect.value : '';
            
            console.log(`Major option clicked: ${majorId}`);
            
            // Load courses
            setTimeout(() => {
                loadCourses(keyword, facultyId, majorId, 1);
            }, 100);
        }
    });
}

// ============ TAB SWITCHING API ============
function initTabSwitchingAPI() {
    const tabs = document.querySelectorAll('.menu-in');
    
    tabs.forEach((tab, index) => {
        tab.addEventListener('click', function() {
            if (index === 0) {
                currentTabAPI = 'faculty';
                loadFaculties();
            } else {
                currentTabAPI = 'course';
                loadCourses('', '', '', 1);
                loadFacultyFilter();
            }
        });
    });
}

// ============ SEARCH HANDLER API ============
function initSearchHandlerAPI() {
    const searchInput = document.getElementById('tim-kiem');
    if (!searchInput) return;
    
    let searchTimeout;
    
    searchInput.addEventListener('input', function() {
        clearTimeout(searchTimeout);
        
        searchTimeout = setTimeout(() => {
            const keyword = this.value.trim();
            
            if (currentTabAPI === 'faculty') {
                loadFaculties(keyword);
            } else {
                loadCourses(keyword, '', '', 1);
            }
        }, 500);
    });
}

// ============ FACULTY FUNCTIONS ============
async function loadFaculties(keyword = '') {
    try {
        const params = new URLSearchParams();
        if (keyword) params.append('keyword', keyword);
        params.append('per_page', 50);
        
        const response = await fetch(`/admin/hoc-thuat/faculty/api?${params}`, {
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json',
            }
        });
        
        const result = await response.json();
        
        if (result.success) {
            facultiesData = result.data.faculties;
            renderFaculties(facultiesData);
        } else {
            showError('Không thể tải danh sách Khoa/Viện');
        }
    } catch (error) {
        console.error('Error loading faculties:', error);
        showError('Lỗi kết nối: ' + error.message);
    }
}

function renderFaculties(faculties) {
    const facultyFrame = document.querySelector('.frame-KhoaVien');
    if (!facultyFrame) return;
    
    facultyFrame.innerHTML = '';
    
    const addButton = document.createElement('button');
    addButton.id = 'add-khoavien';
    addButton.className = 'add';
    addButton.innerHTML = '<i class="fa-solid fa-plus"></i> Thêm Khoa/Viện';
    addButton.onclick = () => openModal('.tao-khoa');
    facultyFrame.appendChild(addButton);
    
    if (faculties.length === 0) {
        const emptyMsg = document.createElement('div');
        emptyMsg.className = 'empty-message';
        emptyMsg.textContent = 'Chưa có dữ liệu Khoa/Viện';
        facultyFrame.appendChild(emptyMsg);
        return;
    }
    
    faculties.forEach(faculty => {
        const card = createFacultyCard(faculty);
        facultyFrame.appendChild(card);
    });
    
    // Re-init accordion for new elements
    initAccordion();
}

function createFacultyCard(faculty) {
    const card = document.createElement('div');
    card.className = 'khung-khoanganh';
    card.dataset.facultyId = faculty.faculty_id;
    
    const statusText = faculty.status?.faculty_status_id === 1 ? 'Hoạt động' : 'Không hoạt động';
    const majorCount = faculty.majors ? faculty.majors.length : 0;
    
    card.innerHTML = `
        <div class="khung-khoavien">
            <div class="left">
                <div class="khoa-vien">
                    <i class="fa-solid fa-angle-right"></i>
                    <p class="ten-khoa">${escapeHtml(faculty.faculty_name)}</p>
                </div>
                <p class="frame-ma">Mã: <span class="ma-khoa">${escapeHtml(faculty.faculty_code)}</span></p>
            </div>
            <div class="right">
                <div class="frame-loai">
                    <p class="loai">Khoa</p>
                </div>
                <div class="frame-chuyennganh">
                    <p class="chuyennganh">Chuyên ngành: <span class="soluong">${majorCount}</span></p>
                </div>
                <div class="frame-status">
                    <p class="status">${statusText}</p>
                </div>
                <i class="fa-solid fa-trash" onclick="deleteFaculty(${faculty.faculty_id})"></i>
            </div>
        </div>
        <div class="khung-chuyennganh">
            <div class="title-chuyennganh">
                <p>Chuyên ngành:</p>
                <button id="them-chuyennganh" onclick="openMajorModal(${faculty.faculty_id})">
                    <i class="fa-solid fa-plus"></i>
                    Thêm
                </button>
            </div>
            <div class="khung-cacnganh">
                ${renderMajors(faculty.majors || [])}
            </div>
        </div>
    `;
    
    return card;
}

function renderMajors(majors) {
    if (majors.length === 0) {
        return '<p style="text-align: center; color: #999; padding: 20px;">Chưa có chuyên ngành</p>';
    }
    
    return majors.map(major => `
        <div class="nghanh">
            <div class="left">
                <p class="tennghanh">${escapeHtml(major.major_name)}</p>
                <p class="manganh">Mã <span class="ma">${escapeHtml(major.major_code)}</span></p>
            </div>
            <div class="right">
                <i class="fa-solid fa-trash" onclick="deleteMajor(${major.major_id})"></i>
            </div>
        </div>
    `).join('');
}

async function createFaculty(facultyData) {
    try {
        const response = await fetch('/admin/hoc-thuat/faculty/api', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json',
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(facultyData)
        });
        
        const result = await response.json();
        
        if (result.success) {
            showSuccess(result.message || 'Thêm Khoa/Viện thành công');
            closeModal();
            loadFaculties(); // Reload danh sách
            
            // Clear form
            document.getElementById('faculty-name-input').value = '';
            document.getElementById('faculty-code-input').value = '';
        } else {
            showError(result.message || 'Không thể thêm Khoa/Viện');
        }
    } catch (error) {
        console.error('Error creating faculty:', error);
        showError('Lỗi khi thêm Khoa/Viện: ' + error.message);
    }
}

function handleAddFaculty() {
    const nameInput = document.getElementById('faculty-name-input');
    const codeInput = document.getElementById('faculty-code-input');
    
    const facultyName = nameInput?.value.trim();
    const facultyCode = codeInput?.value.trim();
    
    // Validation
    if (!facultyName || !facultyCode) {
        showError('Vui lòng nhập đầy đủ thông tin');
        return;
    }
    
    // Gọi API
    createFaculty({
        faculty_name: facultyName,
        faculty_code: facultyCode,
        faculty_status_id: 1 // Mặc định là hoạt động
    });
}

async function createMajor(majorData) {
    try {
        const response = await fetch('/admin/hoc-thuat/major/api', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json',
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(majorData)
        });
        
        const result = await response.json();
        
        if (result.success) {
            showSuccess(result.message || 'Thêm Chuyên ngành thành công');
            closeModal();
            loadFaculties(); // Reload danh sách faculty để cập nhật majors
            
            // Clear form
            document.getElementById('major-name-input').value = '';
            document.getElementById('major-code-input').value = '';
            document.getElementById('major-faculty-id').value = '';
        } else {
            showError(result.message || 'Không thể thêm Chuyên ngành');
        }
    } catch (error) {
        console.error('Error creating major:', error);
        showError('Lỗi khi thêm Chuyên ngành: ' + error.message);
    }
}

function handleAddMajor() {
    const nameInput = document.getElementById('major-name-input');
    const codeInput = document.getElementById('major-code-input');
    const facultyIdInput = document.getElementById('major-faculty-id');
    
    const majorName = nameInput?.value.trim();
    const majorCode = codeInput?.value.trim();
    const facultyId = facultyIdInput?.value;
    
    // Validation
    if (!majorName || !majorCode) {
        showError('Vui lòng nhập đầy đủ thông tin');
        return;
    }
    
    if (!facultyId) {
        showError('Không xác định được Khoa/Viện');
        return;
    }
    
    // Gọi API
    createMajor({
        major_name: majorName,
        major_code: majorCode,
        faculty_id: parseInt(facultyId), // Single faculty ID
        major_status_id: 1 // Mặc định là hoạt động
    });
}

async function deleteFaculty(facultyId) {
    if (!confirm('Bạn có chắc chắn muốn xóa Khoa/Viện này?\nTất cả Chuyên ngành liên quan cũng sẽ bị xóa.')) {
        return;
    }
    
    try {
        const response = await fetch(`/admin/hoc-thuat/faculty/api/${facultyId}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json',
            }
        });
        
        const result = await response.json();
        
        if (result.success) {
            showSuccess(result.message);
            loadFaculties();
        } else {
            showError(result.message);
        }
    } catch (error) {
        console.error('Error deleting faculty:', error);
        showError('Lỗi khi xóa Khoa/Viện: ' + error.message);
    }
}

async function deleteMajor(majorId) {
    if (!confirm('Bạn có chắc chắn muốn xóa Chuyên ngành này?')) {
        return;
    }
    
    try {
        const response = await fetch(`/admin/hoc-thuat/major/api/${majorId}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json',
            }
        });
        
        const result = await response.json();
        
        if (result.success) {
            showSuccess(result.message);
            loadFaculties();
        } else {
            showError(result.message);
        }
    } catch (error) {
        console.error('Error deleting major:', error);
        showError('Lỗi khi xóa Chuyên ngành: ' + error.message);
    }
}

// ============ COURSE MODAL FUNCTIONS ============
async function checkCourseCode(code) {
    try {
        const response = await fetch(`/admin/hoc-thuat/course/api/check-code/${encodeURIComponent(code)}`, {
            headers: {
                'Accept': 'application/json',
            }
        });
        
        const result = await response.json();
        const validationDiv = document.getElementById('course-code-validation');
        
        if (result.exists) {
            let html = '<span style="color: #dc3545;">❌ ' + result.message + '</span>';
            if (result.suggestions && result.suggestions.length > 0) {
                html += '<br><span style="color: #666;">Gợi ý: ';
                result.suggestions.forEach((suggestion, index) => {
                    html += '<a href="#" class="suggestion-link" data-code="' + suggestion + '" style="color: #007bff; text-decoration: underline; margin-right: 10px;">' + suggestion + '</a>';
                });
                html += '</span>';
            }
            validationDiv.innerHTML = html;
            
            // Thêm event listener cho các link gợi ý
            validationDiv.querySelectorAll('.suggestion-link').forEach(link => {
                link.addEventListener('click', function(e) {
                    e.preventDefault();
                    const suggestedCode = this.dataset.code;
                    document.getElementById('course-code-input').value = suggestedCode;
                    checkCourseCode(suggestedCode);
                });
            });
        } else {
            validationDiv.innerHTML = '<span style="color: #28a745;">✓ ' + result.message + '</span>';
        }
    } catch (error) {
        console.error('Error checking course code:', error);
    }
}

async function loadCourseFaculties() {
    try {
        const response = await fetch('/admin/hoc-thuat/faculty/api/active', {
            headers: {
                'Accept': 'application/json',
            }
        });
        
        const result = await response.json();
        const select = document.getElementById('course-faculty-select');
        
        if (result.success && select) {
            select.innerHTML = '<option value="">-- Chọn Khoa/Viện --</option>';
            result.data.forEach(faculty => {
                const option = document.createElement('option');
                option.value = faculty.faculty_id;
                option.textContent = `${faculty.faculty_name} (${faculty.faculty_code})`;
                select.appendChild(option);
            });
        }
    } catch (error) {
        console.error('Error loading faculties:', error);
    }
}

async function loadCourseMajorsByFaculty(facultyId) {
    const select = document.getElementById('course-major-select');
    
    if (!facultyId) {
        select.innerHTML = '<option value="">-- Chọn Khoa/Viện trước --</option>';
        return;
    }
    
    try {
        const response = await fetch(`/admin/hoc-thuat/major/api/by-faculty/${facultyId}`, {
            headers: {
                'Accept': 'application/json',
            }
        });
        
        const result = await response.json();
        
        if (result.success && select) {
            select.innerHTML = '<option value="">-- Chọn chuyên ngành --</option>';
            result.data.forEach(major => {
                const option = document.createElement('option');
                option.value = major.major_id;
                option.textContent = `${major.major_name} (${major.major_code})`;
                select.appendChild(option);
            });
        }
    } catch (error) {
        console.error('Error loading majors:', error);
    }
}

async function loadCourseMajors() {
    try {
        const response = await fetch('/admin/hoc-thuat/major/api/active', {
            headers: {
                'Accept': 'application/json',
            }
        });
        
        const result = await response.json();
        const select = document.getElementById('course-major-select');
        
        if (result.success && select) {
            select.innerHTML = '<option value="">-- Chọn chuyên ngành --</option>';
            result.data.forEach(major => {
                const option = document.createElement('option');
                option.value = major.major_id;
                option.textContent = `${major.major_name} (${major.major_code})`;
                select.appendChild(option);
            });
        }
    } catch (error) {
        console.error('Error loading majors:', error);
    }
}

async function loadCourseGradingSchemes() {
    try {
        const response = await fetch('/admin/hoc-thuat/grading-schemes/api/active', {
            headers: {
                'Accept': 'application/json',
            }
        });
        
        const result = await response.json();
        const select = document.getElementById('course-grading-scheme-select');
        
        if (result.success && select) {
            select.innerHTML = '<option value="">-- Chọn cấu trúc điểm --</option>';
            result.data.forEach(scheme => {
                const option = document.createElement('option');
                option.value = scheme.grading_scheme_id;
                option.textContent = scheme.scheme_name;
                select.appendChild(option);
            });
        }
    } catch (error) {
        console.error('Error loading grading schemes:', error);
    }
}

async function createCourse(data) {
    try {
        const response = await fetch('/admin/hoc-thuat/course/api', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Content-Type': 'application/json',
                'Accept': 'application/json',
            },
            body: JSON.stringify(data)
        });
        
        const result = await response.json();
        
        if (result.success) {
            showSuccess(result.message);
            closeModal();
            loadCourses();
        } else {
            showError(result.message);
        }
    } catch (error) {
        console.error('Error creating course:', error);
        showError('Lỗi khi thêm Học phần: ' + error.message);
    }
}

function handleAddCourse() {
    const codeInput = document.getElementById('course-code-input');
    const nameInput = document.getElementById('course-name-input');
    const creditInput = document.getElementById('course-credit-input');
    const majorSelect = document.getElementById('course-major-select');
    const gradingSchemeSelect = document.getElementById('course-grading-scheme-select');
    
    const courseCode = codeInput?.value.trim().toUpperCase();
    const courseName = nameInput?.value.trim();
    const credit = creditInput?.value;
    const majorId = majorSelect?.value;
    const gradingSchemeId = gradingSchemeSelect?.value;
    
    // Validation
    if (!courseCode || !courseName || !credit || !majorId) {
        showError('Vui lòng nhập đầy đủ thông tin bắt buộc');
        return;
    }
    
    // Build request data
    const requestData = {
        course_code: courseCode,
        course_name: courseName,
        credit: parseInt(credit),
        major_ids: [parseInt(majorId)],
    };
    
    if (gradingSchemeId) {
        requestData.grading_scheme_id = parseInt(gradingSchemeId);
    }
    
    // Gọi API
    createCourse(requestData);
}

async function loadCourseDetail(courseId) {
    try {
        // Show modal first with loading state
        openModal('.chitiethocphan');
        
        const response = await fetch(`/admin/hoc-thuat/course/api/${courseId}`, {
            headers: {
                'Accept': 'application/json',
            }
        });
        
        const result = await response.json();
        
        if (result.success && result.data) {
            const course = result.data;
            console.log('Course data:', course);
            console.log('Latest version:', course.latest_version);
            
            // Load dropdowns
            await loadEditCourseFaculties();
            await loadEditCourseGradingSchemes();
            
            // Fill data
            document.getElementById('edit-course-id').value = course.course_id;
            document.getElementById('edit-course-code').value = course.course_code;
            document.getElementById('edit-course-name').value = course.course_name;
            
            // Get latest version for credit - Try both camelCase and snake_case
            const latestVersion = course.latest_version || course.latestVersion;
            if (latestVersion) {
                const credit = latestVersion.credit;
                console.log('Setting credit:', credit);
                document.getElementById('edit-course-credit').value = credit;
                
                // Set grading scheme nếu có
                const gradingSchemeSelect = document.getElementById('edit-course-grading-scheme-select');
                if (latestVersion.grading_scheme_id) {
                    gradingSchemeSelect.value = latestVersion.grading_scheme_id;
                }
            }
            
            // Get first major's faculty
            if (course.majors && course.majors.length > 0) {
                const firstMajor = course.majors[0];
                if (firstMajor.faculties && firstMajor.faculties.length > 0) {
                    const facultyId = firstMajor.faculties[0].faculty_id;
                    document.getElementById('edit-course-faculty-select').value = facultyId;
                    
                    // Load majors by faculty
                    await loadEditCourseMajorsByFaculty(facultyId);
                    
                    // Set major
                    document.getElementById('edit-course-major-select').value = firstMajor.major_id;
                }
            }
        } else {
            closeModal();
            showError('Không thể tải thông tin học phần');
        }
    } catch (error) {
        console.error('Error loading course detail:', error);
        closeModal();
        showError('Lỗi khi tải thông tin học phần');
    }
}

async function loadEditCourseFaculties() {
    try {
        const response = await fetch('/admin/hoc-thuat/faculty/api/active', {
            headers: {
                'Accept': 'application/json',
            }
        });
        
        const result = await response.json();
        const select = document.getElementById('edit-course-faculty-select');
        
        if (result.success && select) {
            select.innerHTML = '<option value="">-- Chọn Khoa/Viện --</option>';
            result.data.forEach(faculty => {
                const option = document.createElement('option');
                option.value = faculty.faculty_id;
                option.textContent = `${faculty.faculty_name} (${faculty.faculty_code})`;
                select.appendChild(option);
            });
        }
    } catch (error) {
        console.error('Error loading faculties:', error);
    }
}

async function loadEditCourseMajorsByFaculty(facultyId) {
    const select = document.getElementById('edit-course-major-select');
    
    if (!facultyId) {
        select.innerHTML = '<option value="">-- Chọn Khoa/Viện trước --</option>';
        return;
    }
    
    try {
        const response = await fetch(`/admin/hoc-thuat/major/api/by-faculty/${facultyId}`, {
            headers: {
                'Accept': 'application/json',
            }
        });
        
        const result = await response.json();
        
        if (result.success && select) {
            select.innerHTML = '<option value="">-- Chọn chuyên ngành --</option>';
            result.data.forEach(major => {
                const option = document.createElement('option');
                option.value = major.major_id;
                option.textContent = `${major.major_name} (${major.major_code})`;
                select.appendChild(option);
            });
        }
    } catch (error) {
        console.error('Error loading majors:', error);
    }
}

async function loadEditCourseGradingSchemes() {
    try {
        const response = await fetch('/admin/hoc-thuat/grading-schemes/api/active', {
            headers: {
                'Accept': 'application/json',
            }
        });
        
        const result = await response.json();
        const select = document.getElementById('edit-course-grading-scheme-select');
        
        if (result.success && select) {
            select.innerHTML = '<option value="">-- Chọn cấu trúc điểm --</option>';
            result.data.forEach(scheme => {
                const option = document.createElement('option');
                option.value = scheme.grading_scheme_id;
                option.textContent = scheme.scheme_name;
                select.appendChild(option);
            });
        }
    } catch (error) {
        console.error('Error loading grading schemes:', error);
    }
}

async function updateCourse(courseId, data) {
    try {
        const response = await fetch(`/admin/hoc-thuat/course/api/${courseId}`, {
            method: 'PUT',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Content-Type': 'application/json',
                'Accept': 'application/json',
            },
            body: JSON.stringify(data)
        });
        
        const result = await response.json();
        
        if (result.success) {
            showSuccess(result.message);
            closeModal();
            loadCourses();
        } else {
            showError(result.message);
        }
    } catch (error) {
        console.error('Error updating course:', error);
        showError('Lỗi khi cập nhật Học phần: ' + error.message);
    }
}

function handleUpdateCourse() {
    const courseId = document.getElementById('edit-course-id').value;
    const nameInput = document.getElementById('edit-course-name');
    const creditInput = document.getElementById('edit-course-credit');
    const majorSelect = document.getElementById('edit-course-major-select');
    const gradingSchemeSelect = document.getElementById('edit-course-grading-scheme-select');
    
    const courseName = nameInput?.value.trim();
    const credit = creditInput?.value;
    const majorId = majorSelect?.value;
    const gradingSchemeId = gradingSchemeSelect?.value;
    
    // Validation
    if (!courseName || !credit || !majorId) {
        showError('Vui lòng nhập đầy đủ thông tin bắt buộc');
        return;
    }
    
    // Build request data
    const requestData = {
        course_name: courseName,
        credit: parseInt(credit),
        major_ids: [parseInt(majorId)],
    };
    
    if (gradingSchemeId) {
        requestData.grading_scheme_id = parseInt(gradingSchemeId);
    }
    
    // Gọi API
    updateCourse(courseId, requestData);
}

// ============ COURSE FUNCTIONS ============
async function loadCourses(keyword = '', facultyId = '', majorId = '', page = 1) {
    try {
        const params = new URLSearchParams();
        if (keyword) params.append('keyword', keyword);
        if (facultyId && facultyId !== 'ALL') params.append('faculty_id', facultyId);
        if (majorId && majorId !== 'ALL') params.append('major_id', majorId);
        params.append('page', page);
        params.append('per_page', 20);
        
        console.log(`Loading courses - page: ${page}, URL: /admin/hoc-thuat/course/api?${params}`);
        
        const response = await fetch(`/admin/hoc-thuat/course/api?${params}`, {
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json',
            }
        });
        
        const result = await response.json();
        console.log('API Response:', result);
        
        if (result.success) {
            const courses = result.data.courses;
            const pagination = result.data.pagination;
            
            renderCourses(courses, pagination);
        } else {
            showError('Không thể tải danh sách Học phần');
        }
    } catch (error) {
        console.error('Error loading courses:', error);
        showError('Lỗi kết nối: ' + error.message);
    }
}

function renderCourses(courses, pagination) {
    const tableBody = document.querySelector('#frame-hocphan .course-table tbody');
    
    if (!tableBody) {
        console.error('Table body not found');
        return;
    }
    
    tableBody.innerHTML = '';
    
    if (courses.length === 0) {
        tableBody.innerHTML = '<tr><td colspan="6" class="text-center">Chưa có dữ liệu Học phần</td></tr>';
        return;
    }
    
    courses.forEach((course, index) => {
        const row = document.createElement('tr');
        
        // Lấy tên faculty từ major đầu tiên
        let facultyName = 'N/A';
        if (course.majors && course.majors.length > 0 && course.majors[0].faculties && course.majors[0].faculties.length > 0) {
            facultyName = course.majors[0].faculties[0].faculty_name;
        }
        
        // Lấy credits từ latest_version
        const credits = course.latest_version ? course.latest_version.credit : 0;
        
        row.innerHTML = `
            <td>${escapeHtml(course.course_code)}</td>
            <td>${escapeHtml(course.course_name)}</td>
            <td>${credits}</td>
            <td>${escapeHtml(facultyName)}</td>
            <td>${renderCourseMajors(course.majors || [])}</td>
            <td class="actions">
                <button class="edit" onclick="editCourse(${course.course_id})" title="Sửa">
                    <i class="fa-regular fa-pen-to-square"></i>
                </button>
                <button class="delete" onclick="deleteCourse(${course.course_id})" title="Xóa">
                    <i class="fa-solid fa-trash"></i>
                </button>
            </td>
        `;
        tableBody.appendChild(row);
    });
    
    // Render pagination controls
    if (pagination) {
        renderPagination(pagination);
    }
}

function renderCourseMajors(majors) {
    if (majors.length === 0) return 'N/A';
    return majors.map(major => escapeHtml(major.major_name)).join(', ');
}

function renderPagination(pagination) {
    const infoText = document.getElementById('pagination-info-text');
    const controls = document.getElementById('pagination-controls');
    
    if (!infoText || !controls) return;
    
    console.log('Pagination data:', pagination); // Debug
    
    // Update info text
    if (pagination.total > 0) {
        infoText.textContent = `Hiển thị ${pagination.from}-${pagination.to} trong tổng số ${pagination.total} học phần`;
    } else {
        infoText.textContent = 'Không có dữ liệu';
    }
    
    // Clear previous controls
    controls.innerHTML = '';
    
    // Don't show controls if only one page
    if (pagination.last_page <= 1) {
        console.log('Only one page, not showing controls');
        return;
    }
    
    const currentPage = pagination.current_page;
    const lastPage = pagination.last_page;
    
    console.log(`Rendering pagination: currentPage=${currentPage}, lastPage=${lastPage}`);
    
    // Previous button
    const prevBtn = document.createElement('button');
    prevBtn.className = 'pagination-btn';
    prevBtn.innerHTML = '<i class="fa-solid fa-angle-left"></i> Trước';
    prevBtn.disabled = currentPage === 1;
    prevBtn.onclick = () => goToPage(currentPage - 1);
    controls.appendChild(prevBtn);
    
    // Page numbers
    const maxPagesToShow = 5;
    let startPage = Math.max(1, currentPage - Math.floor(maxPagesToShow / 2));
    let endPage = Math.min(lastPage, startPage + maxPagesToShow - 1);
    
    console.log(`Page range: startPage=${startPage}, endPage=${endPage}`);
    
    // Adjust start if we're near the end
    if (endPage - startPage < maxPagesToShow - 1) {
        startPage = Math.max(1, endPage - maxPagesToShow + 1);
    }
    
    // First page if not in range
    if (startPage > 1) {
        const firstBtn = createPageButton(1, currentPage);
        controls.appendChild(firstBtn);
        
        if (startPage > 2) {
            const dots = document.createElement('span');
            dots.className = 'pagination-dots';
            dots.textContent = '...';
            controls.appendChild(dots);
        }
    }
    
    // Page number buttons
    for (let i = startPage; i <= endPage; i++) {
        const pageBtn = createPageButton(i, currentPage);
        controls.appendChild(pageBtn);
    }
    
    // Last page if not in range
    if (endPage < lastPage) {
        if (endPage < lastPage - 1) {
            const dots = document.createElement('span');
            dots.className = 'pagination-dots';
            dots.textContent = '...';
            controls.appendChild(dots);
        }
        
        const lastBtn = createPageButton(lastPage, currentPage);
        controls.appendChild(lastBtn);
    }
    
    // Next button
    const nextBtn = document.createElement('button');
    nextBtn.className = 'pagination-btn';
    nextBtn.innerHTML = 'Sau <i class="fa-solid fa-angle-right"></i>';
    nextBtn.disabled = currentPage === lastPage;
    nextBtn.onclick = () => goToPage(currentPage + 1);
    controls.appendChild(nextBtn);
}

function createPageButton(pageNum, currentPage) {
    const btn = document.createElement('button');
    btn.className = 'pagination-btn page-number';
    if (pageNum === currentPage) {
        btn.classList.add('active');
    }
    btn.textContent = pageNum;
    btn.onclick = () => goToPage(pageNum);
    return btn;
}

function goToPage(page) {
    console.log(`Going to page ${page}`); // Debug
    
    // Get current filter values
    const facultySelect = document.querySelector('.fake-select[data-name="khoa"] input[type="hidden"]');
    const majorSelect = document.querySelector('.fake-select[data-name="chuyen-nganh"] input[type="hidden"]');
    const searchInput = document.querySelector('#frame-hocphan #tim-kiem');
    
    const facultyId = facultySelect ? facultySelect.value : '';
    const majorId = majorSelect ? majorSelect.value : '';
    const keyword = searchInput ? searchInput.value.trim() : '';
    
    console.log(`Filters - keyword: "${keyword}", facultyId: "${facultyId}", majorId: "${majorId}", page: ${page}`);
    
    // Load courses with pagination - ĐÚng THỨ TỰ: keyword, facultyId, majorId, page
    loadCourses(keyword, facultyId, majorId, page);
}

async function deleteCourse(courseId) {
    if (!confirm('Bạn có chắc chắn muốn xóa Học phần này?')) {
        return;
    }
    
    try {
        const response = await fetch(`/admin/hoc-thuat/course/api/${courseId}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json',
            }
        });
        
        const result = await response.json();
        
        if (result.success) {
            showSuccess(result.message);
            const keyword = document.querySelector('#frame-hocphan #tim-kiem')?.value.trim() || '';
            const facultyId = document.querySelector('.fake-select[data-name="khoa"] input')?.value || '';
            const majorId = document.querySelector('.fake-select[data-name="chuyen-nganh"] input')?.value || '';
            loadCourses(keyword, facultyId, majorId, 1);
        } else {
            showError(result.message);
        }
    } catch (error) {
        console.error('Error deleting course:', error);
        showError('Lỗi khi xóa Học phần: ' + error.message);
    }
}

function editCourse(courseId) {
    loadCourseDetail(courseId);
}

async function loadFacultyFilter() {
    try {
        const response = await fetch('/admin/hoc-thuat/faculty/api/active', {
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json',
            }
        });
        
        const result = await response.json();
        
        if (result.success) {
            renderFacultyFilter(result.data);
        }
    } catch (error) {
        console.error('Error loading faculty filter:', error);
    }
}

function renderFacultyFilter(faculties) {
    const fakeSelect = document.querySelector('.fake-select[data-name="khoa"]');
    
    if (!fakeSelect) return;
    
    const optionsContainer = fakeSelect.querySelector('.options');
    const hiddenInput = fakeSelect.querySelector('input[type="hidden"]');
    const selected = fakeSelect.querySelector('.selected');
    
    // Clear và thêm option "Tất cả"
    optionsContainer.innerHTML = '<div class="option" data-value="ALL">Tất cả các khoa</div>';
    
    // Thêm các faculty
    faculties.forEach(faculty => {
        const option = document.createElement('div');
        option.className = 'option';
        option.dataset.value = faculty.faculty_id;
        option.textContent = faculty.faculty_name;
        optionsContainer.appendChild(option);
    });
    
    // Re-init fake-select để gán event cho options mới
    initFakeSelect();
}

async function loadMajorsByFaculty(facultyId) {
    try {
        console.log(`Loading majors for faculty: ${facultyId}`);
        
        const response = await fetch(`/admin/hoc-thuat/major/api/by-faculty/${facultyId}`, {
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json',
            }
        });
        
        const result = await response.json();
        console.log('Majors API response:', result);
        
        if (result.success) {
            renderMajorFilter(result.data);
        }
    } catch (error) {
        console.error('Error loading majors:', error);
    }
}

function renderMajorFilter(majors) {
    const fakeSelect = document.querySelector('.fake-select[data-name="chuyen-nganh"]');
    
    if (!fakeSelect) return;
    
    const optionsContainer = fakeSelect.querySelector('.options');
    const hiddenInput = fakeSelect.querySelector('input[type="hidden"]');
    const selected = fakeSelect.querySelector('.selected');
    
    // Clear và thêm option "Tất cả"
    optionsContainer.innerHTML = '<div class="option" data-value="ALL">Tất cả các chuyên ngành</div>';
    
    // Thêm các major
    majors.forEach(major => {
        const option = document.createElement('div');
        option.className = 'option';
        option.dataset.value = major.major_id;
        option.textContent = major.major_name;
        optionsContainer.appendChild(option);
    });
    
    console.log(`Rendered ${majors.length} majors`);
    
    // Reset selected về "Tất cả"
    selected.innerHTML = 'Tất cả các chuyên ngành <i class="fa-solid fa-angle-down"></i>';
    hiddenInput.value = 'ALL';
    
    // Re-init fake-select để gán event cho options mới
    initFakeSelect();
}

function clearMajorFilter() {
    const fakeSelect = document.querySelector('.fake-select[data-name="chuyen-nganh"]');
    if (!fakeSelect) return;
    
    const optionsContainer = fakeSelect.querySelector('.options');
    const selected = fakeSelect.querySelector('.selected');
    const hiddenInput = fakeSelect.querySelector('input[type="hidden"]');
    
    optionsContainer.innerHTML = '<div class="option" data-value="">Tất cả các chuyên ngành</div>';
    selected.innerHTML = 'Tất cả các chuyên ngành <i class="fa-solid fa-angle-down"></i>';
    hiddenInput.value = '';
}

function openMajorModal(facultyId) {
    currentFacultyId = facultyId;
    
    // Lưu faculty_id vào hidden input
    const facultyIdInput = document.getElementById('major-faculty-id');
    if (facultyIdInput) {
        facultyIdInput.value = facultyId;
    }
    
    openModal('.them-nganh');
}

function editMajor(majorId) {
    console.log('Edit major', majorId);
}

function editCourse(courseId) {
    console.log('Edit course', courseId);
    loadCourseDetail(courseId);
}

// ============ UTILITY FUNCTIONS ============
function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function showSuccess(message) {
    alert('✅ ' + message);
}

function showError(message) {
    alert('❌ ' + message);
}
