// ==================== CONFIGURATION ====================
const API_BASE_URL = '/admin/thoi-gian/api';
const CSRF_TOKEN = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

// ==================== GLOBAL STATE ====================
let academicYearsData = [];
let currentAcademicYearId = null;

// ==================== DOM READY ====================
document.addEventListener('DOMContentLoaded', () => {
	initializeEventListeners();
	loadAcademicYears();
});

// ==================== INITIALIZE EVENT LISTENERS ====================
function initializeEventListeners() {
	// Modal controls
	const overlay = createOverlay();
	setupModalControls(overlay);
	
	// Academic Year actions
	document.getElementById('add-namhoc')?.addEventListener('click', () => showModal('.themnamhoc'));
	document.getElementById('btn-add-year')?.addEventListener('click', handleAddAcademicYear);
	
	// Semester actions
	document.getElementById('btn-add-semester')?.addEventListener('click', handleAddSemester);
	document.getElementById('btn-update-semester')?.addEventListener('click', handleUpdateSemester);
	
	// Close modal buttons
	document.querySelectorAll('.close-modal').forEach(btn => {
		btn.addEventListener('click', hideAllModals);
	});
}

// ==================== OVERLAY & MODAL MANAGEMENT ====================
function createOverlay() {
	let overlay = document.querySelector('.modal-overlay');
	if (!overlay) {
		overlay = document.createElement('div');
		overlay.className = 'modal-overlay';
		document.body.appendChild(overlay);
	}
	return overlay;
}

function setupModalControls(overlay) {
	overlay.addEventListener('click', hideAllModals);
}

function showModal(selector) {
	hideAllModals();
	const modal = document.querySelector(selector);
	const overlay = document.querySelector('.modal-overlay');
	
	if (modal) {
		modal.classList.add('active');
		overlay?.classList.add('show');
	}
}

function hideAllModals() {
	document.querySelectorAll('.frame-noi.active').forEach(modal => {
		modal.classList.remove('active');
	});
	document.querySelector('.modal-overlay')?.classList.remove('show');
	
	// Reset form inputs
	resetForms();
}

function resetForms() {
	document.getElementById('year-code-input').value = '';
	document.getElementById('year-start-date').value = '';
	document.getElementById('year-end-date').value = '';
	
	document.getElementById('semester-code-input').value = '';
	document.getElementById('semester-start-date').value = '';
	document.getElementById('semester-end-date').value = '';
	
	document.getElementById('edit-semester-code').value = '';
	document.getElementById('edit-semester-start-date').value = '';
	document.getElementById('edit-semester-end-date').value = '';
	document.getElementById('edit-semester-id').value = '';
}

// ==================== API CALLS ====================

/**
 * Load tất cả năm học từ API
 */
async function loadAcademicYears() {
	try {
		showLoading();
		
		const response = await fetch(`${API_BASE_URL}/academic-years`, {
			method: 'GET',
			headers: {
				'Content-Type': 'application/json',
				'X-CSRF-TOKEN': CSRF_TOKEN
			}
		});
		
		const result = await response.json();
		
		if (result.success) {
			academicYearsData = result.data;
			renderAcademicYears(result.data);
		} else {
			showNotification('Không thể tải dữ liệu: ' + result.message, 'error');
		}
	} catch (error) {
		console.error('Error loading academic years:', error);
		showNotification('Có lỗi xảy ra khi tải dữ liệu', 'error');
	} finally {
		hideLoading();
	}
}

/**
 * Thêm năm học mới
 */
async function handleAddAcademicYear() {
	const yearCode = document.getElementById('year-code-input').value.trim();
	const startDate = document.getElementById('year-start-date').value;
	const endDate = document.getElementById('year-end-date').value;
	
	// Validation
	if (!yearCode || !startDate || !endDate) {
		showNotification('Vui lòng điền đầy đủ thông tin', 'error');
		return;
	}
	
	// Validate format năm học (YYYY-YYYY)
	const yearCodePattern = /^\d{4}-\d{4}$/;
	if (!yearCodePattern.test(yearCode)) {
		showNotification('Tên năm học phải có định dạng YYYY-YYYY (VD: 2024-2025)', 'error');
		return;
	}
	
	try {
		const response = await fetch(`${API_BASE_URL}/academic-years`, {
			method: 'POST',
			headers: {
				'Content-Type': 'application/json',
				'X-CSRF-TOKEN': CSRF_TOKEN
			},
			body: JSON.stringify({
				year_code: yearCode,
				start_date: startDate,
				end_date: endDate
			})
		});
		
		const result = await response.json();
		
		if (result.success) {
			showNotification('Thêm năm học thành công', 'success');
			hideAllModals();
			loadAcademicYears(); // Reload data
		} else {
			showNotification(result.message, 'error');
		}
	} catch (error) {
		console.error('Error adding academic year:', error);
		showNotification('Có lỗi xảy ra khi thêm năm học', 'error');
	}
}

