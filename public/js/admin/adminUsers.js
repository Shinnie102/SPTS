// ================= CONFIG =================
const API_BASE_URL = '/admin/users/api';
const ROWS_PER_PAGE = 5;

// ================= STATE MANAGEMENT =================
let currentState = {
    page: 1,
    role: 'all',
    keyword: '',
    users: [],
    pagination: {},
    statistics: {}
};

// ================= INITIALIZATION =================
document.addEventListener('DOMContentLoaded', () => {
    initializeApp();
});

function initializeApp() {
    setupEventListeners();
    loadUsers();
    loadStatistics();
}

// ================= EVENT LISTENERS =================
function setupEventListeners() {
    // Tabs
    document.querySelectorAll('.user-tabs .tab').forEach((tab, index) => {
        tab.addEventListener('click', () => handleTabClick(index));
    });

    // Search
    const searchInput = document.querySelector('.search-box input');
    let searchTimeout;
    searchInput.addEventListener('input', (e) => {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            currentState.keyword = e.target.value.trim();
            currentState.page = 1;
            loadUsers();
        }, 500); // Debounce 500ms
    });

    // Add User Button
    document.getElementById('btnAddUser').addEventListener('click', () => openUserForm());

    // Bulk Add Button
    document.getElementById('btnBulkAdd').addEventListener('click', () => {
        openBulkAddModal();
    });

    // Modal Close Buttons
    document.querySelectorAll('.modal-close').forEach(btn => {
        btn.addEventListener('click', (e) => {
            closeModal(e.target.closest('.modal-overlay'));
        });
    });

    // Close Detail Modal
    document.getElementById('btnCloseDetail').addEventListener('click', () => {
        closeModal(document.getElementById('userDetailModal'));
    });

    // Edit User from Detail Modal
    document.getElementById('btnEditUser').addEventListener('click', () => {
        const userId = document.getElementById('userDetailModal').dataset.userId;
        closeModal(document.getElementById('userDetailModal'));
        openUserForm(parseInt(userId));
    });

    // Cancel Form
    document.getElementById('btnCancelForm').addEventListener('click', () => {
        closeModal(document.getElementById('userFormModal'));
    });

    // Form Submit
    document.getElementById('userForm').addEventListener('submit', handleFormSubmit);

    // Role change in form
    document.getElementById('formRole').addEventListener('change', (e) => {
        const roleMap = { '1': 'ADMIN', '2': 'LECTURER', '3': 'STUDENT' };
        renderAdditionalFields(roleMap[e.target.value] || '');
    });

    // Close modal when clicking outside
    document.querySelectorAll('.modal-overlay').forEach(modal => {
        modal.addEventListener('click', (e) => {
            if (e.target === modal) closeModal(modal);
        });
    });

    // Submit Bulk Add
    const btnSubmitBulk = document.getElementById('btnSubmitBulk');
    if (btnSubmitBulk) {
        btnSubmitBulk.addEventListener('click', handleBulkImport);
    }

    // Download Template
    const btnDownloadTemplate = document.getElementById('btnDownloadTemplate');
    if (btnDownloadTemplate) {
        btnDownloadTemplate.addEventListener('click', () => {
            window.location.href = '/admin/users/api/download-template';
        });
    }

    // Cancel Bulk Add
    const btnCancelBulk = document.getElementById('btnCancelBulk');
    if (btnCancelBulk) {
        btnCancelBulk.addEventListener('click', () => {
            closeModal(document.getElementById('bulkAddModal'));
        });
    }

    // Bulk Add Form Tabs (switch between File Upload and Paste)
    document.querySelectorAll('.form-tab').forEach(tab => {
        tab.addEventListener('click', () => {
            // Remove active from all tabs
            document.querySelectorAll('.form-tab').forEach(t => t.classList.remove('active'));
            // Add active to clicked tab
            tab.classList.add('active');
            
            // Get tab name
            const tabName = tab.dataset.tab;
            
            // Show/hide corresponding content
            const fileTab = document.getElementById('bulkTabFile');
            const pasteTab = document.getElementById('bulkTabPaste');
            
            if (tabName === 'file') {
                fileTab.style.display = 'block';
                pasteTab.style.display = 'none';
            } else {
                fileTab.style.display = 'none';
                pasteTab.style.display = 'block';
            }
        });
    });

    // File Upload Area - Click to select file
    const fileUploadArea = document.getElementById('fileUploadArea');
    const bulkFileInput = document.getElementById('bulkFile');
    
    if (fileUploadArea && bulkFileInput) {
        fileUploadArea.addEventListener('click', () => {
            bulkFileInput.click();
        });

        // Show file name when selected
        bulkFileInput.addEventListener('change', (e) => {
            if (e.target.files.length > 0) {
                const fileName = e.target.files[0].name;
                const fileSize = (e.target.files[0].size / 1024).toFixed(2);
                // Chỉ cập nhật nội dung hiển thị, không thay đổi structure
                const fileIcon = fileName.toLowerCase().endsWith('.csv') ? 'fa-file-csv' : 'fa-file-excel';
                fileUploadArea.innerHTML = `
                    <i class="fa-solid ${fileIcon}" style="font-size: 48px; color: #10b981;"></i>
                    <p><strong>${fileName}</strong></p>
                    <p>Kích thước: ${fileSize} KB</p>
                    <p style="font-size: 12px; color: #64748b;">Click để chọn file khác</p>
                `;
            }
        });

        // Drag and drop support
        fileUploadArea.addEventListener('dragover', (e) => {
            e.preventDefault();
            e.stopPropagation();
            fileUploadArea.style.borderColor = '#2196F3';
            fileUploadArea.style.backgroundColor = '#eff6ff';
        });

        fileUploadArea.addEventListener('dragleave', (e) => {
            e.preventDefault();
            e.stopPropagation();
            fileUploadArea.style.borderColor = '#cbd5e1';
            fileUploadArea.style.backgroundColor = 'transparent';
        });

        fileUploadArea.addEventListener('drop', (e) => {
            e.preventDefault();
            e.stopPropagation();
            fileUploadArea.style.borderColor = '#cbd5e1';
            fileUploadArea.style.backgroundColor = 'transparent';
            
            if (e.dataTransfer.files.length > 0) {
                // Gán file vào input
                const dt = new DataTransfer();
                dt.items.add(e.dataTransfer.files[0]);
                bulkFileInput.files = dt.files;
                
                // Trigger change event manually
                const event = new Event('change', { bubbles: true });
                bulkFileInput.dispatchEvent(event);
            }
        });
    }
}

