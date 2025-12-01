<div class="my-documents-container">
  <div class="upload-form-card">
    <div class="upload-form-header">
      <i class="bi bi-folder2-open"></i>
      <h4>My Documents</h4>
    </div>

    <!-- Tab Navigasi -->
    <ul class="nav nav-tabs" id="myDocTabs" role="tablist">
      <li class="nav-item" role="presentation">
        <button class="nav-link active" id="uploaded-tab" data-bs-toggle="tab" data-bs-target="#uploaded" type="button" role="tab" aria-controls="uploaded" aria-selected="true">
          <i class="bi bi-cloud-upload"></i> Diunggah (<?php echo count($my_documents); ?>)
        </button>
      </li>
      <li class="nav-item" role="presentation">
        <button class="nav-link" id="downloaded-tab" data-bs-toggle="tab" data-bs-target="#downloaded" type="button" role="tab" aria-controls="downloaded" aria-selected="false">
          <i class="bi bi-download"></i> Diunduh (<?php echo count($my_downloads); ?>)
        </button>
      </li>
    </ul>

    <!-- Konten Tab -->
    <div class="tab-content" id="myDocTabContent">
      <!-- Tab Dokumen Diunggah -->
      <div class="tab-pane fade show active" id="uploaded" role="tabpanel" aria-labelledby="uploaded-tab">
        <?php if (empty($my_documents)): ?>
          <div class="empty-state">
            <div class="empty-state-icon"><i class="bi bi-inbox"></i></div>
            <h5 class="empty-state-title">Belum Ada Dokumen</h5>
            <p class="empty-state-description">Anda belum mengunggah dokumen apa pun. <a href="upload.php">Unggah dokumen pertama Anda</a> sekarang.</p>
          </div>
        <?php else: ?>
          <div class="document-grid">
            <?php foreach ($my_documents as $doc): ?>
              <div class="document-card">
                <div class="document-thumbnail">
                  <i class="bi bi-file-earmark-text document-thumbnail-icon"></i>
                  <div class="document-thumbnail-text">
                    <?php 
                      $file_extension = pathinfo($doc['file_path'], PATHINFO_EXTENSION);
                      echo strtoupper($file_extension ?: 'PDF');
                    ?>
                  </div>
                </div>
                <div class="document-content">
                  <div class="document-header">
                    <h6 class="document-title"><?php echo htmlspecialchars($doc['judul']); ?></h6>
                    <div class="document-badges">
                      <span class="badge <?php echo getStatusBadge($doc['status_id']); ?>"><?php echo getStatusName($doc['status_id']); ?></span>
                      <?php if (!empty($doc['turnitin']) && $doc['turnitin'] > 0): ?>
                        <span class="badge badge-success">T: <?php echo $doc['turnitin']; ?>%</span>
                      <?php endif; ?>
                    </div>
                  </div>
                  <div class="document-description"><?php echo htmlspecialchars(substr($doc['abstrak'], 0, 150)) . '...'; ?></div>
                  <div class="document-meta">
                    <span class="document-date"><i class="bi bi-calendar3"></i> <?php echo date('d M y', strtotime($doc['tgl_unggah'])); ?></span>
                  </div>
                  <div class="document-footer">
                    <div class="document-actions">
                      <button class="btn-action btn-view" onclick="viewDocument(<?php echo $doc['dokumen_id']; ?>)" title="Lihat"><i class="bi bi-eye"></i></button>
                      <button class="btn-action btn-download" onclick="downloadDocument(<?php echo $doc['dokumen_id']; ?>)" title="Unduh"><i class="bi bi-download"></i></button>
                    </div>
                  </div>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>

      <!-- Tab Dokumen Diunduh -->
      <div class="tab-pane fade" id="downloaded" role="tabpanel" aria-labelledby="downloaded-tab">
        <?php if (empty($my_downloads)): ?>
          <div class="empty-state">
            <div class="empty-state-icon"><i class="bi bi-download"></i></div>
            <h5 class="empty-state-title">Belum Ada Unduhan</h5>
            <p class="empty-state-description">Anda belum mengunduh dokumen apa pun. Jelajahi <a href="browser.php">repository</a> untuk menemukan dokumen yang menarik.</p>
          </div>
        <?php else: ?>
          <div class="document-grid">
            <?php foreach ($my_downloads as $doc): ?>
              <div class="document-card">
                <div class="document-thumbnail">
                  <i class="bi bi-file-earmark-text document-thumbnail-icon"></i>
                  <div class="document-thumbnail-text">
                    <?php 
                      $file_extension = pathinfo($doc['file_path'], PATHINFO_EXTENSION);
                      echo strtoupper($file_extension ?: 'PDF');
                    ?>
                  </div>
                </div>
                <div class="document-content">
                  <div class="document-header">
                    <h6 class="document-title"><?php echo htmlspecialchars($doc['judul']); ?></h6>
                    <div class="document-badges">
                      <span class="badge <?php echo getStatusBadge($doc['status_id']); ?>"><?php echo getStatusName($doc['status_id']); ?></span>
                    </div>
                  </div>
                  <div class="document-description"><?php echo htmlspecialchars(substr($doc['abstrak'], 0, 150)) . '...'; ?></div>
                  <div class="document-meta">
                    <span class="document-uploader"><i class="bi bi-person-circle"></i> <?php echo htmlspecialchars($doc['uploader_name']); ?></span>
                    <span class="document-date"><i class="bi bi-calendar3"></i> <?php echo date('d M y', strtotime($doc['tgl_unduh'])); ?></span>
                  </div>
                  <div class="document-footer">
                    <div class="document-actions">
                      <button class="btn-action btn-view" onclick="viewDocument(<?php echo $doc['dokumen_id']; ?>)" title="Lihat"><i class="bi bi-eye"></i></button>
                      <button class="btn-action btn-download" onclick="downloadDocument(<?php echo $doc['dokumen_id']; ?>)" title="Unduh"><i class="bi bi-download"></i></button>
                    </div>
                  </div>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>