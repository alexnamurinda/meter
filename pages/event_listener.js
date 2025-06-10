// Utility function to hide all sections and show only the desired section
function showSection(sectionId) {
    const sections = document.querySelectorAll('.content-section');
    sections.forEach(section => section.style.display = 'none'); // Hide all sections
    document.getElementById(sectionId).style.display = 'block'; // Show the specific section
}

document.getElementById('dashboard-link').addEventListener('click', function() {
    showSection('dashboard-content1');
});

document.getElementById('usage-link').addEventListener('click', function() {
    showSection('usage-content');
});


document.getElementById('payment-link').addEventListener('click', function() {
    showSection('payment-content');
});

document.getElementById('recharge-link').addEventListener('click', function() {
    showSection('recharge-content');
});

document.getElementById('alerts-link').addEventListener('click', function() {
    showSection('alerts-content');
});

document.getElementById('support-link').addEventListener('click', function() {
    showSection('support-content');
});

document.getElementById('settings-link').addEventListener('click', function() {
    showSection('account-content');
});