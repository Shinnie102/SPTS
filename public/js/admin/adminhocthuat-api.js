/**
 * ========================================
 * ADMIN - CẤU TRÚC HỌC THUẬT
 * File: adminhocthuat-api.js
 * Xử lý API calls và DOM manipulation
 * ========================================
 */

// ============ CSRF TOKEN ============
const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

// ============ STATE MANAGEMENT ============
let currentTab = 'faculty'; // 'faculty' hoặc 'course'
let currentFacultyId = null;
let facultiesData = [];
let coursesData = [];

// ============ DOM ELEMENTS ============
const modalOverlay = document.getElementById('modalOverlay');
const facultyFrame = document.querySelector('.frame-KhoaVien');
const courseFrame = document.getElementById('frame-hocphan');
const searchInput = document.getElementById('tim-kiem');

// ============ INITIALIZATION ============
document.addEventListener('DOMContentLoaded', function() {
    initTabSwitching();
    initSearchHandler();
    loadFaculties();
    initModalHandlers();
});

// ============ TAB SWITCHING ============
function initTabSwitching() {
    const tabs = document.querySelectorAll('.menu-in');
    
    tabs.forEach((tab, index) => {
        tab.addEventListener('click', function() {
            // Remove active class from all tabs
            tabs.forEach(t => t.classList.remove('active-nav'));
            
            // Add active class to clicked tab
            this.classList.add('active-nav');
            
            // Switch content
            if (index === 0) {
                // Khoa/Viện
                currentTab = 'faculty';
                facultyFrame.style.display = 'block';
                courseFrame.style.display = 'none';
                loadFaculties();
            } else {
                // Học phần
                currentTab = 'course';
                facultyFrame.style.display = 'none';
                courseFrame.style.display = 'block';
                loadCourses();
                loadFacultyFilter();
            }
        });
    });
}

// ============ SEARCH HANDLER ============
function initSearchHandler() {
    let searchTimeout;
    
    searchInput.addEventListener('input', function() {
        clearTimeout(searchTimeout);
        
        searchTimeout = setTimeout(() => {
            const keyword = this.value.trim();
            
            if (currentTab === 'faculty') {
                loadFaculties(keyword);
            } else {
                loadCourses(keyword);
            }
        }, 500); // Debounce 500ms
    });
}

// ============ FACULTY FUNCTIONS ============

/**
 * Load danh sách Khoa/Viện
 */
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

/**
 * Render danh sách Khoa/Viện
 */
function renderFaculties(faculties) {
    facultyFrame.innerHTML = '';
    
    // Thêm nút "Thêm Khoa/Viện"
    const addButton = document.createElement('button');
    addButton.id = 'add-khoavien';
    addButton.className = 'add';
    addButton.innerHTML = '<i class="fa-solid fa-plus"></i> Thêm Khoa/Viện';
    addButton.onclick = () => openFacultyModal();
    facultyFrame.appendChild(addButton);
    
    if (faculties.length === 0) {
        const emptyMsg = document.createElement('div');
        emptyMsg.className = 'empty-message';
        emptyMsg.textContent = 'Chưa có dữ liệu Khoa/Viện';
        facultyFrame.appendChild(emptyMsg);
        return;
    }
    
    // Render từng faculty card
    faculties.forEach(faculty => {
        const card = createFacultyCard(faculty);
        facultyFrame.appendChild(card);
    });
}

/**
 * Tạo Faculty Card
 */
function createFacultyCard(faculty) {
    const card = document.createElement('div');
    card.className = 'khung-khoanganh';
    card.dataset.facultyId = faculty.faculty_id;
    
    const statusClass = faculty.status?.status_id === 1 ? 'active' : 'inactive';
    const statusText = faculty.status?.status_id === 1 ? 'Hoạt động' : 'Không hoạt động';
    
    card.innerHTML = `
        <div class="title-nganh">
            <div>
                <div class="khoa-nganh">${escapeHtml(faculty.faculty_name)}</div>
                <div class="code">Mã: ${escapeHtml(faculty.faculty_code)}</div>
            </div>
            <button class="add-nganh" onclick="openMajorModal(${faculty.faculty_id})" title="Thêm Chuyên ngành">
                <i class="fa-solid fa-plus"></i>
            </button>
        </div>
        <div class="status-badge ${statusClass}">${statusText}</div>
        <div class="khung-nganh">
            ${renderMajors(faculty.majors || [])}
        </div>
        <div class="actions">
            <button class="btn-edit" onclick="editFaculty(${faculty.faculty_id})" title="Sửa">
                <i class="fa-solid fa-pen"></i>
            </button>
            <button class="btn-toggle" onclick="toggleFacultyStatus(${faculty.faculty_id})" title="Khóa/Mở">
                <i class="fa-solid fa-lock"></i>
            </button>
            <button class="btn-delete" onclick="deleteFaculty(${faculty.faculty_id})" title="Xóa">
                <i class="fa-solid fa-trash"></i>
            </button>
        </div>
    `;
    
    return card;
}

