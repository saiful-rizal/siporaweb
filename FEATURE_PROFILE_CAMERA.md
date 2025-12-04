# Panduan Fitur Profil dan Kamera

## Deskripsi
Fitur ini memungkinkan pengguna untuk:
1. **Mengubah Foto Profil** - Upload foto dari file atau ambil langsung dari kamera laptop
2. **Akses Kamera** - Menggunakan webcam untuk mengambil foto profil secara real-time

## Cara Menggunakan

### 1. Mengakses Modal Ubah Profil
- Klik pada avatar profil di navbar atau dropdown user
- Pilih "Profil Saya"
- Klik tombol kamera (ikon kamera) di sudut kanan bawah foto profil

### 2. Mengambil Foto dari Kamera
- Klik tombol "Ambil Foto dari Kamera"
- Izinkan akses ke kamera laptop ketika browser meminta
- Klik "Ambil Foto" untuk mengabadikan gambar
- Klik "Simpan Foto" untuk mengunggah

### 3. Mengunggah Foto dari File
- Klik tombol "Upload Foto"
- Pilih file gambar dari komputer (JPG, PNG, GIF, dll)
- Pratinjau akan muncul secara otomatis
- Klik "Simpan Foto" untuk mengunggah

### 4. Batalkan dan Ulang
- Klik "Batal" untuk keluar dari mode kamera
- Klik "Ulang" untuk membatalkan pilihan foto dan memilih ulang

## Kebutuhan Teknis

### Setup File System
Buat direktori untuk penyimpanan foto:
```bash
mkdir -p frontend/uploads/profile
chmod 755 frontend/uploads/profile
```

Atau jalankan setup script:
```
http://localhost/siporaweb/migrate_profile_photo.php
```

### Struktur File
- **Frontend**: `frontend/upload_profile_photo.php` - Handler untuk upload foto
- **Storage**: `frontend/uploads/profile/` - Direktori penyimpanan foto
  - Nama file: `profile_USER_ID.jpg`
  - Contoh: `profile_1.jpg` untuk user dengan id 1

### File yang Diubah
1. `frontend/components/navbar.php`
   - Modal untuk ubah profil foto
   - JavaScript untuk menangani kamera dan upload
   - CSS styling untuk komponen baru

2. `frontend/includes/functions.php`
   - Update fungsi `hasProfilePhoto()`
   - Update fungsi `getProfilePhotoUrl()`

## Fitur Keamanan

### Validasi
- Pemeriksaan tipe file (hanya gambar)
- Batas ukuran file maksimal 5MB
- Validasi base64 data
- Authentikasi pengguna

### Penyimpanan
- Nama file konsisten per user: `profile_USER_ID.jpg`
- File lama otomatis dihapus saat upload baru
- Hanya menyimpan foto terbaru per user
- Penyimpanan terpisah di direktori frontend

## Dukungan Browser

### Kamera
Fitur kamera memerlukan browser modern yang mendukung:
- `getUserMedia()` API
- HTML5 Video API
- Canvas API

**Browser yang didukung:**
- Chrome/Edge 53+
- Firefox 25+
- Safari 11+
- Opera 40+

**Catatan**: Https atau localhost diperlukan untuk akses kamera

### Upload File
Semua browser modern yang mendukung HTML5 File API

## Troubleshooting

### Kamera Tidak Berfungsi
1. Pastikan browser memiliki akses ke kamera
2. Cek settings privasi browser
3. Gunakan localhost atau https
4. Coba restart browser

### Upload Gagal
1. Cek ukuran file (maksimal 5MB)
2. Pastikan format file adalah gambar
3. Cek permission folder `frontend/uploads/profile/`
   - Jalankan: `chmod 755 frontend/uploads/profile/`
4. Pastikan direktori sudah dibuat

### Foto Profil Tidak Tampil
1. Clear browser cache (Ctrl+Shift+Delete)
2. Refresh halaman (Ctrl+F5)
3. Cek file ada di `frontend/uploads/profile/profile_USER_ID.jpg`
4. Cek permission file (chmod 644 frontend/uploads/profile/*.jpg)

## API Reference

### POST /frontend/upload_profile_photo.php

**Request Body:**
```json
{
  "photo": "data:image/jpeg;base64,..."
}
```

**Response Success:**
```json
{
  "success": true,
  "message": "Foto profil berhasil diubah.",
  "filename": "profile_1.jpg"
}
```

**Response Error:**
```json
{
  "success": false,
  "message": "Deskripsi error"
}
```

## Performa

- Foto dikompresi dengan kualitas 95% untuk menghemat storage
- Automatic cleanup file lama menghemat ruang disk
- Caching dengan timestamp query string untuk fresh content
- Simple file-based storage tanpa database query

## Storage Schema

```
frontend/
  uploads/
    profile/
      profile_1.jpg      <- User ID 1
      profile_2.jpg      <- User ID 2
      profile_3.jpg      <- User ID 3
      ...
      profile_N.jpg      <- User ID N
```

Setiap file: `profile_USER_ID.jpg`
- Hanya menyimpan 1 foto per user
- File lama dihapus otomatis saat ada foto baru

## Future Improvements

Fitur yang bisa ditambahkan:
- [ ] Crop/edit foto sebelum upload
- [ ] Filter foto dari kamera
- [ ] Multiple photo formats (PNG, WebP)
- [ ] Photo history/archive
- [ ] Batch image optimization