// ================= TAB HANDLING =================
function handleTabClick(index) {
    const tabs = document.querySelectorAll('.user-tabs .tab');
    tabs.forEach(t => t.classList.remove('active'));
    tabs[index].classList.add('active');

    const roleMap = ['all', 'ADMIN', 'LECTURER', 'STUDENT'];
    currentState.role = roleMap[index];
    currentState.page = 1;
    loadUsers();
}

// ================= API CALLS =================
async function loadUsers() {
    try {
        showLoading();
        
        const params = new URLSearchParams({
            role: currentState.role,
            keyword: currentState.keyword,
            page: currentState.page,
            per_page: ROWS_PER_PAGE
        });

        const response = await fetch(`${API_BASE_URL}?${params}`, {
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        });

        const result = await response.json();

        if (result.success) {
            currentState.users = result.data.users;
            currentState.pagination = result.data.pagination;
            currentState.statistics = result.data.statistics;
            renderTable();
            updateTabCounts();
        } else {
            showError('Không thể tải dữ liệu');
        }
    } catch (error) {
        console.error('Error loading users:', error);
        showError('Lỗi kết nối server');
    } finally {
        hideLoading();
    }
}

async function loadStatistics() {
    try {
        const response = await fetch(`${API_BASE_URL}/statistics`, {
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        });

        const result = await response.json();

        if (result.success) {
            currentState.statistics = result.data;
            updateTabCounts();
        }
    } catch (error) {
        console.error('Error loading statistics:', error);
    }
}

async function viewUserDetail(userId) {
    try {
        showLoading();
        
        const response = await fetch(`${API_BASE_URL}/${userId}`, {
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        });

        const result = await response.json();

        if (result.success) {
            renderUserDetail(result.data);
            const modal = document.getElementById('userDetailModal');
            modal.dataset.userId = userId;
            openModal(modal);
        } else {
            showError(result.message || 'Không tìm thấy người dùng');
        }
    } catch (error) {
        console.error('Error loading user detail:', error);
        showError('Lỗi kết nối server');
    } finally {
        hideLoading();
    }
}

