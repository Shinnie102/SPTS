// Toggle hiển thị frame-an khi click vào frame-hien
document.addEventListener('DOMContentLoaded', function() {
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

    // Kiểm tra tất cả sơ đồ có sẵn
    document.querySelectorAll('.sododiem').forEach(sodo => {
        updateLockIconVisibility(sodo);
        updateTrashIconState(sodo);
    });

    const frameHienList = document.querySelectorAll('.frame-hien');
    
    frameHienList.forEach(frameHien => {
        frameHien.addEventListener('click', function() {
            // Tìm frame-an tương ứng (là phần tử kế tiếp)
            const frameAn = this.nextElementSibling;
            
            if (frameAn && frameAn.classList.contains('frame-an')) {
                // Toggle class active để hiển thị/ẩn
                frameAn.classList.toggle('active');
            }
        });
    });

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
        });
    }

    // Hiển thị modal sửa sơ đồ điểm khi click vào icon pen
    const penIcons = document.querySelectorAll('.fa-pen-to-square');
    penIcons.forEach(icon => {
        icon.addEventListener('click', function(e) {
            e.stopPropagation(); // Ngăn sự kiện click lan ra frame-hien
            frameModule.classList.add('active');
            moduleSuaSodoDiem.classList.add('active');
            moduleThemSodoDiem.classList.remove('active');
        });
    });

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
    frameModule.addEventListener('click', function(e) {
        if (e.target === frameModule) {
            frameModule.classList.remove('active');
            moduleThemSodoDiem.classList.remove('active');
            moduleSuaSodoDiem.classList.remove('active');
        }
    });

    // Xử lý hiển thị tooltip lock khi hover vào icon lock
    const lockIcons = document.querySelectorAll('.fa-lock');
    const lockTooltip = document.querySelector('.lock');

    lockIcons.forEach(icon => {
        icon.addEventListener('mouseenter', function(e) {
            const rect = this.getBoundingClientRect();
            lockTooltip.style.top = (rect.bottom + 10) + 'px';
            lockTooltip.style.left = (rect.left - 100) + 'px';
            lockTooltip.classList.add('active');
        });

        icon.addEventListener('mouseleave', function() {
            lockTooltip.classList.remove('active');
        });
    });

    // Ẩn tooltip khi hover ra khỏi nó
    if (lockTooltip) {
        lockTooltip.addEventListener('mouseenter', function() {
            this.classList.add('active');
        });

        lockTooltip.addEventListener('mouseleave', function() {
            this.classList.remove('active');
        });
    }

    // Xử lý thêm thành phần điểm trong modal Thêm
    const btnThemThanhPhanThem = moduleThemSodoDiem.querySelector('#themthanhphan');
    const containerThanhPhanThem = btnThemThanhPhanThem.parentElement;
    
    btnThemThanhPhanThem.addEventListener('click', function() {
        const newThanhPhan = document.createElement('div');
        newThanhPhan.className = 'themthanhphandiem';
        newThanhPhan.innerHTML = `
            <input type="text" placeholder="Tên thành phần điểm" class="tenthanhphandiem">
            <input type="text" placeholder="0%" class="phantramthanhphan">
            <button class="xoathanhphandiem">Xoá</button>
        `;
        containerThanhPhanThem.insertBefore(newThanhPhan, btnThemThanhPhanThem);
        attachXoaThanhPhanHandler(newThanhPhan.querySelector('.xoathanhphandiem'));
    });

    // Xử lý thêm thành phần điểm trong modal Sửa
    const btnThemThanhPhanSua = moduleSuaSodoDiem.querySelector('#themthanhphan');
    const containerThanhPhanSua = btnThemThanhPhanSua.parentElement;
    
    btnThemThanhPhanSua.addEventListener('click', function() {
        const newThanhPhan = document.createElement('div');
        newThanhPhan.className = 'themthanhphandiem';
        newThanhPhan.innerHTML = `
            <input type="text" placeholder="Tên thành phần điểm" class="tenthanhphandiem">
            <input type="text" placeholder="0%" class="phantramthanhphan">
            <button class="xoathanhphandiem">Xoá</button>
        `;
        containerThanhPhanSua.insertBefore(newThanhPhan, btnThemThanhPhanSua);
        attachXoaThanhPhanHandler(newThanhPhan.querySelector('.xoathanhphandiem'));
    });

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

    // Xử lý nút Thêm sơ đồ điểm
    const btnThem = moduleThemSodoDiem.querySelector('.btn.them');
    btnThem.addEventListener('click', function() {
        const tenSoDo = moduleThemSodoDiem.querySelector('input[placeholder="Nhập tên sơ đồ"]').value;
        const maSoDo = moduleThemSodoDiem.querySelector('input[placeholder="Nhập mã sơ đồ"]').value;
        
        if (!tenSoDo || !maSoDo) {
            alert('Vui lòng nhập đầy đủ tên và mã sơ đồ!');
            return;
        }

        // Lấy tất cả thành phần điểm
        const thanhPhanList = moduleThemSodoDiem.querySelectorAll('.themthanhphandiem');
        const thanhPhans = [];
        let tongTrongSo = 0;

        thanhPhanList.forEach(tp => {
            const ten = tp.querySelector('.tenthanhphandiem').value;
            const trongSo = tp.querySelector('.phantramthanhphan').value;
            if (ten && trongSo) {
                const percent = parseInt(trongSo.replace('%', ''));
                tongTrongSo += percent;
                thanhPhans.push({ ten, trongSo });
            }
        });

        if (tongTrongSo !== 100) {
            alert('Tổng trọng số phải bằng 100%!');
            return;
        }

        // Tạo sơ đồ mới
        const newSoDoDiem = document.createElement('div');
        newSoDoDiem.className = 'sododiem';
        
        let thanhPhanHTML = '';
        thanhPhans.forEach(tp => {
            thanhPhanHTML += `
                <div class="frame-thanhphan">
                    <p class="tenthanhphan">${tp.ten}</p>
                    <p class="trongso">${tp.trongSo}</p>
                </div>
            `;
        });

        newSoDoDiem.innerHTML = `
            <div class="frame-hien">
                <div class="left">
                    <div class="title-sodo">
                        <p class="tensodo">${tenSoDo}</p>
                        <i class="fa-solid fa-lock hidden"></i>
                    </div>
                    <p class="masodo">Mã: ${maSoDo}</p>
                    <p class="dangdung">Đang dùng: <span>0</span> lớp</p>
                </div>
                <div class="right">
                    <div class="status" id="hoatdong">Hoạt động</div>
                    <i class="fa-regular fa-pen-to-square"></i>
                    <i class="fa-solid fa-trash"></i>
                </div>
            </div>
            <div class="frame-an">
                <p class="thanhphandiem">Thanh phần điểm:</p>
                ${thanhPhanHTML}
            </div>
        `;

        // Thêm vào danh sách
        document.getElementById('frame-sododiem').appendChild(newSoDoDiem);

        // Gắn sự kiện cho sơ đồ mới
        attachSoDoEventHandlers(newSoDoDiem);

        // Reset form và đóng modal
        moduleThemSodoDiem.querySelectorAll('input').forEach(input => input.value = '');
        frameModule.classList.remove('active');
        moduleThemSodoDiem.classList.remove('active');
    });

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
        frameHien.addEventListener('click', function() {
            frameAn.classList.toggle('active');
        });

        // Mở modal sửa
        penIcon.addEventListener('click', function(e) {
            e.stopPropagation();
            frameModule.classList.add('active');
            moduleSuaSodoDiem.classList.add('active');
            moduleThemSodoDiem.classList.remove('active');
            
            // Load dữ liệu vào form sửa
            const tenSoDo = sodoElement.querySelector('.tensodo').textContent;
            const maSoDo = sodoElement.querySelector('.masodo').textContent.replace('Mã: ', '');
            moduleSuaSodoDiem.querySelector('input[placeholder="Nhập tên sơ đồ"]').value = tenSoDo;
            moduleSuaSodoDiem.querySelector('input[placeholder="Nhập mã sơ đồ"]').value = maSoDo;
        });

        // Xóa sơ đồ
        trashIcon.addEventListener('click', function(e) {
            e.stopPropagation();
            
            // Kiểm tra nếu nút bị vô hiệu hóa
            if (this.classList.contains('disabled')) {
                alert('Không thể xóa sơ đồ điểm đang được sử dụng!');
                return;
            }
            
            if (confirm('Bạn có chắc chắn muốn xóa sơ đồ điểm này?')) {
                sodoElement.remove();
            }
        });
    }

    // Gắn sự kiện cho các sơ đồ có sẵn
    document.querySelectorAll('.sododiem').forEach(sodo => {
        const trashIcon = sodo.querySelector('.fa-trash');
        if (trashIcon) {
            trashIcon.addEventListener('click', function(e) {
                e.stopPropagation();
                
                // Kiểm tra nếu nút bị vô hiệu hóa
                if (this.classList.contains('disabled')) {
                    alert('Không thể xóa sơ đồ điểm đang được sử dụng!');
                    return;
                }
                
                if (confirm('Bạn có chắc chắn muốn xóa sơ đồ điểm này?')) {
                    sodo.remove();
                }
            });
        }
    });
});