/**
 * Xóa năm học
 */
async function handleDeleteAcademicYear(academicYearId, yearCode) {
	if (!confirm(`Bạn có chắc chắn muốn xóa năm học "${yearCode}"?`)) {
		return;
	}
	
	try {
		const response = await fetch(`${API_BASE_URL}/academic-years/${academicYearId}`, {
			method: 'DELETE',
			headers: {
				'Content-Type': 'application/json',
				'X-CSRF-TOKEN': CSRF_TOKEN
			}
		});
		
		const result = await response.json();
		
		if (result.success) {
			showNotification('Xóa năm học thành công', 'success');
			loadAcademicYears(); // Reload data
		} else {
			showNotification(result.message, 'error');
		}
	} catch (error) {
		console.error('Error deleting academic year:', error);
		showNotification('Có lỗi xảy ra khi xóa năm học', 'error');
	}
}

/**
 * Mở modal thêm học kỳ
 */
function openAddSemesterModal(academicYearId, yearCode) {
	currentAcademicYearId = academicYearId;
	document.getElementById('target-academic-year-id').value = academicYearId;
	document.getElementById('target-year-name').textContent = yearCode;
	
	// Set min/max date dựa trên năm học
	const yearData = academicYearsData.find(y => y.academic_year_id === academicYearId);
	if (yearData) {
		const startDateInput = document.getElementById('semester-start-date');
		const endDateInput = document.getElementById('semester-end-date');
		
		// Convert DD/MM/YYYY to YYYY-MM-DD
		const [startDay, startMonth, startYear] = yearData.start_date.split('/');
		const [endDay, endMonth, endYear] = yearData.end_date.split('/');
		
		startDateInput.min = `${startYear}-${startMonth}-${startDay}`;
		startDateInput.max = `${endYear}-${endMonth}-${endDay}`;
		endDateInput.min = `${startYear}-${startMonth}-${startDay}`;
		endDateInput.max = `${endYear}-${endMonth}-${endDay}`;
	}
	
	showModal('.themkihoc');
}

/**
 * Thêm học kỳ mới
 */
async function handleAddSemester() {
	const academicYearId = document.getElementById('target-academic-year-id').value;
	const semesterCode = document.getElementById('semester-code-input').value.trim();
	const startDate = document.getElementById('semester-start-date').value;
	const endDate = document.getElementById('semester-end-date').value;
	
	// Validation
	if (!semesterCode || !startDate || !endDate) {
		showNotification('Vui lòng điền đầy đủ thông tin', 'error');
		return;
	}
	
	try {
		const response = await fetch(`${API_BASE_URL}/semesters`, {
			method: 'POST',
			headers: {
				'Content-Type': 'application/json',
				'X-CSRF-TOKEN': CSRF_TOKEN
			},
			body: JSON.stringify({
				academic_year_id: parseInt(academicYearId),
				semester_code: semesterCode,
				start_date: startDate,
				end_date: endDate
			})
		});
		
		const result = await response.json();
		
		if (result.success) {
			showNotification('Thêm học kỳ thành công', 'success');
			hideAllModals();
			loadAcademicYears(); // Reload data
		} else {
			showNotification(result.message, 'error');
		}
	} catch (error) {
		console.error('Error adding semester:', error);
		showNotification('Có lỗi xảy ra khi thêm học kỳ', 'error');
	}
}

/**
 * Mở modal sửa học kỳ
 */
async function openEditSemesterModal(semesterId) {
	try {
		const response = await fetch(`${API_BASE_URL}/semesters/${semesterId}`, {
			method: 'GET',
			headers: {
				'Content-Type': 'application/json',
				'X-CSRF-TOKEN': CSRF_TOKEN
			}
		});
		
		const result = await response.json();
		
		if (result.success) {
			const semester = result.data;
			// Hiển thị modal trước, tránh reset form làm mất dữ liệu
			showModal('.editkyhoc');
			
			document.getElementById('edit-semester-id').value = semester.semester_id;
			document.getElementById('edit-semester-code').value = semester.semester_code;
			document.getElementById('edit-semester-start-date').value = semester.start_date;
			document.getElementById('edit-semester-end-date').value = semester.end_date;
		} else {
			showNotification(result.message, 'error');
		}
	} catch (error) {
		console.error('Error loading semester:', error);
		showNotification('Có lỗi xảy ra khi tải thông tin học kỳ', 'error');
	}
}