async function toggleUserStatus(userId) {
    if (!confirm('Bạn có chắc muốn thay đổi trạng thái người dùng này?')) {
        return;
    }

    try {
        showLoading();
        
        const response = await fetch(`${API_BASE_URL}/${userId}/toggle-status`, {
            method: 'PATCH',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Content-Type': 'application/json'
            }
        });

        const result = await response.json();

        if (result.success) {
            showSuccess(result.message);
            loadUsers();
        } else {
            showError(result.message || 'Không thể thay đổi trạng thái');
        }
    } catch (error) {
        console.error('Error toggling status:', error);
        showError('Lỗi kết nối server');
    } finally {
        hideLoading();
    }
}

async function deleteUser(userId) {
    if (!confirm('Bạn có chắc muốn xóa người dùng này?\nHành động này không thể hoàn tác!')) {
        return;
    }

    try {
        showLoading();
        
        const response = await fetch(`${API_BASE_URL}/${userId}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Content-Type': 'application/json'
            }
        });

        const result = await response.json();

        if (result.success) {
            showSuccess(result.message);
            loadUsers();
            loadStatistics();
        } else {
            showError(result.message || 'Không thể xóa người dùng');
        }
    } catch (error) {
        console.error('Error deleting user:', error);
        showError('Lỗi kết nối server');
    } finally {
        hideLoading();
    }
}

// ================= RENDER FUNCTIONS =================
function renderTable() {
    const tbody = document.querySelector('.user-table tbody');
    const users = currentState.users;

    if (users.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="6" style="text-align: center; padding: 40px; color: #64748b;">
                    <i class="fa-solid fa-inbox" style="font-size: 48px; margin-bottom: 16px; display: block;"></i>
                    <p>Không có dữ liệu</p>
                </td>
            </tr>
        `;
        renderPagination();
        updateFooterInfo();
        return;
    }

    tbody.innerHTML = users.map(user => {
        const roleClass = user.role.role_code.toLowerCase();
        const roleText = getRoleText(user.role.role_code);
        const statusClass = user.status.code === 'ACTIVE' ? 'active' : 'inactive';
        const statusText = user.status.name;
        const isAdmin = user.role.role_code === 'ADMIN';

        return `
            <tr>
                <td>${escapeHtml(user.full_name)}</td>
                <td>${escapeHtml(user.email)}</td>
                <td><span class="role ${roleClass}">${roleText}</span></td>
                <td><span class="status ${statusClass}">${statusText}</span></td>
                <td>${formatDate(user.created_at)}</td>
                <td class="actions">
                    <i class="fa-regular fa-eye" onclick="viewUserDetail(${user.user_id})" title="Xem chi tiết"></i>
                    ${!isAdmin ? `
                        <i class="fa-solid fa-lock${user.status.code === 'ACTIVE' ? '' : '-open'}" 
                           onclick="toggleUserStatus(${user.user_id})"
                           title="${user.status.code === 'ACTIVE' ? 'Khóa người dùng' : 'Mở khóa người dùng'}"></i>
                        <i class="fa-solid fa-trash" 
                           onclick="deleteUser(${user.user_id})"
                           title="Xóa người dùng"></i>
                    ` : ''}
                </td>
            </tr>
        `;
    }).join('');

    renderPagination();
    updateFooterInfo();
}

function renderPagination() {
    const pagination = currentState.pagination;
    const container = document.querySelector('.pagination');
    
    if (!pagination || !pagination.last_page) {
        container.innerHTML = '';
        return;
    }

    const currentPage = pagination.current_page;
    const totalPages = pagination.last_page;

    let html = '';

    // Previous button
    html += `
        <button ${currentPage === 1 ? 'disabled' : ''} onclick="changePage(${currentPage - 1})">
            Trước
        </button>
    `;

    // Page numbers
    const range = getPageRange(currentPage, totalPages);
    range.forEach(page => {
        if (page === '...') {
            html += `<button disabled>...</button>`;
        } else {
            html += `
                <button 
                    class="${page === currentPage ? 'active' : ''}"
                    onclick="changePage(${page})">
                    ${page}
                </button>
            `;
        }
    });

    // Next button
    html += `
        <button ${currentPage === totalPages ? 'disabled' : ''} onclick="changePage(${currentPage + 1})">
            Tiếp theo
        </button>
    `;

    container.innerHTML = html;
}

function getPageRange(current, total) {
    const delta = 2;
    const range = [];
    const rangeWithDots = [];

    for (let i = 1; i <= total; i++) {
        if (i === 1 || i === total || (i >= current - delta && i <= current + delta)) {
            range.push(i);
        }
    }

    let prev = 0;
    for (const i of range) {
        if (prev && i - prev > 1) {
            rangeWithDots.push('...');
        }
        rangeWithDots.push(i);
        prev = i;
    }

    return rangeWithDots;
}

function updateFooterInfo() {
    const pagination = currentState.pagination;
    const infoText = document.querySelector('.table-footer span');
    
    if (!pagination || !pagination.total) {
        infoText.textContent = 'Hiển thị 0 trên 0 người dùng';
        return;
    }

    const from = pagination.from || 0;
    const to = pagination.to || 0;
    const total = pagination.total || 0;

    infoText.textContent = `Hiển thị ${from}-${to} trên ${total} người dùng`;
}

function updateTabCounts() {
    const stats = currentState.statistics;
    const tabs = document.querySelectorAll('.user-tabs .tab');
    
    if (!stats || !tabs.length) return;

    const counts = [stats.total, stats.admin, stats.lecturer, stats.student];
    const labels = ['Tất cả', 'Quản trị viên', 'Giáo viên', 'Sinh viên'];

    tabs.forEach((tab, index) => {
        tab.innerHTML = `${labels[index]} <span>${counts[index] || 0}</span>`;
    });
}

function renderUserDetail(user) {
    // Set avatar
    const initials = user.full_name.split(' ').slice(-2).map(n => n[0]).join('').toUpperCase();
    document.getElementById('detailAvatar').textContent = initials;

    // Set header info
    document.getElementById('detailFullName').textContent = user.full_name;
    document.getElementById('detailUserId').textContent = user.code_user;

    // Set personal info
    document.getElementById('detailName').textContent = user.full_name;
    document.getElementById('detailBirthday').innerHTML = `<i class="fa-regular fa-calendar"></i> ${formatDate(user.birth) || '-'}`;
    document.getElementById('detailEmail').innerHTML = `<i class="fa-regular fa-envelope"></i> ${user.email}`;
    document.getElementById('detailPhone').innerHTML = `<i class="fa-solid fa-phone"></i> ${user.phone || '-'}`;
    document.getElementById('detailAddress').innerHTML = `<i class="fa-solid fa-location-dot"></i> ${user.address || '-'}`;

    // Set academic info based on role
    const academicSection = document.getElementById('academicInfoSection');
    const academicGrid = academicSection.querySelector('.info-grid');
    
    if (user.role.role_code === 'STUDENT') {
        academicSection.querySelector('h4').innerHTML = '<i class="fa-solid fa-graduation-cap"></i> Thông tin học tập';
        academicGrid.innerHTML = `
            <div class="info-item">
                <span class="info-label">Mã sinh viên</span>
                <span class="info-value">${user.code_user || '-'}</span>
            </div>
            <div class="info-item">
                <span class="info-label">Chuyên ngành</span>
                <span class="info-value">${user.major || '-'}</span>
            </div>
            <div class="info-item">
                <span class="info-label">Ngày định hướng</span>
                <span class="info-value">${formatDate(user.orientation_day) || '-'}</span>
            </div>
        `;
        academicSection.style.display = 'block';
    } else if (user.role.role_code === 'LECTURER') {
        academicSection.querySelector('h4').innerHTML = '<i class="fa-solid fa-chalkboard-user"></i> Thông tin giảng dạy';
        academicGrid.innerHTML = `
            <div class="info-item">
                <span class="info-label">Mã giảng viên</span>
                <span class="info-value">${user.code_user || '-'}</span>
            </div>
            <div class="info-item">
                <span class="info-label">Chuyên môn</span>
                <span class="info-value">${user.major || '-'}</span>
            </div>
        `;
        academicSection.style.display = 'block';
    } else {
        academicSection.style.display = 'none';
    }

    // Set system info
    const roleClass = user.role.role_code.toLowerCase();
    const roleText = getRoleText(user.role.role_code);
    const statusClass = user.status.code === 'ACTIVE' ? 'active' : 'inactive';
    
    document.getElementById('detailRole').innerHTML = `<span class="role ${roleClass}">${roleText}</span>`;
    document.getElementById('detailStatus').innerHTML = `<span class="status ${statusClass}">${user.status.name}</span>`;
    document.getElementById('detailJoinDate').textContent = formatDate(user.created_at);
}

// ================= FORM HANDLING =================
async function openUserForm(userId = null) {
    const modal = document.getElementById('userFormModal');
    const title = document.getElementById('formModalTitle');
    const form = document.getElementById('userForm');
    const passwordField = document.getElementById('passwordField');
    const passwordInput = document.getElementById('formPassword');
    
    form.reset();
    renderAdditionalFields('');

    if (userId) {
        // Edit mode
        title.textContent = 'Chỉnh sửa thông tin người dùng';
        passwordField.style.display = 'none';
        passwordInput.removeAttribute('required');
        
        // Load user data
        try {
            showLoading();
            
            const response = await fetch(`${API_BASE_URL}/${userId}`, {
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                }
            });

            const result = await response.json();

            if (result.success) {
                const user = result.data;
                
                form.dataset.userId = userId;
                document.getElementById('formRole').value = user.role_id;
                document.getElementById('formFullName').value = user.full_name;
                document.getElementById('formEmail').value = user.email;
                document.getElementById('formPhone').value = user.phone || '';
                document.getElementById('formBirthday').value = user.birth || '';
                document.getElementById('formAddress').value = user.address || '';
                
                renderAdditionalFields(user.role.role_code, user);
            } else {
                showError('Không thể tải thông tin người dùng');
                return;
            }
        } catch (error) {
            console.error('Error loading user:', error);
            showError('Lỗi kết nối server');
            return;
        } finally {
            hideLoading();
        }
    } else {
        // Add mode
        title.textContent = 'Thêm người dùng mới';
        passwordField.style.display = 'block';
        passwordInput.setAttribute('required', 'required');
        form.removeAttribute('data-user-id');
    }

    openModal(modal);
}

