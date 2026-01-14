document.addEventListener('DOMContentLoaded', function() {
        // Render toàn bộ danh sách lớp
        renderFullClassTable();
        
        // Thêm chức năng tìm kiếm
        setupSearch();
      });
      
      function renderFullClassTable() {
        const tableBody = document.getElementById('full-class-table-body');
        if (!tableBody) return;
        
        tableBody.innerHTML = '';
        
        // Hiển thị TOÀN BỘ danh sách lớp (không giới hạn)
        mockData.classes.forEach(cls => {
          const row = document.createElement('tr');
          row.setAttribute('role', 'row');
          row.setAttribute('data-code', cls.code.toLowerCase());
          row.setAttribute('data-name', cls.name.toLowerCase());
          
          // Xác định class cho status
          let statusClass = cls.status;
          if (cls.status === 'completed') statusClass = 'completed';
          if (cls.status === 'pending') statusClass = 'pending';
          if (cls.status === 'locked') statusClass = 'locked';
          
          row.innerHTML = `
            <td role="cell">${cls.code}</td>
            <td role="cell">${cls.name}</td>
            <td role="cell">${cls.total}</td>
            <td role="cell"><span class="status ${statusClass}">${cls.statusText}</span></td>
            <td role="cell"><a href="#" class="action-link">Xem chi tiết →</a></td>
          `;
          
          tableBody.appendChild(row);
        });
      }
      
      function setupSearch() {
        const searchInput = document.getElementById('search-class-input');
        if (!searchInput) return;
        
        searchInput.addEventListener('input', function() {
          const searchTerm = this.value.toLowerCase().trim();
          const rows = document.querySelectorAll('#full-class-table-body tr');
          
          if (searchTerm === '') {
            // Hiển thị tất cả nếu ô tìm kiếm trống
            rows.forEach(row => {
              row.style.display = '';
            });
            return;
          }
          
          // Lọc theo mã lớp và tên môn học
          rows.forEach(row => {
            const code = row.getAttribute('data-code');
            const name = row.getAttribute('data-name');
            
            if (code.includes(searchTerm) || name.includes(searchTerm)) {
              row.style.display = '';
            } else {
              row.style.display = 'none';
            }
          });
        });
        
        // Thêm phím tắt (Esc để xóa tìm kiếm)
        searchInput.addEventListener('keydown', function(e) {
          if (e.key === 'Escape') {
            this.value = '';
            const rows = document.querySelectorAll('#full-class-table-body tr');
            rows.forEach(row => {
              row.style.display = '';
            });
          }
        });
      }