/**
 * Cập nhật học kỳ
 */
async function handleUpdateSemester() {
	const semesterId = document.getElementById('edit-semester-id').value;
	const semesterCode = document.getElementById('edit-semester-code').value.trim();
	const startDate = document.getElementById('edit-semester-start-date').value;
	const endDate = document.getElementById('edit-semester-end-date').value;
	
	// Validation
	if (!semesterCode || !startDate || !endDate) {
		showNotification('Vui lòng điền đầy đủ thông tin', 'error');
		return;
	}
	
	try {
		const response = await fetch(`${API_BASE_URL}/semesters/${semesterId}`, {
			method: 'PUT',
			headers: {
				'Content-Type': 'application/json',
				'X-CSRF-TOKEN': CSRF_TOKEN
			},
			body: JSON.stringify({
				semester_code: semesterCode,
				start_date: startDate,
				end_date: endDate
			})
		});
		
		const result = await response.json();
		
		if (result.success) {
			showNotification('Cập nhật học kỳ thành công', 'success');
			hideAllModals();
			loadAcademicYears(); // Reload data
		} else {
			showNotification(result.message, 'error');
		}
	} catch (error) {
		console.error('Error updating semester:', error);
		showNotification('Có lỗi xảy ra khi cập nhật học kỳ', 'error');
	}
}

/**
 * Xóa học kỳ
 */
async function handleDeleteSemester(semesterId, semesterCode) {
	if (!confirm(`Bạn có chắc chắn muốn xóa học kỳ "${semesterCode}"?`)) {
		return;
	}
	
	try {
		const response = await fetch(`${API_BASE_URL}/semesters/${semesterId}`, {
			method: 'DELETE',
			headers: {
				'Content-Type': 'application/json',
				'X-CSRF-TOKEN': CSRF_TOKEN
			}
		});
		
		const result = await response.json();
		
		if (result.success) {
			showNotification('Xóa học kỳ thành công', 'success');
			loadAcademicYears(); // Reload data
		} else {
			showNotification(result.message, 'error');
		}
	} catch (error) {
		console.error('Error deleting semester:', error);
		showNotification('Có lỗi xảy ra khi xóa học kỳ', 'error');
	}
}

// ==================== RENDER FUNCTIONS ====================

/**
 * Render danh sách năm học
 */
function renderAcademicYears(years) {
	const container = document.getElementById('academic-years-container');
	
	if (!years || years.length === 0) {
		container.innerHTML = `
			<div style="text-align: center; padding: 3rem; color: #615F5F;">
				<i class="fa-solid fa-inbox" style="font-size: 3rem; margin-bottom: 1rem; opacity: 0.5;"></i>
				<p style="font-size: 1.2rem;">Chưa có năm học nào</p>
				<p style="margin-top: 0.5rem;">Nhấn "Thêm năm học" để bắt đầu</p>
			</div>
		`;
		return;
	}
	
	container.innerHTML = years.map(year => renderAcademicYear(year)).join('');
	
	// Attach event listeners sau khi render
	attachAcademicYearEventListeners();
}

/**
 * Render một năm học
 */
function renderAcademicYear(year) {
	const statusClass = year.status_code === 'ACTIVE' ? 'dangdienra' : 'daketthuc';
	const statusText = year.status_name;
	
	return `
		<div class="frame-nam" data-year-id="${year.academic_year_id}">
			<div class="nam">
				<div class="frame-left">
					<div>
						<i class="fa-solid fa-chevron-right toggle-icon"></i>
						<p class="tennamhoc">${year.year_code}</p>
					</div>
					<p class="thoigianhocki">
						<span class="batdau">${year.start_date}</span> - 
						<span class="ketthuc">${year.end_date}</span> - 
						<span class="sohocki">${year.semester_count}</span> học kỳ
					</p>
				</div>
				<div class="frame-right">
					<div class="trangthai-hocki ${statusClass}">
						<p class="trangthai">${statusText}</p>
					</div>
					<i class="fa-solid fa-trash delete-year" data-year-id="${year.academic_year_id}" data-year-code="${year.year_code}"></i>
				</div>
			</div>
			<div class="frame-ky">
				${year.semesters.map(semester => renderSemester(semester)).join('')}
				<button class="btn-themhocky" data-year-id="${year.academic_year_id}" data-year-code="${year.year_code}">
					<i class="fa-solid fa-plus"></i> Thêm học kỳ vào <span class="namhoc">${year.year_code}</span>
				</button>
			</div>
		</div>
	`;
}

/**
 * Render một học kỳ
 */
