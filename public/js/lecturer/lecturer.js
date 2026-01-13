// Load header
fetch('./../partials/header.html')
    .then(response => response.text())
    .then(data => {
        document.querySelector('.header').innerHTML = data;
    })
    .catch(error => console.error('Error loading header:', error));

// Load menu
fetch('./menu_lecturer.html')
    .then(response => response.text())
    .then(data => {
        document.querySelector('.menu_lecturer').innerHTML = data;
    })
    .catch(error => console.error('Error loading menu:', error));
