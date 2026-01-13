// js/loadComponents.js - Phiên bản tuần tự với debug
async function loadComponents() {
  console.log('Bắt đầu load components...');
  try {
    // Load header trước
    console.log('Đang tải header...');
    const headerResponse = await fetch('components/header.html');
    console.log('Header response status:', headerResponse.status);
    if (!headerResponse.ok) {
      throw new Error(`HTTP ${headerResponse.status}: Không tìm thấy file header.html`);
    }
    const headerHTML = await headerResponse.text();
    console.log('Header HTML length:', headerHTML.length);
    
    const headerPlaceholder = document.getElementById('header-placeholder');
    if (headerPlaceholder) {
      headerPlaceholder.outerHTML = headerHTML;
      console.log('Đã chèn header');
    } else {
      console.error('Không tìm thấy placeholder cho header');
    }
    
    // Load sidebar sau
    console.log('Đang tải sidebar...');
    const sidebarResponse = await fetch('components/sidebar.html');
    console.log('Sidebar response status:', sidebarResponse.status);
    if (!sidebarResponse.ok) {
      throw new Error(`HTTP ${sidebarResponse.status}: Không tìm thấy file sidebar.html`);
    }
    const sidebarHTML = await sidebarResponse.text();
    console.log('Sidebar HTML length:', sidebarHTML.length);
    
    const sidebarPlaceholder = document.getElementById('sidebar-placeholder');
    if (sidebarPlaceholder) {
      sidebarPlaceholder.outerHTML = sidebarHTML;
      console.log('Đã chèn sidebar');
    } else {
      console.error('Không tìm thấy placeholder cho sidebar');
    }
    
    // Gắn sự kiện
    attachEvents();
    updateSidebarActiveItem();
    
    console.log('Components loaded successfully');
  } catch (error) {
    console.error('Error loading components:', error);
  }
}

function attachEvents() {
  console.log('Đang gắn sự kiện logout...');
  // Gắn sự kiện cho cả hai nút logout
  document.querySelectorAll('.header-logout-btn, .logout-btn').forEach(btn => {
    btn.addEventListener('click', handleLogout);
  });
}

function handleLogout() {
  console.log('Đăng xuất...');
  window.location.href = 'login.html';
}

function updateSidebarActiveItem() {
  const currentPage = window.location.pathname.split('/').pop();
  console.log('Trang hiện tại:', currentPage);
  const navItems = document.querySelectorAll('.nav-item');
  
  navItems.forEach(item => {
    item.classList.remove('active');
    const href = item.getAttribute('href');
    
    if (href && (href === currentPage || 
        (currentPage === '' && href === 'lecturerDashboard.html'))) {
      item.classList.add('active');
      item.setAttribute('aria-current', 'page');
    }
  });
}

// Chạy khi DOM sẵn sàng
if (document.readyState === 'loading') {
  console.log('Document đang loading, chờ DOMContentLoaded');
  document.addEventListener('DOMContentLoaded', loadComponents);
} else {
  console.log('Document đã sẵn sàng, chạy ngay');
  loadComponents();
}