function renderSemester(semester) {
	const statusClass = semester.status_code === 'ONGOING' ? 'dangdienra' : 'daketthuc';
	const statusText = semester.status_name;
	
	return `
		<div class="ky">
			<div class="frame-left">
				<p class="tenkyhoc">${semester.semester_code}</p>
				<p class="thoigianhocki">
					<span class="batdau">${semester.start_date}</span> - 
					<span class="ketthuc">${semester.end_date}</span> - 
					<span class="solop">${semester.class_count}</span> lớp
				</p>
			</div>
			<div class="frame-right">
				<div class="trangthai-hocki ${statusClass}">
					<p class="trangthai">${statusText}</p>
				</div>
				<i class="fa-solid fa-pen-to-square edit edit-semester" data-semester-id="${semester.semester_id}"></i>
				<i class="fa-solid fa-trash delete-semester" data-semester-id="${semester.semester_id}" data-semester-code="${semester.semester_code}"></i>
			</div>
		</div>
	`;
}

/**
 * Gắn event listeners cho các năm học sau khi render
 */
function attachAcademicYearEventListeners() {
	// Toggle học kỳ
	document.querySelectorAll('.nam').forEach(header => {
		header.addEventListener('click', (event) => {
			// Không toggle khi click vào icon xóa
			if (event.target.closest('.delete-year')) {
				return;
			}
			
			const frameNam = header.closest('.frame-nam');
			const frameKy = frameNam.querySelector('.frame-ky');
			const icon = header.querySelector('.toggle-icon');
			
			const isOpen = frameKy.classList.toggle('show');
			
			if (icon) {
				icon.classList.toggle('fa-chevron-right', !isOpen);
				icon.classList.toggle('fa-angle-down', isOpen);
			}
		});
	});
	
	// Xóa năm học
	document.querySelectorAll('.delete-year').forEach(btn => {
		btn.addEventListener('click', (e) => {
			e.stopPropagation();
			const yearId = parseInt(btn.dataset.yearId);
			const yearCode = btn.dataset.yearCode;
			handleDeleteAcademicYear(yearId, yearCode);
		});
	});
	
	// Thêm học kỳ
	document.querySelectorAll('.btn-themhocky').forEach(btn => {
		btn.addEventListener('click', () => {
			const yearId = parseInt(btn.dataset.yearId);
			const yearCode = btn.dataset.yearCode;
			openAddSemesterModal(yearId, yearCode);
		});
	});
	
	// Sửa học kỳ
	document.querySelectorAll('.edit-semester').forEach(btn => {
		btn.addEventListener('click', (e) => {
			e.stopPropagation();
			const semesterId = parseInt(btn.dataset.semesterId);
			openEditSemesterModal(semesterId);
		});
	});
	
	// Xóa học kỳ
	document.querySelectorAll('.delete-semester').forEach(btn => {
		btn.addEventListener('click', (e) => {
			e.stopPropagation();
			const semesterId = parseInt(btn.dataset.semesterId);
			const semesterCode = btn.dataset.semesterCode;
			handleDeleteSemester(semesterId, semesterCode);
		});
	});
	
	// Hover effect cho buttons
	document.querySelectorAll('button, .btn').forEach(button => {
		button.addEventListener('mouseenter', () => button.classList.add('hover'));
		button.addEventListener('mouseleave', () => button.classList.remove('hover'));
	});
}

// ==================== UTILITY FUNCTIONS ====================

function showLoading() {
	const container = document.getElementById('academic-years-container');
	container.innerHTML = `
		<div class="loading-indicator" style="text-align: center; padding: 2rem;">
			<i class="fa-solid fa-spinner fa-spin" style="font-size: 2rem; color: #0088F0;"></i>
			<p style="margin-top: 1rem; color: #615F5F;">Đang tải dữ liệu...</p>
		</div>
	`;
}

function hideLoading() {
	// Loading will be replaced by content
}

/**
 * Hiển thị thông báo
 */
function showNotification(message, type = 'info') {
	// Remove existing notifications
	const existingNotif = document.querySelector('.notification-toast');
	if (existingNotif) {
		existingNotif.remove();
	}
	
	const notification = document.createElement('div');
	notification.className = `notification-toast notification-${type}`;
	
	const icon = type === 'success' ? 'fa-check-circle' : 
	             type === 'error' ? 'fa-exclamation-circle' : 
	             'fa-info-circle';
	
	notification.innerHTML = `
		<i class="fa-solid ${icon}"></i>
		<span>${message}</span>
	`;
	
	document.body.appendChild(notification);
	
	// Trigger animation
	setTimeout(() => notification.classList.add('show'), 10);
	
	// Auto hide after 3 seconds
	setTimeout(() => {
		notification.classList.remove('show');
		setTimeout(() => notification.remove(), 300);
	}, 3000);
}