function renderAdditionalFields(role, userData = {}) {
    const container = document.getElementById('additionalFields');
    
    if (role === 'STUDENT') {
        container.innerHTML = `
            <h4 style="margin: 24px 0 16px 0; font-size: 16px; color: #1e293b;">Thông tin sinh viên</h4>
            <div class="form-group">
                <label class="form-label">Chuyên ngành</label>
                <input type="text" class="form-input" id="formMajor" value="${escapeHtml(userData.major || '')}" placeholder="Công nghệ Thông tin">
            </div>
            <div class="form-group">
                <label class="form-label">Ngày định hướng</label>
                <input type="date" class="form-input" id="formOrientationDay" value="${userData.orientation_day || ''}">
            </div>
        `;
    } else if (role === 'LECTURER') {
        container.innerHTML = `
            <h4 style="margin: 24px 0 16px 0; font-size: 16px; color: #1e293b;">Thông tin giảng viên</h4>
            <div class="form-group">
                <label class="form-label">Chuyên môn</label>
                <input type="text" class="form-input" id="formMajor" value="${escapeHtml(userData.major || '')}" placeholder="Khoa học Máy tính">
            </div>
        `;
    } else {
        container.innerHTML = '';
    }
}

async function handleFormSubmit(e) {
    e.preventDefault();

    const form = e.target;
    const userId = form.dataset.userId;
    const isEdit = !!userId;

    // Collect form data
    const formData = {
        role_id: parseInt(document.getElementById('formRole').value),
        full_name: document.getElementById('formFullName').value,
        email: document.getElementById('formEmail').value,
        phone: document.getElementById('formPhone').value || null,
        birth: document.getElementById('formBirthday').value || null,
        address: document.getElementById('formAddress').value || null,
    };

    // Add password for new user
    if (!isEdit) {
        formData.password = document.getElementById('formPassword').value;
    }

    // Add role-specific fields
    const majorInput = document.getElementById('formMajor');
    if (majorInput) {
        formData.major = majorInput.value || null;
    }

    const orientationDayInput = document.getElementById('formOrientationDay');
    if (orientationDayInput) {
        formData.orientation_day = orientationDayInput.value || null;
    }

    try {
        showLoading();

        const url = isEdit ? `${API_BASE_URL}/${userId}` : API_BASE_URL;
        const method = isEdit ? 'PUT' : 'POST';

        console.log('=== SUBMIT USER FORM DEBUG ===');
        console.log('URL:', url);
        console.log('Method:', method);
        console.log('Form Data:', formData);
        
        // Log CSRF token
        const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
        console.log('CSRF Token:', csrfToken);

        const response = await fetch(url, {
            method: method,
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify(formData)
        });

        console.log('Response Status:', response.status);
        console.log('Response OK:', response.ok);
        console.log('Response Status Text:', response.statusText);
        console.log('Response Headers:', response.headers);
        console.log('Content-Type:', response.headers.get('content-type'));
        
        // Get response as text first to see what we're actually getting
        const responseText = await response.text();
        console.log('Response Text (first 500 chars):', responseText.substring(0, 500));
        console.log('Full Response Text:', responseText);

        // Check if response is actually HTML (redirect)
        if (responseText.trim().startsWith('<!DOCTYPE') || responseText.trim().startsWith('<html')) {
            console.error('ERROR: Server returned HTML instead of JSON!');
            console.error('This usually means:');
            console.error('1. Validation failed and Laravel redirected back');
            console.error('2. Authorization failed');
            console.error('3. CSRF token mismatch');
            console.error('4. Route not found');
            throw new Error('Server returned HTML page instead of JSON. Check validation errors.');
        }

        // Try to parse as JSON
        let result;
        try {
            result = JSON.parse(responseText);
            console.log('Parsed JSON:', result);
        } catch (parseError) {
            console.error('JSON Parse Error:', parseError);
            console.error('Failed to parse response as JSON');
            throw new Error('Server returned non-JSON response: ' + responseText.substring(0, 100));
        }

        if (result.success) {
            showSuccess(result.message);
            closeModal(document.getElementById('userFormModal'));
            loadUsers();
            loadStatistics();
        } else {
            // Handle validation errors
            if (result.errors) {
                let errorMessages = [];
                for (let field in result.errors) {
                    errorMessages.push(...result.errors[field]);
                }
                showError(errorMessages.join('<br>'));
            } else {
                showError(result.message || 'Có lỗi xảy ra');
            }
        }
    } catch (error) {
        console.error('Error submitting form:', error);
        showError('Lỗi kết nối server');
    } finally {
        hideLoading();
    }
}