/**
 * Render danh sách Majors trong Faculty
 */
function renderMajors(majors) {
    if (majors.length === 0) {
        return '<div class="empty-majors">Chưa có chuyên ngành</div>';
    }
    
    return majors.map(major => `
        <div class="nganh-item" data-major-id="${major.major_id}">
            <span class="nganh-name">${escapeHtml(major.major_name)}</span>
            <span class="nganh-code">(${escapeHtml(major.major_code)})</span>
            <div class="nganh-actions">
                <button class="btn-sm-edit" onclick="editMajor(${major.major_id})" title="Sửa">
                    <i class="fa-solid fa-pen"></i>
                </button>
                <button class="btn-sm-delete" onclick="deleteMajor(${major.major_id})" title="Xóa">
                    <i class="fa-solid fa-trash"></i>
                </button>
            </div>
        </div>
    `).join('');
}

/**
 * Tạo Faculty mới
 */
async function createFaculty(formData) {
    try {
        const response = await fetch('/admin/hoc-thuat/faculty/api', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Content-Type': 'application/json',
                'Accept': 'application/json',
            },
            body: JSON.stringify(formData)
        });
        
        const result = await response.json();
        
        if (result.success) {
            showSuccess(result.message);
            closeModal();
            loadFaculties();
        } else {
            showError(result.message);
        }
    } catch (error) {
        console.error('Error creating faculty:', error);
        showError('Lỗi khi tạo Khoa/Viện: ' + error.message);
    }
}

/**
 * Cập nhật Faculty
 */
async function updateFaculty(facultyId, formData) {
    try {
        const response = await fetch(`/admin/hoc-thuat/faculty/api/${facultyId}`, {
            method: 'PUT',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Content-Type': 'application/json',
                'Accept': 'application/json',
            },
            body: JSON.stringify(formData)
        });
        
        const result = await response.json();
        
        if (result.success) {
            showSuccess(result.message);
            closeModal();
            loadFaculties();
        } else {
            showError(result.message);
        }
    } catch (error) {
        console.error('Error updating faculty:', error);
        showError('Lỗi khi cập nhật Khoa/Viện: ' + error.message);
    }
}

/**
 * Xóa Faculty
 */
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

/**
 * Toggle Faculty Status
 */
async function toggleFacultyStatus(facultyId) {
    try {
        const response = await fetch(`/admin/hoc-thuat/faculty/api/${facultyId}/toggle-status`, {
            method: 'PATCH',
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
        console.error('Error toggling faculty status:', error);
        showError('Lỗi khi đổi trạng thái: ' + error.message);
    }
}

// ============ MAJOR FUNCTIONS ============

/**
 * Tạo Major mới
 */
async function createMajor(formData) {
    try {
        const response = await fetch('/admin/hoc-thuat/major/api', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Content-Type': 'application/json',
                'Accept': 'application/json',
            },
            body: JSON.stringify(formData)
        });
        
        const result = await response.json();
        
        if (result.success) {
            showSuccess(result.message);
            closeModal();
            loadFaculties();
        } else {
            showError(result.message);
        }
    } catch (error) {
        console.error('Error creating major:', error);
        showError('Lỗi khi tạo Chuyên ngành: ' + error.message);
    }
}

/**
 * Cập nhật Major
 */
async function updateMajor(majorId, formData) {
    try {
        const response = await fetch(`/admin/hoc-thuat/major/api/${majorId}`, {
            method: 'PUT',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Content-Type': 'application/json',
                'Accept': 'application/json',
            },
            body: JSON.stringify(formData)
        });
        
        const result = await response.json();
        
        if (result.success) {
            showSuccess(result.message);
            closeModal();
            loadFaculties();
        } else {
            showError(result.message);
        }
    } catch (error) {
        console.error('Error updating major:', error);
        showError('Lỗi khi cập nhật Chuyên ngành: ' + error.message);
    }
}

/**
 * Xóa Major
 */
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

// ============ COURSE FUNCTIONS ============

/**
 * Load danh sách Courses
 */
async function loadCourses(keyword = '', facultyId = '', majorId = '') {
    try {
        const params = new URLSearchParams();
        if (keyword) params.append('keyword', keyword);
        if (facultyId) params.append('faculty_id', facultyId);
        if (majorId) params.append('major_id', majorId);
        params.append('per_page', 10);
        
        const response = await fetch(`/admin/hoc-thuat/course/api?${params}`, {
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json',
            }
        });
        
        const result = await response.json();
        
        if (result.success) {
            coursesData = result.data.courses;
            renderCourses(coursesData, result.data.pagination);
        } else {
            showError('Không thể tải danh sách Học phần');
        }
    } catch (error) {
        console.error('Error loading courses:', error);
        showError('Lỗi kết nối: ' + error.message);
    }
}

/**
 * Render danh sách Courses
 */
