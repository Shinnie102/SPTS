// ================= DATA MANAGEMENT =================
const usersData = [
    {
        id: 1,
        fullName: 'TS. Nguyễn Văn A',
        email: 'nguyenvana@university.edu.vn',
        role: 'lecturer',
        status: 'active',
        joinDate: '2015-09-01',
        phone: '0912 345 678',
        birthday: '1985-03-15',
        address: '123 Đường ABC, Quận 1, TP.HCM',
        // Lecturer specific
        classesTeaching: 3,
        experience: '9 năm'
    },
    {
        id: 2,
        fullName: 'Trần Thị B',
        email: 'tranthib@student.university.edu.vn',
        role: 'student',
        status: 'active',
        joinDate: '2023-09-01',
        phone: '0987 654 321',
        birthday: '2005-05-20',
        address: '456 Đường XYZ, Quận 3, TP.HCM',
        // Student specific
        studentId: 'SV2023001',
        class: 'CNTT-K18',
        major: 'Công nghệ Thông tin',
        gpa: '3.65/4.0',
        faculty: 'Khoa Công nghệ Thông tin'
    },
    {
        id: 3,
        fullName: 'Phạm Văn C',
        email: 'phamvanc@university.edu.vn',
        role: 'admin',
        status: 'active',
        joinDate: '2023-12-01',
        phone: '0909 123 456',
        birthday: '1990-08-10',
        address: '789 Đường DEF, Quận 5, TP.HCM'
    },
    {
        id: 4,
        fullName: 'Hoàng Thị D',
        email: 'hoangthid@student.university.edu.vn',
        role: 'student',
        status: 'inactive',
        joinDate: '2024-01-20',
        phone: '0918 222 333',
        birthday: '2004-11-25',
        address: '321 Đường GHI, Quận 7, TP.HCM',
        studentId: 'SV2024002',
        class: 'CNTT-K19',
        major: 'Khoa học Máy tính',
        gpa: '3.20/4.0',
        faculty: 'Khoa Công nghệ Thông tin'
    },
    {
        id: 5,
        fullName: 'Lê Văn E',
        email: 'levane@university.edu.vn',
        role: 'lecturer',
        status: 'active',
        joinDate: '2024-03-05',
        phone: '0933 444 555',
        birthday: '1988-07-30',
        address: '654 Đường JKL, Quận 10, TP.HCM',
        classesTeaching: 2,
        experience: '5 năm'
    }
];

let currentEditingUserId = null;
let currentPage = 1;
let currentRole = 'all';
let keyword = '';
const ROWS_PER_PAGE = 5;

// ================= INITIALIZATION =================
document.addEventListener('DOMContentLoaded', () => {
    initializeApp();
});

function initializeApp() {
    const tabs = document.querySelectorAll('.user-tabs .tab');
    const searchInput = document.querySelector('.search-box input');

    // Render initial data
    renderTable();
    updateTabCounts();

    // ================= EVENT LISTENERS =================
    
    // Tabs
    tabs.forEach(tab => {
        tab.addEventListener('click', () => {
            tabs.forEach(t => t.classList.remove('active'));
            tab.classList.add('active');

            const label = tab.innerText.toLowerCase();
            if (label.includes('quản trị')) currentRole = 'admin';
            else if (label.includes('giáo viên') || label.includes('giảng viên')) currentRole = 'lecturer';
            else if (label.includes('sinh viên')) currentRole = 'student';
            else currentRole = 'all';

            currentPage = 1;
            renderTable();
        });
    });

    // Search
    searchInput.addEventListener('input', (e) => {
        keyword = e.target.value.toLowerCase().trim();
        currentPage = 1;
        renderTable();
    });

    // Add User Button
    document.getElementById('btnAddUser').addEventListener('click', () => {
        openUserForm();
    });

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
        closeModal(document.getElementById('userDetailModal'));
        openUserForm(currentEditingUserId, true);
    });

    // Cancel Form
    document.getElementById('btnCancelForm').addEventListener('click', () => {
        closeModal(document.getElementById('userFormModal'));
    });

    // Cancel Bulk
    document.getElementById('btnCancelBulk').addEventListener('click', () => {
        closeModal(document.getElementById('bulkAddModal'));
    });

    // Form Submit
    document.getElementById('userForm').addEventListener('submit', (e) => {
        e.preventDefault();
        handleFormSubmit();
    });

    // Role change in form
    document.getElementById('formRole').addEventListener('change', (e) => {
        renderAdditionalFields(e.target.value);
    });

    // Bulk Add Tabs
    document.querySelectorAll('.form-tab').forEach(tab => {
        tab.addEventListener('click', () => {
            document.querySelectorAll('.form-tab').forEach(t => t.classList.remove('active'));
            tab.classList.add('active');
            
            const tabName = tab.dataset.tab;
            document.getElementById('bulkTabFile').style.display = tabName === 'file' ? 'block' : 'none';
            document.getElementById('bulkTabPaste').style.display = tabName === 'paste' ? 'block' : 'none';
        });
    });

    // File Upload Area
    const fileUploadArea = document.getElementById('fileUploadArea');
    const fileInput = document.getElementById('bulkFile');
    
    fileUploadArea.addEventListener('click', () => fileInput.click());
    
    fileInput.addEventListener('change', (e) => {
        const file = e.target.files[0];
        if (file) {
            console.log('File selected:', file.name);
            alert('Chức năng upload file đang được phát triển');
        }
    });

    // Submit Bulk Add
    document.getElementById('btnSubmitBulk').addEventListener('click', () => {
        alert('Chức năng thêm hàng loạt đang được phát triển');
    });

    // Close modal when clicking outside
    document.querySelectorAll('.modal-overlay').forEach(modal => {
        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
                closeModal(modal);
            }
        });
    });
}

