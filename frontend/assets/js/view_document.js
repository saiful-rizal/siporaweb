function viewDocument(docId) {
    // Tampilkan modal dengan pesan loading
    const modal = document.getElementById('viewDocumentModal');
    const modalTitle = document.getElementById('viewModalTitle');
    const modalContent = document.getElementById('viewModalContent');
    const downloadButton = document.getElementById('downloadButton');

    modalTitle.textContent = 'Memuat...';
    modalContent.innerHTML = `
        <div style="text-align: center; padding: 20px;">
            <i class="bi bi-hourglass-split" style="font-size: 2rem; color: var(--primary-blue);"></i>
            <p>Memuat detail dokumen...</p>
        </div>
    `;
    downloadButton.style.display = 'none';

    // Tampilkan modal
    modal.style.display = 'block';
    setTimeout(() => {
        modal.classList.add('show');
    }, 10);

    // Ambil data dokumen dari API
    fetch(`api/get_document.php?id=${docId}`)
        .then(response => {
            if (!response.ok) {
                throw new Error('Dokumen tidak ditemukan.');
            }
            return response.json();
        })
        .then(data => {
            if (data.error) {
                throw new Error(data.error);
            }

            // Isi modal dengan data dokumen
            modalTitle.textContent = data.judul;
            modalContent.innerHTML = `
                <div class="document-view-header">
                    <div class="document-view-meta">
                        <span class="meta-item">
                            <i class="bi bi-person-circle"></i>
                            ${data.uploader_name}
                        </span>
                        <span class="meta-item">
                            <i class="bi bi-calendar3"></i>
                            ${new Date(data.tgl_unggah).toLocaleDateString('id-ID', { year: 'numeric', month: 'long', day: 'numeric' })}
                        </span>
                        <span class="meta-item">
                            <i class="bi bi-eye"></i>
                            ${data.download_count} kali dilihat
                        </span>
                    </div>
                </div>

                <div class="document-view-badges">
                    <span class="badge ${getStatusBadge(data.status_id)}">${getStatusName(data.status_id)}</span>
                    ${data.turnitin > 0 ? `<span class="badge badge-success">Turnitin: ${data.turnitin}%</span>` : ''}
                </div>

                <hr>

                <div class="document-view-section">
                    <h4><i class="bi bi-info-circle"></i> Informasi Dokumen</h4>
                    <div class="info-grid">
                        <div class="info-item">
                            <span class="info-label">Jurusan</span>
                            <span class="info-value">${data.nama_jurusan || 'Tidak ada'}</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Program Studi</span>
                            <span class="info-value">${data.nama_prodi || 'Tidak ada'}</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Tema</span>
                            <span class="info-value">${data.nama_tema || 'Tidak ada'}</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Tahun</span>
                            <span class="info-value">${data.tahun || 'Tidak ada'}</span>
                        </div>
                    </div>
                </div>

                <div class="document-view-section">
                    <h4><i class="bi bi-key"></i> Kata Kunci</h4>
                    <p>${data.kata_kunci || 'Tidak ada kata kunci.'}</p>
                </div>

                <div class="document-view-section">
                    <h4><i class="bi bi-file-text"></i> Abstrak</h4>
                    <p class="abstract-text">${data.abstrak.replace(/\n/g, '<br>') || 'Tidak ada abstrak.'}</p>
                </div>
            `;
            
            // Set link unduhan
            downloadButton.href = `download.php?id=${data.dokumen_id}`;
            downloadButton.style.display = 'inline-flex';

        })
        .catch(error => {
            // Tampilkan pesan error
            modalTitle.textContent = 'Error';
            modalContent.innerHTML = `
                <div style="text-align: center; padding: 20px; color: #dc3545;">
                    <i class="bi bi-exclamation-triangle" style="font-size: 2rem;"></i>
                    <p>${error.message}</p>
                </div>
            `;
        });
}

function closeViewModal() {
    const modal = document.getElementById('viewDocumentModal');
    modal.classList.remove('show');
    setTimeout(() => {
        modal.style.display = 'none';
    }, 300);
}

// Fungsi helper untuk badge (copy dari dashboard.js agar bisa diakses)
function getStatusBadge(status_id) {
    switch(status_id) {
        case 1: return 'badge-success';
        case 2: return 'badge-warning';
        case 3: return 'badge-info';
        default: return 'badge-secondary';
    }
}

function getStatusName(status_id) {
    switch(status_id) {
        case 1: return 'Diterbitkan';
        case 2: return 'Review';
        case 3: return 'Menunggu Publikasi';
        default: return 'Unknown';
    }
}