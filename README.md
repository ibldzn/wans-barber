# WAN'S Barbershop & Reflexology POS

Aplikasi POS kasir + inventory + finance + payroll berbasis Laravel 12 dan Filament v4. Fokusnya untuk operasional barbershop/reflexology dengan alur kerja cepat, laporan ringkas, dan slip gaji PDF.

## Fitur Utama
- POS kasir dengan input cepat, price tier (regular / callout), dan pilihan pegawai per item.
- Inventory retail + consumable (pemakaian consumable otomatis dari penjualan jasa).
- Ledger income/expense otomatis dari penjualan/pembelian + input manual.
- Payroll berbasis absensi + komisi + potongan kasbon.
- Kasbon bisa dicicil per payroll (nominal bisa berbeda tiap periode).
- Slip gaji PDF A4.

## Teknologi
- Laravel 12
- Filament v4
- Filament Shield + Spatie Permission
- DomPDF

## Setup Lokal

### Prasyarat
- PHP ^8.2
- Composer
- Node.js + npm
- Database (MySQL/PostgreSQL/SQLite)

### Instalasi
1. Install dependencies:

```bash
composer install
npm install
```

2. Buat file env:

```bash
cp .env.example .env
php artisan key:generate
```

3. Set database di `.env`:

```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=wans_barber
DB_USERNAME=root
DB_PASSWORD=
```

4. Migrasi + seeding:

```bash
php artisan migrate
php artisan db:seed
php artisan db:seed --class=ShieldSeeder
```

5. Build assets:

```bash
npm run build
```

6. Jalankan aplikasi:

```bash
php artisan serve
```

Akses panel Filament di:
```
http://localhost:8000/kasir
```

### User Pertama (Super Admin)
`ShieldSeeder` akan memberi role `super_admin` ke user pertama di tabel `users`.
Jika belum ada user, buat via tinker:

```bash
php artisan tinker
>>> \App\Models\User::create([
... 'name' => 'Owner',
... 'email' => 'owner@local.test',
... 'password' => bcrypt('password'),
... ])
```

Lalu jalankan lagi:

```bash
php artisan db:seed --class=ShieldSeeder
```

## Cara Pakai

### 1. Master Data
Siapkan data awal berikut:
- Product Categories (Service / Retail / Consumable) + komisi default.
- Products (harga reguler, harga callout, type).
- Employees.
- Payment Methods.
- Finance Categories.
- Suppliers.

### 2. POS (Sales)
- Buka menu **POS**.
- Pilih kasir, payment method, lalu tambahkan item.
- Set pegawai per item (wajib untuk service).
- Submit transaksi.

Hasilnya otomatis:
- Sale + Sale Items.
- Inventory movement (out).
- Ledger income (jasa / barang).

### 3. Inventory
- **Purchases** untuk menambah stok retail/consumable.
- **Inventory Movements** untuk penyesuaian stok manual.
- Produk service bisa punya mapping consumable (Product Consumables).

### 4. Finance
- **Financial Transactions** untuk income/expense manual.
- Laporan bisa dilihat di **Reports**.

### 5. Payroll & Absensi
1. Input **Employee Attendance** per hari.
2. Buat **Payroll Period**.
3. Klik **Generate Payslips**.
4. Buka **Payslips** untuk detail & PDF.
5. Klik **Mark Paid** untuk mencatat expense gaji ke ledger.

### 6. Kasbon & Cicilan
- Buat kasbon di **Employee Cash Advances**.
- Jika cicilan bervariasi tiap periode, input di menu **Pembayaran Kasbon**.
  Contoh: bulan 1 = 1.000.000, bulan 2 = 300.000, bulan 3 = 500.000, dst.
- Payroll akan memotong sesuai pembayaran yang ada di periode itu.
- Jika tidak ada pembayaran tetapi kasbon punya **Cicilan Default**, sistem otomatis potong sesuai default.

### 7. Laporan
- **Rekap Metode Pembayaran**: filter tanggal lalu lihat total per metode + per tanggal.
- **Laporan Laba Rugi**: ringkasan income vs expense.

## Catatan Operasional
- Set timezone: `Asia/Jakarta`.
- Currency IDR (display tanpa desimal).
- Satu transaksi = satu payment method.
- Tidak ada fitur refund/return di MVP.

## Troubleshooting
- Menu tidak muncul / tombol hilang:
  Jalankan `php artisan db:seed --class=ShieldSeeder` untuk regenerasi permission.
- Setelah update resource baru:
  Jalankan `php artisan db:seed --class=ShieldSeeder` lagi.
- Login gagal setelah perubahan `.env`:
  Jalankan `php artisan config:clear`.