// ================= PAGINATION =================
function changePage(page) {
    currentState.page = page;
    loadUsers();
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

// ================= BULK IMPORT HANDLING =================
function openBulkAddModal() {
    const modal = document.getElementById('bulkAddModal');
    
    // Reset paste textarea
    document.getElementById('bulkPasteData').value = '';
    
    // Reset file input
    const fileInput = document.getElementById('bulkFile');
    if (fileInput) fileInput.value = '';
    
    // Reset file upload area display (chỉ reset nội dung hiển thị, không tạo input mới)
    const fileUploadArea = document.getElementById('fileUploadArea');
    if (fileUploadArea) {
        fileUploadArea.innerHTML = `
            <i class="fa-solid fa-cloud-arrow-up"></i>
            <p><strong>Kéo thả file vào đây</strong> hoặc click để chọn</p>
            <p>Hỗ trợ: .xlsx, .xls, .csv (Tối đa 5MB)</p>
        `;
    }
    
    // Reset to file tab
    document.querySelectorAll('.form-tab').forEach(t => t.classList.remove('active'));
    document.querySelector('.form-tab[data-tab="file"]').classList.add('active');
    document.getElementById('bulkTabFile').style.display = 'block';
    document.getElementById('bulkTabPaste').style.display = 'none';
    
    openModal(modal);
}

async function handleBulkImport() {
    const activeTab = document.querySelector('.form-tab.active');
    const importType = activeTab.dataset.tab; // 'file' hoặc 'paste'
    
    let formData = new FormData();
    formData.append('import_type', importType);
    
    if (importType === 'paste') {
        const pasteData = document.getElementById('bulkPasteData').value.trim();
        
        if (!pasteData) {
            showError('Vui lòng nhập dữ liệu');
            return;
        }
        
        formData.append('data', pasteData);
    } else {
        const fileInput = document.getElementById('bulkFile');
        
        if (!fileInput.files.length) {
            showError('Vui lòng chọn file');
            return;
        }
        
        formData.append('file', fileInput.files[0]);
    }
    
    try {
        showLoading();
        
        const response = await fetch('/admin/users/api/bulk-import', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            showBulkImportResults(result.results);
            closeModal(document.getElementById('bulkAddModal'));
            loadUsers();
            loadStatistics();
        } else {
            showError(result.message || 'Có lỗi xảy ra');
        }
    } catch (error) {
        console.error('Error bulk importing:', error);
        showError('Lỗi kết nối server');
    } finally {
        hideLoading();
    }
}

function showBulkImportResults(results) {
    const message = `
        <div style="text-align: left;">
            <p style="margin-bottom: 12px;"><strong>Kết quả import:</strong></p>
            <p>✅ Thành công: <strong>${results.success}</strong>/${results.total}</p>
            <p>❌ Thất bại: <strong>${results.failed}</strong>/${results.total}</p>
            ${results.errors.length > 0 ? `
                <details style="margin-top: 12px;">
                    <summary style="cursor: pointer; color: #ef4444;">Chi tiết lỗi (${results.errors.length})</summary>
                    <ul style="margin-top: 8px; padding-left: 20px; max-height: 200px; overflow-y: auto;">
                        ${results.errors.map(err => `
                            <li style="margin: 4px 0;">
                                <strong>Dòng ${err.row}:</strong> ${err.error}
                                <br><small style="color: #64748b;">${err.data.full_name || ''} - ${err.data.email || ''}</small>
                            </li>
                        `).join('')}
                    </ul>
                </details>
            ` : ''}
        </div>
    `;
    
    // Tạo modal tùy chỉnh để hiển thị kết quả
    const resultModal = document.createElement('div');
    resultModal.style.cssText = `
        position: fixed;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        background: white;
        padding: 24px;
        border-radius: 12px;
        box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        z-index: 100001;
        max-width: 500px;
        width: 90%;
    `;
    resultModal.innerHTML = `
        ${message}
        <button onclick="this.parentElement.remove()" style="
            margin-top: 16px;
            padding: 10px 20px;
            background: #2196F3;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            width: 100%;
        ">Đóng</button>
    `;
    document.body.appendChild(resultModal);
}

// ================= MODAL UTILITIES =================
function openModal(modal) {
    modal.classList.add('active');
    document.body.style.overflow = 'hidden';
}

function closeModal(modal) {
    modal.classList.remove('active');
    document.body.style.overflow = '';
}

// ================= HELPER FUNCTIONS =================
function getRoleText(roleCode) {
    const roleMap = {
        'ADMIN': 'Admin',
        'LECTURER': 'Lecturer',
        'STUDENT': 'Student'
    };
    return roleMap[roleCode] || roleCode;
}

function formatDate(dateString) {
    if (!dateString) return '-';
    const date = new Date(dateString);
    return date.toLocaleDateString('vi-VN');
}

function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// ================= LOADING & NOTIFICATIONS =================
function showLoading() {
    // Tạo loading overlay nếu chưa có
    if (!document.getElementById('loadingOverlay')) {
        const overlay = document.createElement('div');
        overlay.id = 'loadingOverlay';
        overlay.style.cssText = `
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.3);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 99999;
        `;
        overlay.innerHTML = '<div class="spinner" style="border: 4px solid #f3f3f3; border-top: 4px solid #2196F3; border-radius: 50%; width: 40px; height: 40px; animation: spin 1s linear infinite;"></div>';
        document.body.appendChild(overlay);
        
        // Add animation
        const style = document.createElement('style');
        style.textContent = '@keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }';
        document.head.appendChild(style);
    } else {
        document.getElementById('loadingOverlay').style.display = 'flex';
    }
}

function hideLoading() {
    const overlay = document.getElementById('loadingOverlay');
    if (overlay) {
        overlay.style.display = 'none';
    }
}

function showSuccess(message) {
    showNotification(message, 'success');
}

function showError(message) {
    showNotification(message, 'error');
}

function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 16px 24px;
        border-radius: 8px;
        background: ${type === 'success' ? '#10b981' : type === 'error' ? '#ef4444' : '#2196F3'};
        color: white;
        font-weight: 500;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        z-index: 100000;
        animation: slideIn 0.3s ease;
    `;
    notification.textContent = message;

    document.body.appendChild(notification);

    setTimeout(() => {
        notification.style.animation = 'slideOut 0.3s ease';
        setTimeout(() => notification.remove(), 300);
    }, 3000);
}

// Add animations
const style = document.createElement('style');
style.textContent = `
    @keyframes slideIn {
        from { transform: translateX(400px); opacity: 0; }
        to { transform: translateX(0); opacity: 1; }
    }
    @keyframes slideOut {
        from { transform: translateX(0); opacity: 1; }
        to { transform: translateX(400px); opacity: 0; }
    }
`;
document.head.appendChild(style);

// ================= MAKE FUNCTIONS GLOBAL =================
window.viewUserDetail = viewUserDetail;
window.toggleUserStatus = toggleUserStatus;
window.deleteUser = deleteUser;
window.changePage = changePage;