# 🎥 Instalasi Fitur Profil & Kamera

## ✨ Fitur Baru
- ✅ Ganti foto profil dengan upload file
- ✅ Ambil foto profil langsung dari kamera laptop
- ✅ Pratinjau foto sebelum disimpan
- ✅ Automatic cleanup file lama
- ✅ Responsive design
- ✅ Validasi keamanan
- ✅ File-based storage (tidak perlu database)

## 📋 Langkah Instalasi

### 1️⃣ Buat Direktori Upload
```bash
mkdir -p frontend/uploads/profile
chmod 755 frontend/uploads/profile
```

### 2️⃣ Verifikasi Instalasi
1. Login ke aplikasi
2. Klik avatar/profil di navbar
3. Klik tombol kamera di foto profil
4. Coba ambil foto atau upload file
5. Foto seharusnya tersimpan dan muncul di profil

**Alternatif: Gunakan Setup Script**
- Buka browser: `http://localhost/siporaweb/migrate_profile_photo.php`
- Script akan membuat direktori dan set permission otomatis

## 🔧 File-File yang Diubah

### Frontend
- `frontend/components/navbar.php` (✏️ Updated)
  - Added modal for profile photo change
  - Added camera and upload functionality
  - Added preview system

- `frontend/includes/functions.php` (✏️ Updated)
  - Updated `hasProfilePhoto()` untuk baca dari file
  - Updated `getProfilePhotoUrl()` untuk baca dari file

### Baru Dibuat
- `frontend/upload_profile_photo.php` (✨ New)
  - API endpoint untuk upload foto
  - Base64 decode dan validasi
  - File system storage
  - Auto cleanup old photos

- `migrate_profile_photo.php` (✨ New)
  - Setup script untuk membuat direktori
  - Verifikasi permission file

- `migrations/profile_photo.sql` (✨ New)
  - Dokumentasi setup file system

- `FEATURE_PROFILE_CAMERA.md` (✨ New)
  - Dokumentasi lengkap fitur

## 🐛 Troubleshooting

### Error: "Gagal mengakses kamera"
- ✅ Gunakan HTTPS atau localhost
- ✅ Izinkan akses kamera di browser settings
- ✅ Cek apakah device punya kamera
- ✅ Restart browser dan coba lagi

### Error: "Gagal mengunggah foto"
- ✅ Cek direktori `frontend/uploads/profile/` ada dan writable
- ✅ Cek ukuran file < 5MB
- ✅ Pastikan format gambar valid (JPG, PNG, GIF, dst)
- ✅ Jalankan permission: `chmod 755 frontend/uploads/profile/`

### Direktori tidak terbuat
- ✅ Jalankan: `php migrate_profile_photo.php`
- ✅ Atau manual: `mkdir -p frontend/uploads/profile`

### Foto tidak tampil setelah upload
- ✅ Clear cache browser (Ctrl+Shift+Del)
- ✅ Refresh halaman (Ctrl+F5)
- ✅ Cek file ada di `frontend/uploads/profile/profile_USER_ID.jpg`

## 📱 Browser Support

| Browser | Camera | Upload | Tested |
|---------|--------|--------|--------|
| Chrome  | ✅     | ✅     | ✅     |
| Firefox | ✅     | ✅     | ✅     |
| Safari  | ✅     | ✅     | ✅     |
| Edge    | ✅     | ✅     | ✅     |
| IE 11   | ❌     | ✅     | N/A    |

## 🔒 Keamanan

- Base64 validation untuk semua upload foto
- Maximum file size 5MB enforcement
- File type validation (image only)
- User authentication required
- Session-based access control
- XSS protection dengan htmlspecialchars

## ⚙️ Configuration

Jika perlu mengubah setting, edit file `frontend/upload_profile_photo.php`:

```php
// Upload directory
$upload_dir = __DIR__ . '/uploads/profile/';

// Filename pattern
$filename = 'profile_' . $user_id . '.jpg';

// Photo quality (0-1)
$photo_data = canvas.toDataURL('image/jpeg', 0.95);
```

## 📊 File Storage Schema

```
frontend/
  uploads/
    profile/
      profile_1.jpg      <- User ID 1
      profile_2.jpg      <- User ID 2
      profile_3.jpg      <- User ID 3
```

- Satu foto per user
- Nama file: `profile_USER_ID.jpg`
- Foto baru otomatis replace foto lama
- Tidak perlu database tracking

## 🚀 Performance

- Photo compression: 95% quality for smaller filesize
- File-based storage: No database queries needed
- Auto cleanup: Only 1 file per user
- Lazy loading: Photos loaded on demand
- Cache busting: Timestamp query string

## 📝 Changelog

### Version 1.0 (Dec 2025)
- ✨ Initial release
- ✨ Camera support
- ✨ File upload support
- ✨ Photo preview
- ✨ File-based storage (no database)
- ✨ Auto cleanup old photos

## 💡 Tips

1. **Untuk Kamera Terbaik**
   - Gunakan cahaya alami
   - Posisikan kamera setara dengan wajah
   - Jarak optimal 20-30 cm

2. **Untuk Upload File**
   - Gunakan format JPG untuk file size lebih kecil
   - Crop foto sesuai kebutuhan sebelum upload
   - Pastikan wajah jelas dan terlihat

3. **Performance**
   - Photo secara otomatis di-compress
   - Storage minimal (1 file per user)
   - Loading cepat dari file system

## ❓ FAQ

**Q: Berapa ukuran maksimal foto?**
A: 5MB

**Q: Format foto apa saja yang didukung?**
A: JPG, PNG, GIF, WebP, dan format gambar lainnya

**Q: Bisa upload multiple photos?**
A: Tidak, hanya 1 foto profil per user (yang baru menimpa yang lama)

**Q: Apakah perlu database?**
A: Tidak, semua file disimpan di `frontend/uploads/profile/`

**Q: Bisa di-access langsung dari browser?**
A: Ya, foto dapat diakses di: `frontend/uploads/profile/profile_USER_ID.jpg`

**Q: Apakah bisa akses dari mobile?**
A: Upload: ✅ Bisa, Kamera: Tergantung browser (Chrome mobile ✅, Safari mobile ⚠️)

## 📞 Support

Jika ada masalah atau pertanyaan, silakan:
1. Cek documentation di `FEATURE_PROFILE_CAMERA.md`
2. Cek error message di browser console (F12)
3. Cek direktori permissions

