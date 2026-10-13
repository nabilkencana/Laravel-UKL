# 🚀 Layanan Autentikasi & Manajemen Pengguna (Tugas UKL 2)

Aplikasi backend API berbasis **Laravel 12** dan **PHP 8.4** yang menyediakan sistem autentikasi modern, fleksibel, dan aman. Mendukung multi-identitas login (Email dan Username), pendaftaran nomor WhatsApp/telepon, sistem proteksi verifikasi OTP (One-Time Password) dengan pembatasan kedaluwarsa serta percobaan (*brute-force prevention*), manajemen sesi database, dan API Token berbasis **Laravel Sanctum**.

---

## 📑 Daftar Isi

- [Arsitektur & Tech Stack](#-arsitektur--tech-stack)
- [Desain Database & Entity Relationship Diagram (ERD)](#-desain-database--entity-relationship-diagram-erd)
  - [Diagram ERD (Mermaid)](#diagram-erd-mermaid)
  - [Kamus Data (Data Dictionary)](#kamus-data-data-dictionary)
- [Diagram Alur Sistem (Sequence Diagram)](#-diagram-alur-sistem-sequence-diagram)
  - [1. Alur Registrasi & Login (Email/Username)](#1-alur-registrasi--login-emailusername)
  - [2. Alur Autentikasi OTP WhatsApp](#2-alur-autentikasi-otp-whatsapp)
- [Flowchart Logika Setiap Endpoint (Mermaid)](#-flowchart-logika-setiap-endpoint-mermaid)
  - [1. POST /api/auth/register (Registrasi Akun)](#1-post-apiauthregister-registrasi-akun)
  - [2. POST /api/auth/login-email (Login Email / Username)](#2-post-apiauthlogin-email-login-email--username)
  - [3. POST /api/auth/request-otp-wa (Permintaan OTP WhatsApp)](#3-post-apiauthrequest-otp-wa-permintaan-otp-whatsapp)
  - [4. POST /api/auth/verify-otp-wa (Verifikasi OTP WhatsApp)](#4-post-apiauthverify-otp-wa-verifikasi-otp-whatsapp)
  - [5. POST /api/auth/forgot-password/request (Permintaan Lupa Password)](#5-post-apiauthforgot-passwordrequest-permintaan-lupa-password)
  - [6. POST /api/auth/forgot-password/reset (Reset Password Baru)](#6-post-apiauthforgot-passwordreset-reset-password-baru)
  - [7. GET /api/user (Profil Pengguna Terautentikasi)](#7-get-apiuser-profil-pengguna-terautentikasi)
- [Dokumentasi API Endpoint](#-dokumentasi-api-endpoint)
- [Koleksi Postman (Postman Collection)](#-koleksi-postman-postman-collection)
- [Panduan Instalasi & Menjalankan Proyek](#-panduan-instalasi--menjalankan-proyek)
- [Pengujian (Testing)](#-pengujian-testing)

---

## 🛠 Arsitektur & Tech Stack

| Komponen | Teknologi | Keterangan |
| :--- | :--- | :--- |
| **Bahasa Pemrograman** | PHP 8.4 | Fitur modern PHP (Constructor promotion, typed properties, match expressions) |
| **Framework Backend** | Laravel Framework 12 (v13.x) | Arsitektur MVC & RESTful API |
| **Autentikasi Token** | Laravel Sanctum 4.x | Bearer Token API polimorfik |
| **Database Engine** | SQLite (Default Dev) / MySQL / PostgreSQL | Relational Database Management System |
| **Session & Cache** | Database Driver | Manajemen sesi HTTP & cache terpusat |
| **Pengujian (Testing)** | Pest PHP 5.x & PHPUnit 13.x | Framework testing modern & ekspresif |

---

## 🗄 Desain Database & Entity Relationship Diagram (ERD)

Desain basis data dirancang secara terstandarisasi untuk mendukung fleksibilitas identitas pengguna (Email, Username, Nomor WhatsApp) serta keamanan token sesi dan verifikasi OTP.

### Diagram ERD (Mermaid)

```mermaid
erDiagram
    %% Entitas Utama dan Relasi
    USERS ||--o{ PERSONAL_ACCESS_TOKENS : "memiliki token API (polymorphic)"
    USERS ||--o{ SESSIONS : "memiliki riwayat sesi web"
    USERS ||--o{ OTPS : "diasosiasikan via identifier (telepon/email)"
    USERS ||--o{ PASSWORD_RESET_TOKENS : "memiliki permintaan reset via email"

    %% Relasi Tabel Sistem Pendukung
    JOB_BATCHES ||--o{ JOBS : "mengelompokkan eksekusi"

    %% Definisi Entitas USERS
    USERS {
        bigint id PK "Primary Key (Auto Increment)"
        varchar name "Nama Lengkap Pengguna"
        varchar email UK "Email unik pengguna (Nullable)"
        varchar username UK "Username unik pengguna (Nullable)"
        varchar phone_number UK "Nomor WhatsApp unik (Nullable)"
        varchar password "Hash kata sandi (Bcrypt/Argon2)"
        boolean is_verified "Status verifikasi akun (Default: false)"
        timestamp created_at "Waktu pembuatan record"
        timestamp updated_at "Waktu perubahan record"
    }

    %% Definisi Entitas PERSONAL_ACCESS_TOKENS (Sanctum)
    PERSONAL_ACCESS_TOKENS {
        bigint id PK "Primary Key (Auto Increment)"
        varchar tokenable_type "Model tujuan (App\\Models\\User)"
        bigint tokenable_id FK "ID referensi entitas pengguna"
        text name "Nama Token (misal: auth_token)"
        varchar token UK "Hash SHA-256 token rahasia (64 char)"
        text abilities "Hak akses / scope token (Nullable)"
        timestamp last_used_at "Waktu terakhir token digunakan"
        timestamp expires_at "Waktu kedaluwarsa token (Indexed)"
        timestamp created_at "Waktu pembuatan token"
        timestamp updated_at "Waktu pembaruan token"
    }

    %% Definisi Entitas OTPS
    OTPS {
        bigint id PK "Primary Key (Auto Increment)"
        varchar identifier "No Telepon atau Email penerima"
        varchar otp_code "Kode rahasia verifikasi (4-6 digit)"
        enum type "Tipe: login_whatsapp atau forgot_password"
        integer attempts "Jumlah percobaan gagal (Default: 0)"
        timestamp expires_at "Batas waktu berlaku kode OTP"
        timestamp created_at "Waktu pembuatan OTP"
        timestamp updated_at "Waktu pembaruan OTP"
    }

    %% Definisi Entitas PASSWORD_RESET_TOKENS
    PASSWORD_RESET_TOKENS {
        varchar email PK "Email tujuan reset kata sandi"
        varchar token "Hash token reset kata sandi"
        timestamp created_at "Waktu token di-generate"
    }

    %% Definisi Entitas SESSIONS
    SESSIONS {
        varchar id PK "ID unik sesi (Primary Key)"
        bigint user_id FK "Referensi ID pengguna (Nullable)"
        varchar ip_address "Alamat IP klien (IPv4 / IPv6, max 45)"
        text user_agent "Informasi browser dan perangkat klien"
        longtext payload "Data terenkripsi / ter-serialize sesi"
        integer last_activity "Unix timestamp aktivitas terakhir"
    }

    %% Definisi Entitas JOBS (Queue Worker)
    JOBS {
        bigint id PK "Primary Key (Auto Increment)"
        varchar queue "Nama antrean antrian kerja"
        longtext payload "Payload serialisasi tugas (Job class & data)"
        tinyint attempts "Jumlah percobaan eksekusi"
        integer reserved_at "Waktu tugas dicadangkan worker"
        integer available_at "Waktu tugas siap dijalankan"
        integer created_at "Waktu tugas masuk ke antrean"
    }

    %% Definisi Entitas JOB_BATCHES
    JOB_BATCHES {
        varchar id PK "UUID unik kumpulan antrean"
        varchar name "Nama pengelompokan batch antrean"
        integer total_jobs "Total jumlah job dalam batch"
        integer pending_jobs "Sisa job yang belum diproses"
        integer failed_jobs "Jumlah job yang mengalami kegagalan"
        longtext failed_job_ids "Array ID job yang gagal dieksekusi"
        mediumtext options "Konfigurasi callback batch (serialized)"
        integer cancelled_at "Waktu pembatalan batch"
        integer created_at "Waktu pembuatan batch"
        integer finished_at "Waktu penyelesaian batch"
    }

    %% Definisi Entitas FAILED_JOBS
    FAILED_JOBS {
        bigint id PK "Primary Key (Auto Increment)"
        varchar uuid UK "UUID unik rekaman kegagalan"
        text connection "Koneksi database/queue yang digunakan"
        text queue "Nama antrean yang gagal"
        longtext payload "Data payload job yang gagal"
        longtext exception "Trace pesan error atau exception"
        timestamp failed_at "Waktu kegagalan terjadi"
    }

    %% Definisi Entitas CACHE
    CACHE {
        varchar key PK "Kunci data cache unik"
        mediumtext value "Nilai data yang disimpan"
        integer expiration "Waktu kedaluwarsa (Unix timestamp)"
    }
```

---

### Kamus Data (Data Dictionary)

Berikut adalah rincian struktur kolom, tipe data, serta aturan kendala (*constraints*) untuk setiap tabel pada basis data:

#### 1. Tabel `users`
Tabel utama untuk menyimpan kredensial dan informasi profil pengguna.

| Kolom | Tipe Data | Nullable | Key / Constraint | Default | Deskripsi Bisnis |
| :--- | :--- | :---: | :---: | :---: | :--- |
| `id` | `BIGINT UNSIGNED` | Tidak | **PK** (Auto Inc) | - | Identifier unik pengguna |
| `name` | `VARCHAR(255)` | Tidak | - | - | Nama lengkap pengguna |
| `email` | `VARCHAR(255)` | Ya | **UNIQUE** | `NULL` | Alamat surel (login & notifikasi) |
| `username` | `VARCHAR(255)` | Ya | **UNIQUE** | `NULL` | Nama pengguna unik untuk opsi login |
| `phone_number` | `VARCHAR(255)` | Ya | **UNIQUE** | `NULL` | Nomor telepon/WhatsApp untuk registrasi & OTP |
| `password` | `VARCHAR(255)` | Ya | - | `NULL` | Hash kata sandi terenkripsi (Bcrypt) |
| `is_verified` | `BOOLEAN` | Tidak | - | `false` | Menandakan apakah kontak/email telah diverifikasi |
| `created_at` | `TIMESTAMP` | Ya | - | `NULL` | Waktu akun didaftarkan |
| `updated_at` | `TIMESTAMP` | Ya | - | `NULL` | Waktu akun terakhir diperbarui |

#### 2. Tabel `otps`
Tabel penampung kode One-Time Password untuk login berbasis WhatsApp dan pemulihan kata sandi.

| Kolom | Tipe Data | Nullable | Key / Constraint | Default | Deskripsi Bisnis |
| :--- | :--- | :---: | :---: | :---: | :--- |
| `id` | `BIGINT UNSIGNED` | Tidak | **PK** (Auto Inc) | - | Identifier unik entri OTP |
| `identifier` | `VARCHAR(255)` | Tidak | Index | - | Target penerima (No WhatsApp / Email) |
| `otp_code` | `VARCHAR(255)` | Tidak | - | - | Kode OTP 4–6 digit (terenkripsi / acak) |
| `type` | `ENUM` | Tidak | - | - | Jenis aksi: `'login_whatsapp'` atau `'forgot_password'` |
| `attempts` | `INTEGER` | Tidak | - | `0` | Jumlah salah input (pencegahan *brute-force*) |
| `expires_at` | `TIMESTAMP` | Tidak | Index | - | Batas kedaluwarsa waktu toleransi kode OTP |
| `created_at` | `TIMESTAMP` | Ya | - | `NULL` | Waktu OTP digenerate |
| `updated_at` | `TIMESTAMP` | Ya | - | `NULL` | Waktu pembaruan status OTP |

#### 3. Tabel `personal_access_tokens` (Laravel Sanctum)
Tabel untuk mengelola token API otentikasi Bearer Token berbasis polimorfisme Eloquent.

| Kolom | Tipe Data | Nullable | Key / Constraint | Default | Deskripsi Bisnis |
| :--- | :--- | :---: | :---: | :---: | :--- |
| `id` | `BIGINT UNSIGNED` | Tidak | **PK** (Auto Inc) | - | Identifier unik token |
| `tokenable_type` | `VARCHAR(255)` | Tidak | Morph Index | - | Nama model pemilik (misal: `App\Models\User`) |
| `tokenable_id` | `BIGINT UNSIGNED` | Tidak | Morph Index | - | ID model pemilik (merujuk ke `users.id`) |
| `name` | `TEXT` | Tidak | - | - | Label nama token (misal: `'auth_token'`) |
| `token` | `VARCHAR(64)` | Tidak | **UNIQUE** | - | Hash SHA-256 dari plain token |
| `abilities` | `TEXT` | Ya | - | `NULL` | Array izin / hak akses token |
| `last_used_at` | `TIMESTAMP` | Ya | - | `NULL` | Terakhir kali token digunakan memanggil API |
| `expires_at` | `TIMESTAMP` | Ya | Index | `NULL` | Batas kedaluwarsa masa aktif token |
| `created_at` | `TIMESTAMP` | Ya | - | `NULL` | Waktu token dibuat |
| `updated_at` | `TIMESTAMP` | Ya | - | `NULL` | Waktu status token diubah |

#### 4. Tabel `password_reset_tokens`
Tabel untuk menyimpan token verifikasi pergantian/reset kata sandi.

| Kolom | Tipe Data | Nullable | Key / Constraint | Default | Deskripsi Bisnis |
| :--- | :--- | :---: | :---: | :---: | :--- |
| `email` | `VARCHAR(255)` | Tidak | **PK** | - | Alamat email pemohon reset sandi |
| `token` | `VARCHAR(255)` | Tidak | - | - | Token acak terenkripsi |
| `created_at` | `TIMESTAMP` | Ya | - | `NULL` | Waktu token reset digenerate |

#### 5. Tabel `sessions`
Tabel session driver terpusat untuk menyimpan status sesi login web secara aman di database.

| Kolom | Tipe Data | Nullable | Key / Constraint | Default | Deskripsi Bisnis |
| :--- | :--- | :---: | :---: | :---: | :--- |
| `id` | `VARCHAR(255)` | Tidak | **PK** | - | Session ID unik dari cookie pengguna |
| `user_id` | `BIGINT UNSIGNED` | Ya | Index / FK | `NULL` | ID pengguna login (merujuk ke `users.id`) |
| `ip_address` | `VARCHAR(45)` | Ya | - | `NULL` | IP address klien saat terhubung |
| `user_agent` | `TEXT` | Ya | - | `NULL` | Browser dan Sistem Operasi klien |
| `payload` | `LONGTEXT` | Tidak | - | - | Isi state sesi terenkripsi |
| `last_activity` | `INTEGER` | Tidak | Index | - | Timestamp UNIX aktivitas terkini |

---

## 🔄 Diagram Alur Sistem (Sequence Diagram)

### 1. Alur Registrasi dan Login (Email/Username)

Pengguna dapat mendaftar dengan akun baru, serta melakukan login menggunakan **Email** ataupun **Username** secara dinamis:
```mermaid
sequenceDiagram
    autonumber
    actor Client as Pengguna / Frontend
    participant API as AuthController (Laravel)
    participant Validator as Input Validator
    participant DB as Basis Data (Users dan Tokens)

    %% Skenario Registrasi
    rect rgb(240, 248, 255)
    note over Client, DB: Alur Registrasi Akun Baru
    Client->>API: POST /api/auth/register (name, email, phone_number, password, password_confirmation)
    API->>Validator: Validasi format dan keunikan (email, phone_number)
    alt Validasi Gagal
        Validator-->>API: Error validasi
        API-->>Client: 422 Unprocessable Content {success: false, errors}
    else Validasi Berhasil
        Validator-->>API: Lolos validasi
        API->>DB: INSERT INTO users (name, email, phone_number, password_hash)
        DB-->>API: Record User tersimpan
        API->>DB: INSERT INTO personal_access_tokens (Sanctum Token)
        DB-->>API: Plain-text Token dihasilkan
        API-->>Client: 201 Created {success: true, data: {user, token}}
    end
    end

    %% Skenario Login
    rect rgb(245, 255, 245)
    note over Client, DB: Alur Login (Multi-Identifier: Email / Username)
    Client->>API: POST /api/auth/login-email (identity, password)
    API->>API: Deteksi tipe identity (Filter Email vs Username)
    API->>DB: Cari user WHERE email = identity OR username = identity
    alt User Tidak Ditemukan atau Password Tidak Cocok
        DB-->>API: User null / Hash::check gagal
        API-->>Client: 401 Unauthorized {message: "Kombinasi email/username atau kata sandi tidak cocok."}
    else Autentikasi Sukses
        DB-->>API: User data valid
        API->>DB: Terbitkan Token Sanctum baru (createToken)
        DB-->>API: Bearer Token
        API-->>Client: 200 OK {success: true, message: "Masuk berhasil", data: {user, token}}
    end
    end
```

### 2. Alur Autentikasi OTP WhatsApp

Verifikasi dua langkah atau *passwordless login* menggunakan pesan WhatsApp:

```mermaid
sequenceDiagram
    autonumber
    actor Client as Pengguna / Frontend
    participant App as Backend API
    participant OTP_DB as Tabel OTPS
    participant WA as WhatsApp Gateway
    participant User_DB as Tabel USERS

    %% Permintaan OTP
    Client->>App: POST /api/auth/request-otp-wa (phone_number: "08123456789")
    App->>App: Generate kode acak 6-digit dan waktu berlaku (+5 menit)
    App->>OTP_DB: Simpan identifier, otp_code, type, expires_at
    App->>WA: Kirim pesan berisi kode OTP ke nomor WhatsApp
    WA-->>Client: Pesan WhatsApp diterima pengguna

    %% Verifikasi OTP
    Client->>App: POST /api/auth/verify-otp-wa (phone_number, otp_code)
    App->>OTP_DB: SELECT * WHERE identifier = target ORDER BY id DESC LIMIT 1
    alt Waktu Sekarang > expires_at (Kedaluwarsa)
        App-->>Client: 400 Bad Request (Kode OTP telah kedaluwarsa)
    else Kode OTP Salah
        App->>OTP_DB: Increment kolom attempts (+1)
        App-->>Client: 401 Unauthorized (Kode OTP tidak valid)
    else Kode OTP Benar
        App->>User_DB: Update is_verified = true
        App->>User_DB: Terbitkan Sanctum Bearer Token
        App->>OTP_DB: Hapus / invalidasi record OTP
        App-->>Client: 200 OK {success: true, token: "Bearer eyJhb..."}
    end
```

---

## 🔀 Flowchart Logika Setiap Endpoint (Mermaid)

Bagian ini menyajikan diagram alir logika (*flowchart*) sederhana untuk setiap endpoint API beserta poin-poin penjelasan singkat agar mudah dipahami dan dipresentasikan saat uji kompetensi (UKL).

```
┌────────────────────────────────────────────────────────────────────────┐
│                 RINGKASAN LOGIKA 7 ENDPOINT API                        │
├────────────────────────────────────────────────────────────────────────┤
│ 1. POST /api/auth/register               -> Validasi -> Hash -> Token  │
│ 2. POST /api/auth/login-email            -> Deteksi  -> Cek  -> Token  │
│ 3. POST /api/auth/request-otp-wa         -> Cek No   -> Buat -> Kirim  │
│ 4. POST /api/auth/verify-otp-wa          -> Batas 3x -> Cek  -> Token  │
│ 5. POST /api/auth/forgot-password/request-> Cek User -> Buat -> Kirim  │
│ 6. POST /api/auth/forgot-password/reset  -> Cek OTP  -> Hash -> Update │
│ 7. GET  /api/user                        -> Sanctum  -> Ambil Profile  │
└────────────────────────────────────────────────────────────────────────┘
```

---

### 1. POST `/api/auth/register` (Registrasi Akun)
Endpoint untuk mendaftarkan akun pengguna baru menggunakan nama, password, serta fleksibilitas kontak (Email atau Nomor WhatsApp).

```mermaid
flowchart TD
    A(["Mulai - Request Register"]) --> B["Client kirim Nama, Kontak, dan Password"]
    B --> C{"Validasi Input:<br/>Format dan Keunikan Data?"}
    C -- Gagal --> D["Kembalikan 422 Unprocessable Content<br/>Pesan error validasi"]
    C -- Berhasil --> E["Hash Password dengan Bcrypt"]
    E --> F["Simpan Data User Baru ke Database"]
    F --> G["Generate API Token Sanctum"]
    G --> H(["Selesai - Kembalikan 201 Created<br/>Data User dan Bearer Token"])
```

**💡 Cara Menjelaskan Alur Ini:**
1. **Input Data**: Klien mengirim nama, password, serta salah satu kontak wajib (Email atau No WhatsApp).
2. **Validasi**: Sistem memastikan format data valid dan email/nomor WA belum pernah didaftarkan sebelumnya. Jika duplikat/salah format, kembalikan HTTP `422`.
3. **Enkripsi & Simpan**: Password di-hash menggunakan algoritma Bcrypt demi keamanan data, lalu disimpan ke tabel `users`.
4. **Generate Token**: Sistem langsung menerbitkan personal access token (Sanctum) agar pengguna otomatis login dengan status HTTP `201 Created`.

---

### 2. POST `/api/auth/login-email` (Login Email / Username)
Endpoint masuk sistem secara dinamis yang otomatis mendeteksi apakah pengguna mengetikkan Email atau Username pada kolom identitas.

```mermaid
flowchart TD
    A(["Mulai - Request Login"]) --> B["Client kirim Identity dan Password"]
    B --> C{"Validasi Input:<br/>Identity dan Password terisi?"}
    C -- Gagal --> D["Kembalikan 422 Unprocessable Content"]
    C -- Berhasil --> E{"Deteksi Tipe Identity:<br/>Format Email atau Username?"}
    E -- Format Email --> F1["Cari User berdasarkan Email"]
    E -- Format Username --> F2["Cari User berdasarkan Username"]
    F1 --> G{"User Ditemukan dan<br/>Hash Password Cocok?"}
    F2 --> G
    G -- Tidak Cocok --> H["Kembalikan 401 Unauthorized<br/>Kombinasi salah"]
    G -- Cocok --> I["Buat Personal Access Token Sanctum"]
    I --> J(["Selesai - Kembalikan 200 OK<br/>Data User dan Bearer Token"])
```

**💡 Cara Menjelaskan Alur Ini:**
1. **Input Fleksibel**: Pengguna hanya perlu memasukkan kolom `identity` (bisa diisi alamat email atau username) beserta `password`.
2. **Deteksi Otomatis**: Backend secara pintar mengenali apakah input berisi format email (`FILTER_VALIDATE_EMAIL`). Jika bukan email, dicari sebagai username.
3. **Pencocokan Sandi**: Sistem memeriksa apakah akun ditemukan dan kata sandi cocok via `Hash::check`. Jika salah, kembalikan HTTP `401 Unauthorized`.
4. **Token Login**: Jika data benar, token Sanctum baru dibuatkan dan dikirimkan ke pengguna dengan status HTTP `200 OK`.

---

### 3. POST `/api/auth/request-otp-wa` (Permintaan OTP WhatsApp)
Endpoint untuk membuat dan mengirimkan 6 digit One-Time Password ke nomor WhatsApp terdaftar.

```mermaid
flowchart TD
    A(["Mulai - Request OTP"]) --> B["Client kirim Nomor WhatsApp"]
    B --> C{"Cek Nomor Terdaftar<br/>di Database Users?"}
    C -- Tidak Terdaftar --> D["Kembalikan 404 Not Found<br/>Nomor belum terdaftar"]
    C -- Terdaftar --> E["Generate 6 Digit Angka Acak OTP"]
    E --> F["Simpan Hash OTP ke Tabel otps<br/>Expired: 5 Menit, Attempts: 0"]
    F --> G["Kirim Kode via WhatsApp Gateway"]
    G --> H(["Selesai - Kembalikan 200 OK<br/>OTP Berhasil Dikirimkan"])
```

**💡 Cara Menjelaskan Alur Ini:**
1. **Pengecekan Nomor**: Sistem memastikan nomor WhatsApp sudah terdaftar di database. Jika belum, kembalikan HTTP `404 Not Found`.
2. **Pembuatan Kode**: Sistem meng-generate 6 digit angka acak (misal: `748291`).
3. **Keamanan OTP**: Kode disimpan di tabel `otps` dalam bentuk hash (bukan teks mentah), diset kedaluwarsa dalam 5 menit, dan counter kesalahan disetel ke 0.
4. **Pengiriman**: Kode dikirim ke WhatsApp pengguna melalui integrasi gateway, lalu respon HTTP `200 OK` dikembalikan.

---

### 4. POST `/api/auth/verify-otp-wa` (Verifikasi OTP WhatsApp)
Endpoint verifikasi kode OTP WhatsApp untuk login tanpa password (*passwordless login*) dengan perlindungan *brute-force*.

```mermaid
flowchart TD
    A(["Mulai - Verifikasi OTP"]) --> B["Client kirim No WhatsApp dan 6 Digit OTP"]
    B --> C{"Cek Data OTP Aktif di Database?"}
    C -- Tidak Ada atau Expired --> D["Kembalikan 400 Bad Request<br/>OTP salah atau kadaluarsa"]
    C -- Masih Berlaku --> E{"Percobaan Gagal<br/>Sudah 3 Kali atau Lebih?"}
    E -- Ya --> F["Kembalikan 429 Too Many Requests<br/>Akses dibekukan sementara"]
    E -- Tidak --> G{"Verifikasi Kode:<br/>Hash OTP Cocok?"}
    G -- Salah --> H["Tingkatkan Kolom attempts + 1"]
    H --> I["Kembalikan 400 Bad Request<br/>Pesan sisa percobaan"]
    G -- Benar --> J["Hapus Record OTP dari Database"]
    J --> K["Generate API Token Sanctum"]
    K --> L(["Selesai - Kembalikan 200 OK<br/>Login Sukses dan Bearer Token"])
```

**💡 Cara Menjelaskan Alur Ini:**
1. **Pengecekan Masa Berlaku**: Sistem memeriksa apakah ada OTP aktif untuk nomor tersebut dan apakah masih dalam rentang 5 menit. Jika lewat, tolak dengan HTTP `400 Bad Request`.
2. **Proteksi Brute-Force**: Jika salah input sudah mencapai 3 kali atau lebih, akun dibekukan sementara dengan HTTP `429 Too Many Requests`.
3. **Verifikasi Hash**: Jika kode salah, sistem menambah nilai `attempts` dan memberitahukan sisa kesempatan.
4. **Pembersihan & Token**: Jika kode benar, data OTP langsung dihapus (mencegah *replay attack*) dan token Sanctum diterbitkan (`200 OK`).

---

### 5. POST `/api/auth/forgot-password/request` (Permintaan Lupa Password)
Endpoint untuk meminta kode verifikasi pemulihan kata sandi melalui saluran Email ataupun WhatsApp.

```mermaid
flowchart TD
    A(["Mulai - Request Lupa Password"]) --> B["Client pilih Metode dan masukkan Identifier"]
    B --> C{"Pengguna Terdaftar di Database?"}
    C -- Tidak Ditemukan --> D["Kembalikan 404 Not Found<br/>Pengguna tidak ditemukan"]
    C -- Ditemukan --> E["Generate 6 Digit Kode OTP"]
    E --> F["Simpan Hash OTP ke Tabel otps<br/>Tipe: forgot_password, Expired: 10 Menit"]
    F --> G["Kirim Kode ke Email atau WhatsApp Pengguna"]
    G --> H(["Selesai - Kembalikan 200 OK<br/>Instruksi Reset Terkirim"])
```

**💡 Cara Menjelaskan Alur Ini:**
1. **Pilihan Saluran**: Pengguna memilih saluran pengiriman (`email` atau `whatsapp`) dan memasukkan identitas tujuannya.
2. **Pengecekan User**: Backend memastikan bahwa data pengguna memang ada di database. Jika tidak ada, kembalikan HTTP `404 Not Found`.
3. **Simpan OTP Reset**: Sistem membuat kode 6 digit OTP acak, menyimpannya dalam tabel `otps` dengan tipe `forgot_password` dan masa berlaku 10 menit.
4. **Kirim Instruksi**: Kode instruksi dikirim ke kontak yang dipilih dan mengembalikan respon HTTP `200 OK`.

---

### 6. POST `/api/auth/forgot-password/reset` (Reset Password Baru)
Endpoint untuk mengeksekusi penggantian kata sandi lama dengan kata sandi baru setelah OTP tervalidasi.

```mermaid
flowchart TD
    A(["Mulai - Reset Password"]) --> B["Client kirim Identifier, OTP, dan Password Baru"]
    B --> C{"Validasi Data OTP:<br/>Ada, Belum Expired, dan Kode Cocok?"}
    C -- Tidak Cocok atau Expired --> D["Kembalikan 400 Bad Request<br/>Kode verifikasi tidak valid atau kadaluarsa"]
    C -- Valid dan Cocok --> E["Hash Password Baru dengan Bcrypt"]
    E --> F["Update Kolom password pada Tabel Users"]
    F --> G["Hapus Record OTP dari Database"]
    G --> H(["Selesai - Kembalikan 200 OK<br/>Password Berhasil Diperbarui"])
```

**💡 Cara Menjelaskan Alur Ini:**
1. **Input Data Baru**: Klien mengirimkan identitas akun, kode OTP yang diterima, serta password baru beserta konfirmasinya.
2. **Validasi OTP**: Sistem mengecek apakah OTP cocok dan belum melewati batas waktu 10 menit. Jika tidak valid, kembalikan HTTP `400 Bad Request`.
3. **Update Password**: Password baru di-hash dan disimpan menggantikan password lama pada tabel `users`.
4. **Hapus OTP**: Record OTP segera dihapus agar tidak bisa dipakai kembali, lalu respon sukses HTTP `200 OK` dikembalikan.

---

### 7. GET `/api/user` (Profil Pengguna Terautentikasi)
Endpoint terproteksi (*protected route*) untuk mengambil data profil lengkap pengguna yang sedang aktif/login.

```mermaid
flowchart TD
    A(["Mulai - Request Profil"]) --> B["Client kirim Request dengan Header Bearer Token"]
    B --> C{"Middleware auth:sanctum:<br/>Apakah Token Valid dan Aktif?"}
    C -- Tidak Ada atau Tidak Valid --> D["Kembalikan 401 Unauthorized<br/>Unauthenticated"]
    C -- Token Valid --> E["Ambil Data Pengguna Terkait dari Database"]
    E --> F(["Selesai - Kembalikan 200 OK<br/>Data Profil Pengguna Lengkap"])
```

**💡 Cara Menjelaskan Alur Ini:**
1. **Header Autentikasi**: Klien wajib menyertakan token API pada header request: `Authorization: Bearer <token>`.
2. **Middleware Sanctum**: Laravel Sanctum memeriksa validitas token pada tabel `personal_access_tokens`. Jika token tidak ada atau kadaluarsa, kembalikan HTTP `401 Unauthorized`.
3. **Respon Profil**: Jika token valid, sistem mengambil record user yang berelasi dan mengembalikannya dalam format JSON dengan status HTTP `200 OK`.

---

## 📡 Dokumentasi API Endpoint

Seluruh endpoint autentikasi dikelompokkan dalam prefix `/api/auth`, serta endpoint profil `/api/user`:

### 1. Registrasi Akun Pengguna
- **URL**: `/api/auth/register`
- **Method**: `POST`
- **Headers**: `Accept: application/json`, `Content-Type: application/json`
- **Payload Request**:
  ```json
  {
    "name": "Budi Santoso",
    "email": "budi@example.com",
    "phone_number": "081234567890",
    "password": "Password123!",
    "password_confirmation": "Password123!"
  }
  ```
  *(Keterangan: `email` dan `phone_number` bersifat fleksibel, salah satunya wajib diisi).*
- **Contoh Respons Sukses (201 Created)**:
  ```json
  {
    "success": true,
    "message": "Pendaftaran berhasil",
    "data": {
      "user": {
        "id": 1,
        "name": "Budi Santoso",
        "email": "budi@example.com",
        "phone_number": "081234567890",
        "created_at": "2026-10-07T06:55:00.000000Z",
        "updated_at": "2026-10-07T06:55:00.000000Z"
      },
      "token": "1|qXyZabcde12345..."
    }
  }
  ```

### 2. Login (Email atau Username)
- **URL**: `/api/auth/login-email`
- **Method**: `POST`
- **Headers**: `Accept: application/json`, `Content-Type: application/json`
- **Payload Request**:
  ```json
  {
    "identity": "budi@example.com",
    "password": "Password123!"
  }
  ```
  *(Keterangan: Kolom `identity` dapat diisi alamat email maupun username pengguna).*
- **Contoh Respons Sukses (200 OK)**:
  ```json
  {
    "success": true,
    "message": "Masuk berhasil",
    "data": {
      "user": {
        "id": 1,
        "name": "Budi Santoso",
        "email": "budi@example.com",
        "phone_number": "081234567890"
      },
      "token": "2|hIjkLmNoP67890..."
    }
  }
  ```

### 3. Permintaan OTP WhatsApp
- **URL**: `/api/auth/request-otp-wa`
- **Method**: `POST`
- **Payload Request**:
  ```json
  {
    "phone_number": "081234567890"
  }
  ```
- **Contoh Respons Sukses (200 OK)**:
  ```json
  {
    "success": true,
    "message": "Kode OTP berhasil dikirimkan via WhatsApp."
  }
  ```

### 4. Verifikasi OTP WhatsApp
- **URL**: `/api/auth/verify-otp-wa`
- **Method**: `POST`
- **Payload Request**:
  ```json
  {
    "phone_number": "081234567890",
    "otp_code": "123456"
  }
  ```
- **Contoh Respons Sukses (200 OK)**:
  ```json
  {
    "success": true,
    "message": "Verifikasi berhasil",
    "data": {
      "user": { "id": 1, "name": "Budi Santoso", "phone_number": "081234567890" },
      "token": "3|mNpQrStUv54321..."
    }
  }
  ```

### 5. Permintaan Lupa Kata Sandi (Forgot Password)
- **URL**: `/api/auth/forgot-password/request`
- **Method**: `POST`
- **Payload Request**:
  ```json
  {
    "method": "whatsapp",
    "identifier": "081234567890"
  }
  ```
  *(Keterangan: `method` dapat bernilai `"whatsapp"` atau `"email"`).*
- **Contoh Respons Sukses (200 OK)**:
  ```json
  {
    "success": true,
    "message": "Instruksi reset kata sandi telah dikirimkan."
  }
  ```

### 6. Reset Kata Sandi Baru
- **URL**: `/api/auth/forgot-password/reset`
- **Method**: `POST`
- **Payload Request**:
  ```json
  {
    "identifier": "081234567890",
    "otp_code": "123456",
    "password": "PasswordBaru123!",
    "password_confirmation": "PasswordBaru123!"
  }
  ```
- **Contoh Respons Sukses (200 OK)**:
  ```json
  {
    "success": true,
    "message": "Kata sandi berhasil diperbarui. Silakan masuk kembali."
  }
  ```

### 7. Profil Pengguna Terautentikasi
- **URL**: `/api/user`
- **Method**: `GET`
- **Headers**: `Accept: application/json`, `Authorization: Bearer <token>`
- **Contoh Respons Sukses (200 OK)**:
  ```json
  {
    "id": 1,
    "name": "Budi Santoso",
    "email": "budi@example.com",
    "phone_number": "081234567890",
    "is_verified": true
  }
  ```

---

## 📮 Koleksi Postman (Postman Collection)

Koleksi Postman lengkap beserta environment lokal telah disediakan di dalam folder terpisah [`postman/`](./postman):

- 📄 **Collection File**: [`postman/tugas-ukl2.postman_collection.json`](./postman/tugas-ukl2.postman_collection.json)
- 🌍 **Environment File**: [`postman/tugas-ukl2.postman_environment.json`](./postman/tugas-ukl2.postman_environment.json)
- 📖 **Panduan Penggunaan**: [`postman/README.md`](./postman/README.md)

Koleksi telah dilengkapi dengan *test script* otomatis yang menyimpan token Sanctum ke variabel `{{token}}` setelah proses register, login, atau verifikasi OTP WhatsApp berhasil.
---

## 💻 Panduan Instalasi & Menjalankan Proyek

### Prasyarat Sistem
- **PHP** versi `>= 8.3` atau `>= 8.4` dengan ekstensi (`pdo`, `sqlite3` atau `pdo_mysql`, `mbstring`, `openssl`).
- **Composer** versi `>= 2.x`.
- **Node.js** & **NPM** (opsional untuk asset frontend).

### Langkah-langkah Instalasi

1. **Clone Repositori**:
   ```bash
   git clone <url-repository> tugas-ukl2
   cd tugas-ukl2
   ```

2. **Instal Dependensi PHP**:
   ```bash
   composer install
   ```

3. **Konfigurasi Environment**:
   Salin file konfigurasi environment dan buat App Encryption Key:
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

4. **Konfigurasi Database**:
   Secara default, aplikasi menggunakan database **SQLite**. Buat berkas database lokal jika belum ada:
   ```bash
   touch database/database.sqlite
   ```
   *(Jika menggunakan MySQL, sesuaikan nilai `DB_CONNECTION=mysql`, `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, dan `DB_PASSWORD` di berkas `.env`).*

5. **Jalankan Migrasi Database**:
   Jalankan migrasi untuk membuat seluruh tabel skema basis data:
   ```bash
   php artisan migrate
   ```

6. **Menjalankan Server Pengembangan**:
   Jalankan local development server:
   ```bash
   php artisan serve
   ```
   API kini dapat diakses melalui URL: `http://127.0.0.1:8000`.

---

## 🧪 Pengujian (Testing)

Proyek ini telah dikonfigurasi dengan framework pengujian modern **Pest PHP**:

Jalankan seluruh rangkaian pengujian fitur dan unit:
```bash
php artisan test --compact
```
Atau jalankan langsung melalui binary Pest:
```bash
vendor/bin/pest
```

---

## 📄 Lisensi

Proyek ini bersifat open-source dan dilisensikan di bawah lisensi [MIT License](LICENSE).
