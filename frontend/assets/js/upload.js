document.getElementById('mobileMenuBtn').addEventListener('click', function() {
  document.getElementById('navLinks').classList.toggle('active');
});

document.getElementById('userAvatarContainer').addEventListener('click', function(e) {
  e.stopPropagation();
  document.getElementById('userDropdown').classList.toggle('active');
  document.getElementById('notificationDropdown').classList.remove('active');
});

document.getElementById('notificationIcon').addEventListener('click', function(e) {
  e.stopPropagation();
  document.getElementById('notificationDropdown').classList.toggle('active');
  document.getElementById('userDropdown').classList.remove('active');
});

document.addEventListener('click', function() {
  document.getElementById('userDropdown').classList.remove('active');
  document.getElementById('notificationDropdown').classList.remove('active');
});

document.getElementById('userDropdown').addEventListener('click', function(e) {
  e.stopPropagation();
});

document.getElementById('notificationDropdown').addEventListener('click', function(e) {
  e.stopPropagation();
});

// File upload functionality
const fileUploadArea = document.getElementById('fileUploadArea');
const fileInput = document.getElementById('fileInput');
const fileInfo = document.getElementById('fileInfo');
const fileName = document.getElementById('fileName');
const fileSize = document.getElementById('fileSize');
const fileType = document.getElementById('fileType');

fileUploadArea.addEventListener('click', function() {
  fileInput.click();
});

fileUploadArea.addEventListener('dragover', function(e) {
  e.preventDefault();
  this.classList.add('dragover');
});

fileUploadArea.addEventListener('dragleave', function() {
  this.classList.remove('dragover');
});

fileUploadArea.addEventListener('drop', function(e) {
  e.preventDefault();
  this.classList.remove('dragover');
  
  const files = e.dataTransfer.files;
  if (files.length > 0) {
    fileInput.files = files;
    displayFileInfo(files[0]);
  }
});

fileInput.addEventListener('change', function() {
  if (this.files.length > 0) {
    displayFileInfo(this.files[0]);
  }
});

function displayFileInfo(file) {
  fileName.textContent = file.name;
  fileSize.textContent = formatFileSize(file.size);
  fileType.textContent = file.type || 'Unknown';
  fileInfo.classList.add('show');
}

function formatFileSize(bytes) {
  if (bytes === 0) return '0 Bytes';
  const k = 1024;
  const sizes = ['Bytes', 'KB', 'MB', 'GB'];
  const i = Math.floor(Math.log(bytes) / Math.log(k));
  return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}

// Form validation before submit
document.getElementById('uploadForm').addEventListener('submit', function(e) {
  const turnitinInput = document.querySelector('input[name="turnitin"]');
  const turnitinValue = turnitinInput.value;
  
  if (turnitinValue !== '') {
    // Remove % if user entered it
    const cleanValue = turnitinValue.replace('%', '');
    
    // Check if it's a valid number
    if (isNaN(cleanValue)) {
      e.preventDefault();
      alert('Skor Turnitin harus berupa angka');
      turnitinInput.focus();
      return;
    }
    
    // Check range
    const numValue = parseFloat(cleanValue);
    if (numValue < 0 || numValue > 100) {
      e.preventDefault();
      alert('Skor Turnitin harus antara 0-100');
      turnitinInput.focus();
      return;
    }
    
    // Update the value to clean number
    turnitinInput.value = cleanValue;
  }
});

function resetForm() {
  document.getElementById('uploadForm').reset();
  fileInfo.classList.remove('show');
}

function openProfileModal() {
  const modal = document.getElementById('profileModal');
  modal.style.display = 'block';
  setTimeout(() => {
    modal.classList.add('show');
  }, 10);
  document.getElementById('userDropdown').classList.remove('active');
}

function openHelpModal() {
  const modal = document.getElementById('helpModal');
  modal.style.display = 'block';
  setTimeout(() => {
    modal.classList.add('show');
  }, 10);
  document.getElementById('userDropdown').classList.remove('active');
}

function closeModal(modalId) {
  const modal = document.getElementById(modalId);
  modal.classList.remove('show');
  setTimeout(() => {
    modal.style.display = 'none';
  }, 300);
}

function markAllAsRead() {
  document.querySelectorAll('.notification-item').forEach(item => {
    item.classList.remove('unread');
  });
  
  const badge = document.querySelector('.notification-badge');
  if (badge) {
    badge.style.display = 'none';
  }
  
  return false;
}
// Tambahkan ke file JavaScript Anda
document.querySelectorAll('.user-info, .notification-icon').forEach(trigger => {
  trigger.addEventListener('click', function() {
    const dropdown = this.querySelector('.user-dropdown, .notification-dropdown');
    if (dropdown) {
      const rect = this.getBoundingClientRect();
      const dropdownHeight = dropdown.offsetHeight;
      const spaceBelow = window.innerHeight - rect.bottom;
      
      if (spaceBelow < dropdownHeight) {
        // Jika tidak ada cukup ruang di bawah, tampilkan dropdown ke atas
        dropdown.style.top = 'auto';
        dropdown.style.bottom = '100%';
        dropdown.style.marginBottom = '10px';
        dropdown.style.marginTop = '0';
      } else {
        // Jika ada cukup ruang, tampilkan dropdown ke bawah
        dropdown.style.top = '100%';
        dropdown.style.bottom = 'auto';
        dropdown.style.marginTop = '10px';
        dropdown.style.marginBottom = '0';
      }
    }
  });
});