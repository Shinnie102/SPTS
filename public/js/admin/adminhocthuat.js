document.addEventListener('DOMContentLoaded', function() {
    // ===== MODAL MANAGEMENT =====
    const modalOverlay = document.getElementById('modalOverlay');
    const frameNewElements = document.querySelectorAll('.frame-new');
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
            
            // Reset form khi mở modal
            const inputs = modal.querySelectorAll('input, select');
            inputs.forEach(input => {
                if (!input.value || input.value === '') {
                    input.value = '';
                }
            });
            
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

    // Không cho phép close modal bằng overlay click
    modalOverlay.addEventListener('click', function(e) {
        // Chỉ đóng khi click vào overlay, không phải vào modal
        if (e.target === modalOverlay) {
            // Không cho phép đóng
        }
    });

    // ===== VALIDATION FUNCTIONS =====
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
                if (!this.disabled) {
                    // Gọi API hoặc xử lý lưu dữ liệu ở đây
                    console.log('Thêm dữ liệu mới');
                    closeModal();
                }
                e.preventDefault();
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
            openModal('.themhocphan');
        });
    }

    // Nút Edit
    document.querySelectorAll('.course-table button.edit').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.stopPropagation();
            openModal('.chitiethocphan');
        });
    });

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
    const khoavienElements = document.querySelectorAll('.khung-khoavien');

    khoavienElements.forEach(function(khoavien) {
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

// ===== FAKE SELECT DROPDOWN =====
document.querySelectorAll(".fake-select").forEach(select => {
    const selected = select.querySelector(".selected");
    const options = select.querySelector(".options");
    const hiddenInput = select.querySelector("input");

    selected.addEventListener("click", () => {
        document.querySelectorAll(".options").forEach(o => {
            if (o !== options) o.style.display = "none";
        });
        options.style.display = options.style.display === "block" ? "none" : "block";
    });

    select.querySelectorAll(".option").forEach(option => {
        option.addEventListener("click", () => {
            selected.textContent = option.textContent;
            hiddenInput.value = option.dataset.value;
            options.style.display = "none";
        });
    });
});

document.addEventListener("click", e => {
    if (!e.target.closest(".fake-select")) {
        document.querySelectorAll(".options").forEach(o => o.style.display = "none");
    }
});



