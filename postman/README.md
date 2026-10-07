# 📮 Panduan Postman Collection (Tugas UKL 2)

Folder ini berisi berkas **Postman Collection** dan **Postman Environment** resmi untuk menguji seluruh endpoint API autentikasi dan manajemen pengguna pada proyek **Tugas UKL 2**.

---

## 📁 Struktur Berkas

```
postman/
├── README.md                           # Dokumentasi & panduan penggunaan Postman
├── tugas-ukl2.postman_collection.json  # Koleksi request API lengkap & terstruktur
└── tugas-ukl2.postman_environment.json # Konfigurasi variabel environment lokal
```

---

## 🗂 Struktur Folder & Logika Endpoint

Koleksi dikelompokkan ke dalam 4 folder utama sesuai logika bisnis yang ada pada `app/Http/Controllers/AuthController.php` dan `routes/api.php`:

### 1. 📂 `1. Autentikasi (Register & Login)`
Mengelola siklus pendaftaran akun baru, login multi-identitas, dan akses profil terproteksi:
- **`Register - Email & WhatsApp`** (`POST /api/auth/register`)
  Pendaftaran akun lengkap dengan nama, email, nomor telepon/WhatsApp, dan konfirmasi password.
- **`Register - Hanya Email`** (`POST /api/auth/register`)
  Pendaftaran akun jika pengguna hanya memiliki alamat email (`required_without:phone_number`).
- **`Register - Hanya Nomor WhatsApp`** (`POST /api/auth/register`)
  Pendaftaran akun jika pengguna hanya memiliki nomor WhatsApp (`required_without:email`).
- **`Login - Menggunakan Email`** (`POST /api/auth/login-email`)
  Masuk menggunakan email dan password. Token Bearer Sanctum otomatis disimpan ke environment `{{token}}`.
- **`Login - Menggunakan Username`** (`POST /api/auth/login-email`)
  Masuk menggunakan username. Sistem otomatis mendeteksi identitas berupa username atau email.
- **`Get User Profile (Protected)`** (`GET /api/user`)
  Mengakses profil pengguna aktif menggunakan header `Authorization: Bearer {{token}}`.

### 2. 📂 `2. Login via OTP WhatsApp`
Alur autentikasi tanpa kata sandi (*passwordless login*) dengan verifikasi nomor WhatsApp:
- **`1. Request OTP WhatsApp`** (`POST /api/auth/request-otp-wa`)
  Mengirim permintaan pembuatan kode OTP 6-digit dengan masa aktif 5 menit.
- **`2. Verify OTP WhatsApp`** (`POST /api/auth/verify-otp-wa`)
  Memvalidasi kode OTP. Jika valid, kode dihapus dan token Sanctum diterbitkan lalu disimpan otomatis ke `{{token}}`. Dilengkapi proteksi batas percobaan (*attempts limit* maksimal 3 kali).

### 3. 📂 `3. Pemulihan Kata Sandi (Forgot Password)`
Alur reset kata sandi mandiri melalui metode WhatsApp atau Email:
- **`1. Request Reset Password via WhatsApp`** (`POST /api/auth/forgot-password/request`)
  Meminta kode OTP reset kata sandi yang dikirimkan ke nomor WhatsApp (masa aktif 10 menit).
- **`2. Request Reset Password via Email`** (`POST /api/auth/forgot-password/request`)
  Meminta kode OTP reset kata sandi yang dikirimkan ke alamat email.
- **`3. Reset Password (Submit OTP & Password Baru)`** (`POST /api/auth/forgot-password/reset`)
  Memvalidasi kode OTP dan memperbarui kata sandi pengguna dengan password baru.

### 4. 📂 `4. Kasus Uji Validasi & Negatif (Negative Testing)`
Skenario pengujian kegagalan dan penanganan error:
- **`Register Gagal - Body Kosong (422)`** → Pengujian validasi input kosong.
- **`Register Gagal - Password Mismatch (422)`** → Pengujian konfirmasi kata sandi tidak cocok.
- **`Login Gagal - Password Salah (401)`** → Pengujian kredensial salah.
- **`Request OTP Gagal - Nomor Tidak Terdaftar (404)`** → Pengujian nomor tidak ditemukan.
- **`Verify OTP Gagal - OTP Salah (400)`** → Pengujian salah memasukkan kode OTP dan peningkatan counter percobaan.
- **`Get User Profile Gagal - Tanpa Token (401)`** → Pengujian proteksi middleware Sanctum.

---

## 🚀 Cara Import ke Postman

1. Buka aplikasi **Postman**.
2. Klik tombol **Import** (di sudut kiri atas).
3. Tarik (*drag and drop*) atau pilih kedua berkas berikut:
   - `postman/tugas-ukl2.postman_collection.json`
   - `postman/tugas-ukl2.postman_environment.json`
4. Pada dropdown Environment (sudut kanan atas Postman), pilih:
   `Tugas UKL 2 - Local Environment`.
5. Pastikan server lokal Laravel telah berjalan:
   ```bash
   php artisan serve
   ```
   *(Secara default berjalan di `http://127.0.0.1:8000`).*

---

## ⚡ Otomatisasi Environment Variable

Koleksi ini telah dilengkapi **Post-response Scripts (Tests)** otomatis:
Ketika request **Register**, **Login**, atau **Verify OTP WhatsApp** berhasil (status 200/201), token Bearer Sanctum pada respons (`data.token`) akan **langsung disimpan** ke variabel environment `{{token}}`.

Dengan demikian, request **`Get User Profile (Protected)`** dapat langsung dijalankan tanpa perlu menyalin token secara manual!

### Variabel yang Tersedia di Environment

| Variabel | Tipe | Nilai Default | Deskripsi |
| :--- | :---: | :--- | :--- |
| `base_url` | String | `http://127.0.0.1:8000/api` | Base URL API Laravel |
| `token` | Secret | *(Kosong - terisi otomatis)* | Bearer Token Sanctum aktif |
| `test_email` | String | `budi.santoso@example.com` | Email dummy untuk pengujian |
| `test_phone` | String | `081234567890` | Nomor telepon dummy untuk pengujian |
| `test_password` | Secret | `Password123!` | Kata sandi standar pengujian |
| `otp_code` | String | `123456` | Kode OTP untuk pengujian |

---

## 🔄 Contoh Skenario Alur Pengujian Lengkap

### Skenario A: Pendaftaran dan Akses Profil
1. Jalankan `Register - Email & WhatsApp`.
2. Periksa variabel `token` di Environment (otomatis terisi).
3. Jalankan `Get User Profile (Protected)` → Respons profil pengguna tampil.

### Skenario B: Login Tradisional (Email/Username)
1. Jalankan `Login - Menggunakan Email`.
2. Periksa status 200 OK dan pembaruan `token`.
3. Jalankan `Get User Profile (Protected)`.

### Skenario C: Passwordless Login WhatsApp
1. Jalankan `1. Request OTP WhatsApp`.
2. Ambil nilai kode OTP dari database (`otps` table) atau log aplikasi.
3. Masukkan kode ke variabel `otp_code` di Environment.
4. Jalankan `2. Verify OTP WhatsApp` → Token diterbitkan otomatis.
5. Jalankan `Get User Profile (Protected)`.

### Skenario D: Lupa Sandi & Reset Password
1. Jalankan `1. Request Reset Password via WhatsApp` atau via Email.
2. Ambil kode OTP dari tabel `otps`.
3. Jalankan `3. Reset Password (Submit OTP & Password Baru)` dengan kata sandi baru.
4. Uji login kembali menggunakan password baru melalui `Login - Menggunakan Email`.
