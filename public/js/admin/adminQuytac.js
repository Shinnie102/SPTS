// Toggle hiển thị frame-an khi click vào frame-hien
document.addEventListener('DOMContentLoaded', function() {
    // CSRF Token
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

    // Load dữ liệu khi trang được load
    loadAllData();

    /**
     * Load tất cả dữ liệu từ API
     */
    function loadAllData() {
        fetch('/admin/quy-tac/api/data')
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    renderAcademicRules(result.data.academic_rules);
                    renderGradingSchemes(result.data.grading_schemes);
                } else {
                    console.error('Lỗi:', result.message);
                }
            })
            .catch(error => {
                console.error('Lỗi khi tải dữ liệu:', error);
            });
    }

    /**
     * Render quy tắc học vụ
     */
    function renderAcademicRules(rules) {
        const container = document.getElementById('frame-quytac');
        if (!container) return;

        // Xóa các quy tắc cũ (giữ lại tiêu đề)
        const title = container.querySelector('#quytachocvu');
        container.innerHTML = '';
        if (title) {
            container.appendChild(title);
        } else {
            const newTitle = document.createElement('p');
            newTitle.id = 'quytachocvu';
            newTitle.textContent = 'Quy tắc Học vụ';
            container.appendChild(newTitle);
        }

        rules.forEach(rule => {
            const ruleDiv = document.createElement('div');
            ruleDiv.className = 'quytac';
            ruleDiv.innerHTML = `
                <p class="tenquytac">${rule.display_name}</p>
                <p class="noidungquytac">${rule.description || ''}</p>
            `;
            container.appendChild(ruleDiv);
        });
    }

    /**
     * Render sơ đồ điểm
     */
    function renderGradingSchemes(schemes) {
        const container = document.getElementById('frame-sododiem');
        if (!container) return;

        // Xóa các sơ đồ cũ (giữ lại title)
        const title = container.querySelector('#title-sododiem');
        container.innerHTML = '';
        if (title) {
            container.appendChild(title);
        }

        schemes.forEach(scheme => {
            const schemeDiv = createGradingSchemeElement(scheme);
            container.appendChild(schemeDiv);
        });

        // Gắn sự kiện cho các sơ đồ
        document.querySelectorAll('.sododiem').forEach(sodo => {
            attachSoDoEventHandlers(sodo);
        });
    }

    /**
     * Tạo phần tử HTML cho sơ đồ điểm
     */
    function createGradingSchemeElement(scheme) {
        const sodoDiv = document.createElement('div');
        sodoDiv.className = 'sododiem';
        sodoDiv.dataset.schemeId = scheme.grading_scheme_id;

        const classesCount = scheme.classes_count || 0;
        const hasLock = classesCount > 0;
        
        let componentsHTML = '';
        if (scheme.grading_components && scheme.grading_components.length > 0) {
            scheme.grading_components.forEach(comp => {
                componentsHTML += `
                    <div class="frame-thanhphan">
                        <p class="tenthanhphan">${comp.component_name}</p>
                        <p class="trongso">${comp.weight_percent}%</p>
                    </div>
                `;
            });
        }

        sodoDiv.innerHTML = `
            <div class="frame-hien">
                <div class="left">
                    <div class="title-sodo">
                        <p class="tensodo">${scheme.scheme_name}</p>
                        <i class="fa-solid fa-lock ${hasLock ? '' : 'hidden'}"></i>
                    </div>
                    <p class="masodo">Mã: ${scheme.scheme_code}</p>
                    <p class="dangdung">Đang dùng: <span>${classesCount}</span> lớp</p>
                </div>
                <div class="right">
                    <div class="status" id="${scheme.status?.code === 'ACTIVE' ? 'hoatdong' : 'khonghoatdong'}">
                        ${scheme.status?.name || 'Đang áp dụng'}
                    </div>
                    <i class="fa-regular fa-pen-to-square"></i>
                    <i class="fa-solid fa-trash ${hasLock ? 'disabled' : ''}"></i>
                </div>
            </div>
            <div class="frame-an">
                <p class="thanhphandiem">Thành phần điểm:</p>
                ${componentsHTML}
            </div>
        `;

        return sodoDiv;
    }

    // Hàm kiểm tra và ẩn/hiện icon lock dựa trên số lớp đang dùng
    function updateLockIconVisibility(sodoElement) {
        const dangDungSpan = sodoElement.querySelector('.dangdung span');
        const lockIcon = sodoElement.querySelector('.fa-lock');
        
        if (dangDungSpan && lockIcon) {
            const soLop = parseInt(dangDungSpan.textContent);
            if (soLop > 0) {
                lockIcon.classList.remove('hidden');
            } else {
                lockIcon.classList.add('hidden');
            }
        }
    }

    // Hàm kiểm tra và vô hiệu hóa/kích hoạt nút xóa dựa trên số lớp đang dùng
    function updateTrashIconState(sodoElement) {
        const dangDungSpan = sodoElement.querySelector('.dangdung span');
        const trashIcon = sodoElement.querySelector('.fa-trash');
        
        if (dangDungSpan && trashIcon) {
            const soLop = parseInt(dangDungSpan.textContent);
            if (soLop > 0) {
                trashIcon.classList.add('disabled');
            } else {
                trashIcon.classList.remove('disabled');
            }
        }
    }

    // Xử lý hiển thị modal thêm sơ đồ điểm
    const btnAddSodoDiem = document.getElementById('add-sododiem');
    const frameModule = document.querySelector('.frame-module');
    const moduleThemSodoDiem = document.querySelector('.module.themsododiem');
    const moduleSuaSodoDiem = document.querySelector('.module.suasododiem');

    // Hiển thị modal thêm sơ đồ điểm
    if (btnAddSodoDiem) {
        btnAddSodoDiem.addEventListener('click', function() {
            frameModule.classList.add('active');
            moduleThemSodoDiem.classList.add('active');
            moduleSuaSodoDiem.classList.remove('active');
            resetFormThem();
        });
    }

    /**
     * Reset form thêm
     */
    function resetFormThem() {
        moduleThemSodoDiem.querySelectorAll('input').forEach(input => input.value = '');
        
        // Xóa tất cả thành phần điểm và thêm 1 thành phần mới
        const btnThemThanhPhan = moduleThemSodoDiem.querySelector('#themthanhphan');
        const container = btnThemThanhPhan.parentElement;
        const existingComponents = container.querySelectorAll('.themthanhphandiem');
        existingComponents.forEach(comp => comp.remove());
        
        addNewComponent(moduleThemSodoDiem);
    }

    /**
     * Thêm thành phần điểm mới
     */
    function addNewComponent(modal) {
        const btnThemThanhPhan = modal.querySelector('#themthanhphan');
        const container = btnThemThanhPhan.parentElement;
        
        const newThanhPhan = document.createElement('div');
        newThanhPhan.className = 'themthanhphandiem';
        newThanhPhan.innerHTML = `
            <input type="text" placeholder="Tên thành phần điểm" class="tenthanhphandiem">
            <input type="text" placeholder="0%" class="phantramthanhphan">
            <button class="xoathanhphandiem">Xoá</button>
        `;
        container.insertBefore(newThanhPhan, btnThemThanhPhan);
        attachXoaThanhPhanHandler(newThanhPhan.querySelector('.xoathanhphandiem'));
    }

    /**
     * Load dữ liệu vào form sửa
     */
    function loadEditForm(scheme) {
        moduleSuaSodoDiem.dataset.schemeId = scheme.grading_scheme_id;
        moduleSuaSodoDiem.querySelector('input[placeholder="Nhập tên sơ đồ"]').value = scheme.scheme_name;
        moduleSuaSodoDiem.querySelector('input[placeholder="Nhập mã sơ đồ"]').value = scheme.scheme_code;
        
        // Xóa tất cả thành phần cũ
        const btnThemThanhPhan = moduleSuaSodoDiem.querySelector('#themthanhphan');
        const container = btnThemThanhPhan.parentElement;
        const existingComponents = container.querySelectorAll('.themthanhphandiem');
        existingComponents.forEach(comp => comp.remove());
        
        // Thêm các thành phần từ database
        if (scheme.grading_components && scheme.grading_components.length > 0) {
            scheme.grading_components.forEach(comp => {
                const newThanhPhan = document.createElement('div');
                newThanhPhan.className = 'themthanhphandiem';
                newThanhPhan.innerHTML = `
                    <input type="text" placeholder="Tên thành phần điểm" class="tenthanhphandiem" value="${comp.component_name}">
                    <input type="text" placeholder="0%" class="phantramthanhphan" value="${comp.weight_percent}%">
                    <button class="xoathanhphandiem">Xoá</button>
                `;
                container.insertBefore(newThanhPhan, btnThemThanhPhan);
                attachXoaThanhPhanHandler(newThanhPhan.querySelector('.xoathanhphandiem'));
            });
        }
    }

    // Đóng modal khi click vào icon X
    const closeIcons = document.querySelectorAll('.title-sododiem .fa-xmark');
    closeIcons.forEach(icon => {
        icon.addEventListener('click', function() {
            frameModule.classList.remove('active');
            moduleThemSodoDiem.classList.remove('active');
            moduleSuaSodoDiem.classList.remove('active');
        });
    });

    // Đóng modal khi click vào nút hủy
    const btnHuy = document.querySelectorAll('.btn.huy');
    btnHuy.forEach(btn => {
        btn.addEventListener('click', function() {
            frameModule.classList.remove('active');
            moduleThemSodoDiem.classList.remove('active');
            moduleSuaSodoDiem.classList.remove('active');
        });
    });

    // Đóng modal khi click vào backdrop
    if (frameModule) {
        frameModule.addEventListener('click', function(e) {
            if (e.target === frameModule) {
                frameModule.classList.remove('active');
                moduleThemSodoDiem.classList.remove('active');
                moduleSuaSodoDiem.classList.remove('active');
            }
        });
    }

    // Xử lý hiển thị tooltip lock khi hover vào icon lock
    const lockTooltip = document.querySelector('.lock');
    if (lockTooltip) {
        document.addEventListener('mouseenter', function(e) {
            if (e.target && e.target.classList && e.target.classList.contains('fa-lock')) {
                const rect = e.target.getBoundingClientRect();
                lockTooltip.style.top = (rect.bottom + 10) + 'px';
                lockTooltip.style.left = (rect.left - 100) + 'px';
                lockTooltip.classList.add('active');
            }
        }, true);

        document.addEventListener('mouseleave', function(e) {
            if (e.target && e.target.classList && e.target.classList.contains('fa-lock')) {
                lockTooltip.classList.remove('active');
            }
        }, true);
    }

    // Xử lý thêm thành phần điểm trong modal Thêm
    const btnThemThanhPhanThem = moduleThemSodoDiem.querySelector('#themthanhphan');
    if (btnThemThanhPhanThem) {
        btnThemThanhPhanThem.addEventListener('click', function() {
            addNewComponent(moduleThemSodoDiem);
        });
    }

    // Xử lý thêm thành phần điểm trong modal Sửa
    const btnThemThanhPhanSua = moduleSuaSodoDiem.querySelector('#themthanhphan');
    if (btnThemThanhPhanSua) {
        btnThemThanhPhanSua.addEventListener('click', function() {
            addNewComponent(moduleSuaSodoDiem);
        });
    }

    // Hàm xử lý xóa thành phần điểm
    function attachXoaThanhPhanHandler(button) {
        button.addEventListener('click', function() {
            this.parentElement.remove();
        });
    }

    // Gắn sự kiện xóa cho các nút xóa thành phần có sẵn
    document.querySelectorAll('.xoathanhphandiem, #xoathanhphandiem').forEach(btn => {
        attachXoaThanhPhanHandler(btn);
    });

    /**
     * Xử lý nút Thêm sơ đồ điểm
     */
    const btnThem = moduleThemSodoDiem.querySelector('.btn.them');
    if (btnThem) {
        btnThem.addEventListener('click', function() {
            const tenSoDo = moduleThemSodoDiem.querySelector('input[placeholder="Nhập tên sơ đồ"]').value.trim();
            const maSoDo = moduleThemSodoDiem.querySelector('input[placeholder="Nhập mã sơ đồ"]').value.trim();
            
            if (!tenSoDo || !maSoDo) {
                alert('Vui lòng nhập đầy đủ tên và mã sơ đồ!');
                return;
            }

            // Lấy tất cả thành phần điểm
            const thanhPhanList = moduleThemSodoDiem.querySelectorAll('.themthanhphandiem');
            const components = [];
            let tongTrongSo = 0;

            thanhPhanList.forEach(tp => {
                const ten = tp.querySelector('.tenthanhphandiem').value.trim();
                const trongSoStr = tp.querySelector('.phantramthanhphan').value.trim();
                if (ten && trongSoStr) {
                    const percent = parseFloat(trongSoStr.replace('%', ''));
                    tongTrongSo += percent;
                    components.push({ 
                        component_name: ten, 
                        weight_percent: percent 
                    });
                }
            });

            if (components.length === 0) {
                alert('Vui lòng thêm ít nhất một thành phần điểm!');
                return;
            }

            if (Math.abs(tongTrongSo - 100) > 0.01) {
                alert('Tổng trọng số phải bằng 100%!');
                return;
            }

            // Gọi API thêm sơ đồ
            fetch('/admin/quy-tac/api/grading-schemes', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify({
                    scheme_code: maSoDo,
                    scheme_name: tenSoDo,
                    components: components
                })
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    alert(result.message);
                    loadAllData(); // Reload dữ liệu
                    frameModule.classList.remove('active');
                    moduleThemSodoDiem.classList.remove('active');
                } else {
                    alert('Lỗi: ' + result.message);
                }
            })
            .catch(error => {
                console.error('Lỗi:', error);
                alert('Có lỗi xảy ra khi thêm sơ đồ điểm');
            });
        });
    }

    /**
     * Xử lý nút Chỉnh sửa sơ đồ điểm
     */
    const btnChinhSua = moduleSuaSodoDiem.querySelector('.btn.chinhsua');
    if (btnChinhSua) {
        btnChinhSua.addEventListener('click', function() {
            const schemeId = moduleSuaSodoDiem.dataset.schemeId;
            const tenSoDo = moduleSuaSodoDiem.querySelector('input[placeholder="Nhập tên sơ đồ"]').value.trim();
            const maSoDo = moduleSuaSodoDiem.querySelector('input[placeholder="Nhập mã sơ đồ"]').value.trim();
            
            if (!tenSoDo || !maSoDo) {
                alert('Vui lòng nhập đầy đủ tên và mã sơ đồ!');
                return;
            }

            // Lấy tất cả thành phần điểm
            const thanhPhanList = moduleSuaSodoDiem.querySelectorAll('.themthanhphandiem');
            const components = [];
            let tongTrongSo = 0;

            thanhPhanList.forEach(tp => {
                const ten = tp.querySelector('.tenthanhphandiem').value.trim();
                const trongSoStr = tp.querySelector('.phantramthanhphan').value.trim();
                if (ten && trongSoStr) {
                    const percent = parseFloat(trongSoStr.replace('%', ''));
                    tongTrongSo += percent;
                    components.push({ 
                        component_name: ten, 
                        weight_percent: percent 
                    });
                }
            });

            if (components.length === 0) {
                alert('Vui lòng thêm ít nhất một thành phần điểm!');
                return;
            }

            if (Math.abs(tongTrongSo - 100) > 0.01) {
                alert('Tổng trọng số phải bằng 100%!');
                return;
            }

            // Gọi API sửa sơ đồ
            fetch(`/admin/quy-tac/api/grading-schemes/${schemeId}`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify({
                    scheme_code: maSoDo,
                    scheme_name: tenSoDo,
                    components: components
                })
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    alert(result.message);
                    loadAllData(); // Reload dữ liệu
                    frameModule.classList.remove('active');
                    moduleSuaSodoDiem.classList.remove('active');
                } else {
                    alert('Lỗi: ' + result.message);
                }
            })
            .catch(error => {
                console.error('Lỗi:', error);
                alert('Có lỗi xảy ra khi cập nhật sơ đồ điểm');
            });
        });
    }

    // Xử lý nút Xóa sơ đồ điểm
    function attachSoDoEventHandlers(sodoElement) {
        // Cập nhật visibility của lock icon và trạng thái trash icon
        updateLockIconVisibility(sodoElement);
        updateTrashIconState(sodoElement);
        
        const frameHien = sodoElement.querySelector('.frame-hien');
        const frameAn = sodoElement.querySelector('.frame-an');
        const penIcon = sodoElement.querySelector('.fa-pen-to-square');
        const trashIcon = sodoElement.querySelector('.fa-trash');

        // Toggle frame-an
        if (frameHien && frameAn) {
            frameHien.addEventListener('click', function() {
                frameAn.classList.toggle('active');
            });
        }

        // Mở modal sửa
        if (penIcon) {
            penIcon.addEventListener('click', function(e) {
                e.stopPropagation();
                const schemeId = sodoElement.dataset.schemeId;
                
                // Load dữ liệu sơ đồ từ API
                fetch(`/admin/quy-tac/api/grading-schemes/${schemeId}`)
                    .then(response => response.json())
                    .then(result => {
                        if (result.success) {
                            loadEditForm(result.data);
                            frameModule.classList.add('active');
                            moduleSuaSodoDiem.classList.add('active');
                            moduleThemSodoDiem.classList.remove('active');
                        } else {
                            alert('Lỗi: ' + result.message);
                        }
                    })
                    .catch(error => {
                        console.error('Lỗi:', error);
                        alert('Có lỗi xảy ra khi tải dữ liệu');
                    });
            });
        }

        // Xóa sơ đồ
        if (trashIcon) {
            trashIcon.addEventListener('click', function(e) {
                e.stopPropagation();
                
                // Kiểm tra nếu nút bị vô hiệu hóa
                if (this.classList.contains('disabled')) {
                    alert('Không thể xóa sơ đồ điểm đang được sử dụng!');
                    return;
                }
                
                if (confirm('Bạn có chắc chắn muốn xóa sơ đồ điểm này?')) {
                    const schemeId = sodoElement.dataset.schemeId;
                    
                    // Gọi API xóa
                    fetch(`/admin/quy-tac/api/grading-schemes/${schemeId}`, {
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': csrfToken
                        }
                    })
                    .then(response => response.json())
                    .then(result => {
                        if (result.success) {
                            alert(result.message);
                            sodoElement.remove();
                        } else {
                            alert('Lỗi: ' + result.message);
                        }
                    })
                    .catch(error => {
                        console.error('Lỗi:', error);
                        alert('Có lỗi xảy ra khi xóa sơ đồ điểm');
                    });
                }
            });
        }
    }
});
