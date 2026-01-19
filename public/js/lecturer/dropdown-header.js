// dropdown-header.js
// Shared enhanced dropdown for Lecturer header (class-select) used by attendance_header.blade.php
// - Uses the same UI style as attendance page (trigger + menu + search)
// - Safe to include on pages that have #class-select

(() => {
  const enhanceClassSelect = () => {
    const classSelect = document.getElementById('class-select');
    if (!classSelect) return;

    const wrapper = classSelect.closest('.select-wrapper');
    if (!wrapper) return;

    // Already enhanced
    if (wrapper.querySelector('.select-trigger') || wrapper.querySelector('.session-menu')) {
      wrapper.classList.add('dropdown-enhanced');
      return;
    }

    const options = Array.from(classSelect.querySelectorAll('option'));
    const selectedIndex = Math.max(0, options.findIndex(o => o.selected));
    const selectedOption = options[selectedIndex] || options[0] || null;

    // Label text (best effort)
    const labelEl = document.querySelector('label[for="class-select"]');
    const labelText = labelEl ? (labelEl.textContent || '').trim() : 'Lớp học phần';

    // Trigger
    const trigger = document.createElement('div');
    trigger.className = 'select-trigger';
    trigger.innerHTML = `<span class="current-text">${selectedOption ? selectedOption.text : 'Chưa có lựa chọn'}</span><div class="select-arrow">▼</div>`;
    wrapper.appendChild(trigger);

    // Menu (attendance-style)
    const searchIcon = (window.ASSETS && window.ASSETS.searchIcon) ? window.ASSETS.searchIcon : '';

    const menu = document.createElement('div');
    menu.className = 'session-menu';
    menu.innerHTML = `
      <h3 class="menu-title">${labelText}</h3>
      <div class="search-box-container">
        <input type="text" class="search-field" placeholder="Nhập nội dung cần tìm....">
        <button class="search-submit" type="button">${searchIcon ? `<img src="${searchIcon}" alt="search">` : ''}</button>
      </div>
      <ul class="menu-list">
        ${options.map((opt, index) => `
          <li class="menu-item ${index === selectedIndex ? 'active' : ''}" data-value="${String(opt.value)}">${opt.text}</li>
        `).join('')}
      </ul>
    `;
    wrapper.appendChild(menu);

    wrapper.classList.add('dropdown-enhanced');

    const searchField = menu.querySelector('.search-field');
    const menuItems = Array.from(menu.querySelectorAll('.menu-item'));

    // Open/close
    trigger.addEventListener('click', (e) => {
      e.stopPropagation();
      wrapper.classList.toggle('active-menu');
      if (wrapper.classList.contains('active-menu') && searchField) {
        searchField.focus();
        searchField.select();
      }
    });

    // Select item
    menuItems.forEach((item) => {
      item.addEventListener('click', () => {
        const current = menu.querySelector('.menu-item.active');
        if (current) current.classList.remove('active');
        item.classList.add('active');

        trigger.querySelector('.current-text').textContent = item.textContent || '';

        const value = item.getAttribute('data-value') || '';
        classSelect.value = value;
        classSelect.dispatchEvent(new Event('change'));

        wrapper.classList.remove('active-menu');
      });
    });

    // Search
    if (searchField) {
      searchField.addEventListener('input', (e) => {
        const filter = String(e.target.value || '').toLowerCase();
        menuItems.forEach((item) => {
          const text = (item.textContent || '').toLowerCase();
          item.style.display = text.includes(filter) ? 'block' : 'none';
        });
      });
    }

    // Close on outside click (only wire once)
    if (!document.body.dataset.dropdownHeaderCloseWired) {
      document.body.dataset.dropdownHeaderCloseWired = '1';
      document.addEventListener('click', () => {
        document.querySelectorAll('.select-wrapper.dropdown-enhanced.active-menu')
          .forEach((w) => w.classList.remove('active-menu'));
      });
    }
  };

  document.addEventListener('DOMContentLoaded', () => {
    enhanceClassSelect();
  });
})();