// ================= RENDER FUNCTIONS =================

function filterRows() {
    return usersData.filter(user => {
        const name = user.fullName.toLowerCase();
        const email = user.email.toLowerCase();
        const matchKeyword = name.includes(keyword) || email.includes(keyword);
        const matchRole = currentRole === 'all' || user.role === currentRole;
        return matchKeyword && matchRole;
    });
}

function renderTable() {
    const tbody = document.querySelector('.user-table tbody');
    const filteredUsers = filterRows();
    const total = filteredUsers.length;
    const totalPages = Math.ceil(total / ROWS_PER_PAGE);

    currentPage = Math.min(currentPage, totalPages || 1);

    const start = (currentPage - 1) * ROWS_PER_PAGE;
    const end = start + ROWS_PER_PAGE;
    const pageUsers = filteredUsers.slice(start, end);

    // Render table rows
    tbody.innerHTML = pageUsers.map(user => {
        const roleClass = user.role;
        const roleText = getRoleText(user.role);
        const statusClass = user.status;
        const statusText = user.status === 'active' ? 'Hoạt động' : 'Không hoạt động';

        return `
            <tr>
                <td>${user.fullName}</td>
                <td>${user.email}</td>
                <td><span class="role ${roleClass}">${roleText}</span></td>
                <td><span class="status ${statusClass}">${statusText}</span></td>
                <td>${formatDate(user.joinDate)}</td>
                <td class="actions">
                    <i class="fa-regular fa-eye" onclick="viewUserDetail(${user.id})"></i>
                    <i class="fa-solid fa-ellipsis-vertical"></i>
                </td>
            </tr>
        `;
    }).join('');

    // Update footer
    const infoText = document.querySelector('.table-footer span');
    infoText.textContent = `Hiển thị ${Math.min(end, total)} trên ${total} người dùng`;

    renderPagination(totalPages);
}

function renderPagination(totalPages) {
    const pagination = document.querySelector('.pagination');
    pagination.innerHTML = '';

    // Previous button
    const prevBtn = createPageButton('Trước', currentPage > 1, () => {
        currentPage--;
        renderTable();
    });
    pagination.appendChild(prevBtn);

    // Page numbers
    for (let i = 1; i <= totalPages; i++) {
        const btn = createPageButton(i, true, () => {
            currentPage = i;
            renderTable();
        }, i === currentPage);
        pagination.appendChild(btn);
    }

    // Next button
    const nextBtn = createPageButton('Tiếp theo', currentPage < totalPages, () => {
        currentPage++;
        renderTable();
    });
    pagination.appendChild(nextBtn);
}

function createPageButton(text, enabled, onClick, active = false) {
    const btn = document.createElement('button');
    btn.textContent = text;
    if (!enabled) btn.disabled = true;
    if (active) btn.classList.add('active');
    btn.addEventListener('click', onClick);
    return btn;
}

function updateTabCounts() {
    const tabs = document.querySelectorAll('.user-tabs .tab');
    const counts = {
        all: usersData.length,
        admin: usersData.filter(u => u.role === 'admin').length,
        lecturer: usersData.filter(u => u.role === 'lecturer').length,
        student: usersData.filter(u => u.role === 'student').length
    };

    const tabTexts = ['Tất cả', 'Quản trị viên', 'Giáo viên', 'Sinh viên'];
    const tabValues = ['all', 'admin', 'lecturer', 'student'];

    tabs.forEach((tab, index) => {
        const count = counts[tabValues[index]];
        tab.innerHTML = `${tabTexts[index]} <span>${count}</span>`;
    });
}

