document.addEventListener('DOMContentLoaded', () => {
	// Manage modal visibility for add/edit forms.
	const overlay = document.createElement('div');
	overlay.className = 'modal-overlay';
	document.body.appendChild(overlay);

	const yearModal = document.querySelector('.frame-noi.themnamhoc');
	const addTermModal = document.querySelector('.frame-noi.themkihoc');
	const editTermModal = document.querySelector('.frame-noi.editkyhoc');

	const addYearBtn = document.querySelector('#add-namhoc');
	const addTermBtns = document.querySelectorAll('#themhocky');
	const editTermBtns = document.querySelectorAll('.fa-pen-to-square.edit');

	const hideAllModals = () => {
		document.querySelectorAll('.frame-noi.active').forEach(modal => {
			modal.classList.remove('active');
		});
		overlay.classList.remove('show');
	};

	const showModal = (modal) => {
		if (!modal) return;
		hideAllModals();
		modal.classList.add('active');
		overlay.classList.add('show');
	};

	overlay.addEventListener('click', hideAllModals);

	if (addYearBtn && yearModal) {
		addYearBtn.addEventListener('click', () => showModal(yearModal));
	}

	addTermBtns.forEach(btn => {
		btn.addEventListener('click', () => showModal(addTermModal));
	});

	editTermBtns.forEach(btn => {
		btn.addEventListener('click', () => showModal(editTermModal));
	});

	const registerCloseTriggers = () => {
		document.querySelectorAll('.frame-noi .fa-x, .frame-noi .btn.huy').forEach(btn => {
			btn.addEventListener('click', hideAllModals);
		});
	};

	registerCloseTriggers();

	// Toggle term lists inside each academic year.
	const yearRows = document.querySelectorAll('.frame-nam');
	yearRows.forEach(row => {
		const header = row.querySelector('.nam');
		const body = row.querySelector('.frame-ky');
		const icon = header?.querySelector('.fa-chevron-right, .fa-angle-down');
		if (!header || !body) return;

		body.classList.remove('show');

		header.addEventListener('click', (event) => {
			const clickedIcon = event.target.closest('.edit, .fa-trash');
			if (clickedIcon) return; // Do not toggle when action icons are clicked.

			const isOpen = body.classList.toggle('show');
			if (icon) {
				icon.classList.toggle('fa-chevron-right', !isOpen);
				icon.classList.toggle('fa-angle-down', isOpen);
			}
		});
	});

	// Add hover effect for buttons
	const allButtons = document.querySelectorAll('button, .btn');
	allButtons.forEach(button => {
		button.addEventListener('mouseenter', () => {
			button.classList.add('hover');
		});
		button.addEventListener('mouseleave', () => {
			button.classList.remove('hover');
		});
	});

	const frameKy = document.getElementById('frame-ky');
	if (frameKy) {
		frameKy.classList.add('show');
	}
});
