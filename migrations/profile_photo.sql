-- Migration untuk Fitur Profile Photo
-- Tidak perlu perubahan database, hanya setup direktori file

-- Setup File Storage:
-- 1. Buat direktori: frontend/uploads/profile/
-- 2. Set permission: chmod 755 frontend/uploads/profile/
-- 3. Foto akan disimpan dengan nama: profile_USER_ID.jpg
--    Contoh: profile_1.jpg untuk user dengan id_user = 1

-- Profile Photo Storage Structure:
-- frontend/
--   uploads/
--     profile/
--       profile_1.jpg
--       profile_2.jpg
--       profile_3.jpg
--       ... (satu foto per user)

-- Notes:
-- - File lama otomatis dihapus saat upload foto baru
-- - Hanya menyimpan foto terbaru per user
-- - Foto di-compress dengan kualitas 95%