// ================= USER DETAIL MODAL =================
function viewUserDetail(userId) {
    const user = usersData.find(u => u.id === userId);
    if (!user) return;

    currentEditingUserId = userId;

    // Set avatar
    const initials = user.fullName.split(' ').slice(-2).map(n => n[0]).join('').toUpperCase();
    document.getElementById('detailAvatar').textContent = initials;

    // Set header info
    document.getElementById('detailFullName').textContent = user.fullName;
    document.getElementById('detailUserId').textContent = user.studentId || user.email;

    // Set personal info
    document.getElementById('detailName').textContent = user.fullName;
    document.getElementById('detailBirthday').innerHTML = `<i class="fa-regular fa-calendar"></i> ${formatDate(user.birthday) || '-'}`;
    document.getElementById('detailEmail').innerHTML = `<i class="fa-regular fa-envelope"></i> ${user.email}`;
    document.getElementById('detailPhone').innerHTML = `<i class="fa-solid fa-phone"></i> ${user.phone || '-'}`;
    document.getElementById('detailAddress').innerHTML = `<i class="fa-solid fa-location-dot"></i> ${user.address || '-'}`;

    // Set academic info based on role
    const academicSection = document.getElementById('academicInfoSection');
    const academicGrid = academicSection.querySelector('.info-grid');
    
    if (user.role === 'student') {
        academicSection.querySelector('h4').innerHTML = '<i class="fa-solid fa-graduation-cap"></i> Thông tin học tập';
        academicGrid.innerHTML = `
            <div class="info-item">
                <span class="info-label">Mã sinh viên</span>
                <span class="info-value">${user.studentId || '-'}</span>
            </div>
            <div class="info-item">
                <span class="info-label">Lớp học</span>
                <span class="info-value">${user.class || '-'}</span>
            </div>
            <div class="info-item">
                <span class="info-label">Chuyên ngành</span>
                <span class="info-value">${user.major || '-'}</span>
            </div>
            <div class="info-item">
                <span class="info-label">GPA</span>
                <span class="info-value">${user.gpa || '-'}</span>
            </div>
            <div class="info-item">
                <span class="info-label">Khoa</span>
                <span class="info-value">${user.faculty || '-'}</span>
            </div>
        `;
        academicSection.style.display = 'block';
    } else if (user.role === 'lecturer') {
        academicSection.querySelector('h4').innerHTML = '<i class="fa-solid fa-chalkboard-user"></i> Thông tin giảng dạy';
        academicGrid.innerHTML = `
            <div class="info-item">
                <span class="info-label">Số lớp giảng dạy</span>
                <span class="info-value">${user.classesTeaching || 0} lớp</span>
            </div>
            <div class="info-item">
                <span class="info-label">Kinh nghiệm</span>
                <span class="info-value">${user.experience || '-'}</span>
            </div>
        `;
        academicSection.style.display = 'block';
    } else {
        academicSection.style.display = 'none';
    }

    // Set system info
    document.getElementById('detailRole').innerHTML = `<span class="role ${user.role}">${getRoleText(user.role)}</span>`;
    document.getElementById('detailStatus').innerHTML = `<span class="status ${user.status}">${user.status === 'active' ? 'Hoạt động' : 'Không hoạt động'}</span>`;
    document.getElementById('detailJoinDate').textContent = formatDate(user.joinDate);

    // Open modal
    openModal(document.getElementById('userDetailModal'));
}

// ================= USER FORM MODAL =================
function openUserForm(userId = null, isEdit = false) {
    const modal = document.getElementById('userFormModal');
    const title = document.getElementById('formModalTitle');
    const form = document.getElementById('userForm');
    
    form.reset();
    currentEditingUserId = userId;

    // Show/hide password field based on mode
    const passwordField = document.getElementById('passwordField');
    const passwordInput = document.getElementById('formPassword');
    
    if (isEdit && userId) {
        const user = usersData.find(u => u.id === userId);
        if (!user) return;

        title.textContent = 'Chỉnh sửa thông tin người dùng';
        
        // Hide password field when editing
        passwordField.style.display = 'none';
        passwordInput.removeAttribute('required');
        
        // Fill form with user data
        document.getElementById('formRole').value = user.role;
        document.getElementById('formFullName').value = user.fullName;
        document.getElementById('formEmail').value = user.email;
        document.getElementById('formPhone').value = user.phone || '';
        document.getElementById('formBirthday').value = user.birthday || '';
        document.getElementById('formAddress').value = user.address || '';

        renderAdditionalFields(user.role, user);
    } else {
        title.textContent = 'Thêm người dùng mới';
        
        // Show password field when adding new user
        passwordField.style.display = 'block';
        passwordInput.setAttribute('required', 'required');
        
        renderAdditionalFields('');
    }

    openModal(modal);
}

