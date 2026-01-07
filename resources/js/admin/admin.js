// Load header
fetch('../partials/header_admin.html')
    .then(response => response.text())
    .then(data => {
        document.querySelector('.header').innerHTML = data;
    })
    .catch(error => console.error('Error loading header:', error));

// Load menu admin
fetch('./menu_admin.html')
    .then(response => response.text())
    .then(data => {
        document.querySelector('.menu_admin').innerHTML = data;
    })
    .catch(error => console.error('Error loading admin menu:', error));
