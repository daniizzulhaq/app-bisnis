# Rekap Bisnis

Pembukuan jual-beli untuk supplier sayur yang melayani hotel & restoran (1 admin).
Laravel 12 · MySQL · Blade + Bootstrap 5 · dompdf · Laravel Excel.

## Instalasi

```bash
composer install
cp .env.example .env          # atur DB_DATABASE, DB_USERNAME, DB_PASSWORD
php artisan key:generate
php artisan migrate --seed    # hanya akun admin untuk login
php artisan serve             # http://127.0.0.1:8000
```

Login: **admin@admin.com / password** (segera ganti di menu User).

Tidak perlu `npm`: Bootstrap, ikon, SweetAlert2, dan Tom Select dimuat dari CDN;
kustomisasi ada di `public/css/app.css` dan `public/js/app.js`.

Reset database: `php artisan migrate:fresh --seed --force` (**menghapus semua data**, sisa akun admin saja).

## Aturan bisnis penting

| Hal | Aturan |
|---|---|
| No. nota | Otomatis per bulan: `NT-YYMM-0001` (bulan dari tanggal nota) |
| Total nota | Dihitung live di JS, **dihitung ulang di server** (`App\Services\TransaksiService`) |
| Status bayar | Dari total pembayaran **klien** yang terkait nota: belum / sebagian / lunas |
| Pembayaran klien | Tidak boleh melebihi sisa tagihan nota |
| Cashflow | Dibuat/diubah/dihapus otomatis oleh `PembayaranObserver`; baris otomatis hanya bisa diubah dari menu Pembayaran |
| Hapus | Master yang sudah dipakai, dan nota yang sudah punya pembayaran, tidak bisa dihapus |
| Laporan | Total & "dibayar" = dalam periode; **sisa piutang/hutang = kumulatif s/d tanggal akhir periode** |
| Nota cetak | Hanya harga jual (harga beli & supplier tidak tampil ke klien) |

## Test

Membutuhkan database `rekap_bisnis_test` (lihat `phpunit.xml`):

```bash
php artisan test
```