function renderAdditionalFields(role, userData = {}) {
    const container = document.getElementById('additionalFields');
    
    if (role === 'student') {
        container.innerHTML = `
            <h4 style="margin: 24px 0 16px 0; font-size: 16px; color: #1e293b;">Thông tin sinh viên</h4>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Mã sinh viên</label>
                    <input type="text" class="form-input" id="formStudentId" value="${userData.studentId || ''}" placeholder="SV2024001">
                </div>
                <div class="form-group">
                    <label class="form-label">Lớp học</label>
                    <input type="text" class="form-input" id="formClass" value="${userData.class || ''}" placeholder="CNTT-K18">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Chuyên ngành</label>
                    <input type="text" class="form-input" id="formMajor" value="${userData.major || ''}" placeholder="Công nghệ Thông tin">
                </div>
                <div class="form-group">
                    <label class="form-label">Khoa</label>
                    <input type="text" class="form-input" id="formFaculty" value="${userData.faculty || ''}" placeholder="Khoa Công nghệ Thông tin">
                </div>
            </div>
        `;
    } else if (role === 'lecturer') {
        container.innerHTML = `
            <h4 style="margin: 24px 0 16px 0; font-size: 16px; color: #1e293b;">Thông tin giảng viên</h4>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Số năm kinh nghiệm</label>
                    <input type="text" class="form-input" id="formExperience" value="${userData.experience || ''}" placeholder="5 năm">
                </div>
                <div class="form-group">
                    <label class="form-label">Số lớp đang giảng dạy</label>
                    <input type="number" class="form-input" id="formClassesTeaching" value="${userData.classesTeaching || '0'}">
                </div>
            </div>
        `;
    } else {
        container.innerHTML = '';
    }
}

function handleFormSubmit() {
    const role = document.getElementById('formRole').value;
    const fullName = document.getElementById('formFullName').value;
    const email = document.getElementById('formEmail').value;
    const phone = document.getElementById('formPhone').value;
    const birthday = document.getElementById('formBirthday').value;
    const address = document.getElementById('formAddress').value;

    const userData = {
        fullName,
        email,
        role,
        phone,
        birthday,
        address,
        status: 'active',
        joinDate: new Date().toISOString().split('T')[0]
    };

    // Add role-specific fields
    if (role === 'student') {
        userData.studentId = document.getElementById('formStudentId')?.value || '';
        userData.class = document.getElementById('formClass')?.value || '';
        userData.major = document.getElementById('formMajor')?.value || '';
        userData.faculty = document.getElementById('formFaculty')?.value || '';
        userData.gpa = '0.00/4.0';
    } else if (role === 'lecturer') {
        userData.experience = document.getElementById('formExperience')?.value || '0 năm';
        userData.classesTeaching = parseInt(document.getElementById('formClassesTeaching')?.value) || 0;
    }

    if (currentEditingUserId) {
        // Update existing user
        const index = usersData.findIndex(u => u.id === currentEditingUserId);
        if (index !== -1) {
            usersData[index] = { ...usersData[index], ...userData };
            console.log('User updated:', usersData[index]);
        }
    } else {
        // Add new user
        const password = document.getElementById('formPassword').value;
        userData.id = usersData.length + 1;
        userData.password = password; // Save temporary password
        usersData.push(userData);
        console.log('User added:', userData);
    }

    // Refresh UI
    renderTable();
    updateTabCounts();
    closeModal(document.getElementById('userFormModal'));
    
    alert(currentEditingUserId ? 'Cập nhật người dùng thành công!' : 'Thêm người dùng thành công!');
}

// ================= BULK ADD MODAL =================
function openBulkAddModal() {
    const modal = document.getElementById('bulkAddModal');
    openModal(modal);
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
function getRoleText(role) {
    const roleMap = {
        'admin': 'Admin',
        'lecturer': 'Lecturer',
        'student': 'Student'
    };
    return roleMap[role] || role;
}

function formatDate(dateString) {
    if (!dateString) return '-';
    const date = new Date(dateString);
    return date.toLocaleDateString('vi-VN');
}

// Make viewUserDetail available globally
window.viewUserDetail = viewUserDetail;