function renderCourses(courses, pagination) {
    const tableBody = document.querySelector('#frame-hocphan tbody');
    
    if (!tableBody) {
        console.error('Table body not found');
        return;
    }
    
    tableBody.innerHTML = '';
    
    if (courses.length === 0) {
        tableBody.innerHTML = '<tr><td colspan="7" class="text-center">Chưa có dữ liệu Học phần</td></tr>';
        return;
    }
    
    courses.forEach((course, index) => {
        const row = document.createElement('tr');
        row.innerHTML = `
            <td>${(pagination.from || 0) + index}</td>
            <td>${escapeHtml(course.course_code)}</td>
            <td>${escapeHtml(course.course_name)}</td>
            <td>${course.credits || 0}</td>
            <td>${renderCourseMajors(course.majors || [])}</td>
            <td>
                <span class="status-badge ${course.status?.status_id === 1 ? 'active' : 'inactive'}">
                    ${course.status?.status_name || 'N/A'}
                </span>
            </td>
            <td class="actions">
                <button class="btn-view" onclick="viewCourse(${course.course_id})" title="Xem">
                    <i class="fa-solid fa-eye"></i>
                </button>
                <button class="btn-edit" onclick="editCourse(${course.course_id})" title="Sửa">
                    <i class="fa-solid fa-pen"></i>
                </button>
                <button class="btn-toggle" onclick="toggleCourseLock(${course.course_id})" title="Khóa/Mở">
                    <i class="fa-solid fa-lock"></i>
                </button>
                <button class="btn-delete" onclick="deleteCourse(${course.course_id})" title="Xóa">
                    <i class="fa-solid fa-trash"></i>
                </button>
            </td>
        `;
        tableBody.appendChild(row);
    });
    
    // Render pagination
    renderPagination(pagination);
}

/**
 * Render majors trong course table
 */
function renderCourseMajors(majors) {
    if (majors.length === 0) return 'N/A';
    
    return majors.map(major => `<span class="badge">${escapeHtml(major.major_code)}</span>`).join(' ');
}

/**
 * Load Faculty Filter
 */
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

/**
 * Render Faculty Filter Dropdown
 */
function renderFacultyFilter(faculties) {
    const filterFacultySelect = document.getElementById('filter-faculty');
    
    if (!filterFacultySelect) return;
    
    filterFacultySelect.innerHTML = '<option value="">Tất cả Khoa/Viện</option>';
    
    faculties.forEach(faculty => {
        const option = document.createElement('option');
        option.value = faculty.faculty_id;
        option.textContent = faculty.faculty_name;
        filterFacultySelect.appendChild(option);
    });
    
    // Add event listener
    filterFacultySelect.addEventListener('change', function() {
        const facultyId = this.value;
        const keyword = searchInput.value.trim();
        loadCourses(keyword, facultyId);
        
        // Load majors theo faculty
        if (facultyId) {
            loadMajorFilter(facultyId);
        } else {
            clearMajorFilter();
        }
    });
}

// ============ MODAL HANDLERS ============
function initModalHandlers() {
    // Close modal when clicking overlay
    modalOverlay.addEventListener('click', function(e) {
        if (e.target === modalOverlay) {
            closeModal();
        }
    });
}

function openFacultyModal(facultyId = null) {
    // TODO: Implement modal open logic
    console.log('Open faculty modal', facultyId);
}

function openMajorModal(facultyId) {
    currentFacultyId = facultyId;
    // TODO: Implement modal open logic
    console.log('Open major modal for faculty', facultyId);
}

function editFaculty(facultyId) {
    // TODO: Implement edit logic
    console.log('Edit faculty', facultyId);
}

function editMajor(majorId) {
    // TODO: Implement edit logic
    console.log('Edit major', majorId);
}

function viewCourse(courseId) {
    // TODO: Implement view logic
    console.log('View course', courseId);
}

function editCourse(courseId) {
    // TODO: Implement edit logic
    console.log('Edit course', courseId);
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
            loadCourses();
        } else {
            showError(result.message);
        }
    } catch (error) {
        console.error('Error deleting course:', error);
        showError('Lỗi khi xóa Học phần: ' + error.message);
    }
}

async function toggleCourseLock(courseId) {
    try {
        const response = await fetch(`/admin/hoc-thuat/course/api/${courseId}/toggle-lock`, {
            method: 'PATCH',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json',
            }
        });
        
        const result = await response.json();
        
        if (result.success) {
            showSuccess(result.message);
            loadCourses();
        } else {
            showError(result.message);
        }
    } catch (error) {
        console.error('Error toggling course lock:', error);
        showError('Lỗi khi đổi trạng thái: ' + error.message);
    }
}

function closeModal() {
    modalOverlay.style.display = 'none';
    // Clear form data
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

function renderPagination(pagination) {
    // TODO: Implement pagination rendering
    console.log('Pagination:', pagination);
}

function loadMajorFilter(facultyId) {
    // TODO: Implement major filter loading
    console.log('Load major filter for faculty', facultyId);
}

function clearMajorFilter() {
    const filterMajorSelect = document.getElementById('filter-major');
    if (filterMajorSelect) {
        filterMajorSelect.innerHTML = '<option value="">Tất cả Chuyên ngành</option>';
    }
}
