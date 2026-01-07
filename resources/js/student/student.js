// Load header
fetch('../partials/header.html')
    .then(response => response.text())
    .then(data => {
        document.querySelector('.header').innerHTML = data;
    })
    .catch(error => console.error('Error loading header:', error));

// Load menu student
fetch('./menu_student.html')
    .then(response => response.text())
    .then(data => {
        document.querySelector('.menu_student').innerHTML = data;
    })
    .catch(error => console.error('Error loading student menu:', error));
