import './bootstrap';

window.openDeleteModal = function(action, name) {
    document.getElementById('delete-form').action = action;
    document.getElementById('delete-name').textContent = name;
    document.getElementById('delete-modal').classList.add('open');
}

window.closeDeleteModal = function() {
    document.getElementById('delete-modal').classList.remove('open');
}

window.openLogoutModal = function() {
    document.getElementById('logoutModal').classList.add('open');
}

window.closeLogoutModal = function() {
    document.getElementById('logoutModal').classList.remove('open');
}