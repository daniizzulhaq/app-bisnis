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
| Nota | Tanpa nomor; dikenali dari klien + tanggal. Barang dipilih per supplier, supplier terisi otomatis |
| Supplier & barang | Tiap supplier punya warna penanda; barang dikelompokkan per supplier langganan |
| Total nota | Dihitung live di JS, **dihitung ulang di server** (`App\Services\TransaksiService`) |
| Status bayar | Dari total pembayaran **klien** yang terkait nota: belum / sebagian / lunas |
| Pembayaran klien | Tidak boleh melebihi sisa tagihan nota |
| Cashflow | Dibuat/diubah/dihapus otomatis oleh `PembayaranObserver`; baris otomatis hanya bisa diubah dari menu Pembayaran |
| Hapus | Master yang sudah dipakai, dan nota yang sudah punya pembayaran, tidak bisa dihapus |
| Laporan | Total & "dibayar" = dalam periode; **sisa piutang/hutang = kumulatif s/d tanggal akhir periode**. Filter beberapa klien/supplier, format qty (Total: 2 kg · 1 + 1: 1 + 1 kg untuk barang yang dipesan beberapa kali di hari yang sama), mode harga (lengkap/jual/beli). PDF bisa dilihat dulu atau diunduh |
| Nota cetak | Hanya harga jual (harga beli & supplier tidak tampil ke klien) |

## Nota, laporan, dan perputaran modal

- Form nota baru dimulai dengan 0 baris. Gunakan **Tambah Item** untuk menambahkan barang. Di HP, form menggunakan tampilan desktop yang bisa diperbesar dengan pinch zoom.
- Setelah mengedit nota dari rekapan, tombol **Kembali** mempertahankan tanggal, filter, dan halaman daftar asal.
- Laporan **Semua klien** langsung menyertakan rincian barang tiap klien, termasuk di PDF dan Excel. Tombol **Semua klien** menghapus pilihan klien tanpa mengubah periode atau filter lainnya.
- Pada pembayaran supplier, **Kaitkan ke Nota** menyediakan total beli dari semua klien per tanggal untuk supplier terpilih. Sisa tagihan memperhitungkan pembayaran gabungan dan pembayaran individual sebelumnya.
- Cashflow memiliki ringkasan **Perputaran Modal**. Catat modal awal sebagai cashflow masuk berkategori **Modal**, atau gunakan **Input Modal Awal**. Rumus: setoran bersih modal dikurangi total beli nota, ditambah harga beli yang sudah terbayar klien.
- Pengembalian modal dihitung proporsional dari pembayaran klien yang terkait nota: nota beli Rp400.000/jual Rp500.000 yang dibayar Rp250.000 mengembalikan modal Rp200.000. Ketika lunas, seluruh Rp400.000 kembali. Laba tetap di luar pengembalian modal. Pembayaran supplier tercatat pada arus kas dan tidak mengurangi perputaran modal untuk kedua kalinya.
- Edit, hapus, dan pemulihan nota/pembayaran langsung tercermin dalam perhitungan modal. Pada instalasi yang sudah ada, jalankan `php artisan migrate` untuk menambahkan kolom tanggal nota pembayaran supplier.

## Test

Membutuhkan database `rekap_bisnis_test` (lihat `phpunit.xml`):

```bash
php artisan test
```
