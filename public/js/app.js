/* =========================================================
   Rekap Bisnis - Script global
   Dependensi (CDN): Bootstrap 5, SweetAlert2, Tom Select
   ========================================================= */

window.RB = window.RB || {};

/** Toast kecil di pojok kanan atas (sukses / gagal / info). */
RB.toast = Swal.mixin({
    toast: true,
    position: 'top-end',
    showConfirmButton: false,
    timer: 3500,
    timerProgressBar: true,
    didOpen: (el) => {
        el.addEventListener('mouseenter', Swal.stopTimer);
        el.addEventListener('mouseleave', Swal.resumeTimer);
    },
});

/** Format angka ke Rupiah: 9965000 -> "Rp 9.965.000". Dipakai di form transaksi. */
RB.rupiah = (n) => 'Rp ' + Math.round(Number(n) || 0).toLocaleString('id-ID');

/** Format angka biasa dengan maksimal 2 desimal: 1250.5 -> "1.250,5". */
RB.angka = (n) => (Number(n) || 0).toLocaleString('id-ID', { maximumFractionDigits: 2 });

/** Ubah teks "9.965.000" menjadi angka 9965000. */
RB.parseUang = (s) => Number(String(s ?? '').replace(/[^\d,]/g, '').replace(',', '.')) || 0;

/** Ubah teks qty "12,5" / "12.5" menjadi angka 12.5. */
RB.parseQty = (s) => Number(String(s ?? '').replace(',', '.')) || 0;

/** Format isi <input class="input-uang"> dengan pemisah ribuan saat diketik. */
RB.formatInputUang = (el) => {
    const digit = el.value.replace(/\D/g, '');
    el.value = digit ? Number(digit).toLocaleString('id-ID') : '';
};

document.addEventListener('input', (e) => {
    if (e.target.classList?.contains('input-uang')) RB.formatInputUang(e.target);
});

/** Aktifkan Tom Select pada <select class="tom-select"> di dalam elemen root. */
RB.initTomSelect = (root = document) => {
    root.querySelectorAll('select.tom-select:not(.tomselected)').forEach((el) => {
        new TomSelect(el, {
            // Dropdown dirender di <body> agar tidak terpotong oleh .table-responsive / card
            dropdownParent: 'body',
            create: false,
            allowEmptyOption: true,
            maxOptions: 500,
            placeholder: el.dataset.placeholder || 'Pilih...',
            render: {
                no_results: () => '<div class="no-results">Data tidak ditemukan</div>',
            },
        });
    });
};

/**
 * Isi atribut data-label setiap sel dari judul kolom (<th>).
 * Di layar HP, CSS memakai label ini untuk menampilkan tabel sebagai kartu.
 */
RB.labelTabel = () => {
    document.querySelectorAll('.app-content table.table').forEach((tabel) => {
        const judul = [...tabel.querySelectorAll('thead th')].map((th) => th.textContent.trim());
        tabel.querySelectorAll('tbody > tr, tfoot > tr').forEach((tr) => {
            let kolom = 0;
            [...tr.children].forEach((td) => {
                if (!td.hasAttribute('data-label')) td.dataset.label = judul[kolom] ?? '';
                kolom += td.colSpan || 1;
            });
        });
    });
};

document.addEventListener('DOMContentLoaded', () => {
    RB.labelTabel();
    RB.initTomSelect();
    document.querySelectorAll('.input-uang').forEach(RB.formatInputUang);

    // Konfirmasi hapus: <form class="form-delete" data-nama="Nama data">
    document.addEventListener('submit', (e) => {
        const form = e.target;
        if (!form.classList.contains('form-delete') || form.dataset.confirmed) return;

        e.preventDefault();
        Swal.fire({
            title: 'Hapus data?',
            html: `Data <b>${form.dataset.nama || 'ini'}</b> akan dihapus permanen.`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Ya, hapus',
            cancelButtonText: 'Batal',
            confirmButtonColor: '#ef4444',
            cancelButtonColor: '#8a8fa3',
            reverseButtons: true,
        }).then((res) => {
            if (res.isConfirmed) {
                form.dataset.confirmed = '1';
                form.submit();
            }
        });
    });

    // Cegah submit ganda: tombol submit di-disable setelah diklik
    document.addEventListener('submit', (e) => {
        if (e.defaultPrevented) return;
        e.target.querySelectorAll('button[type="submit"][data-loading]').forEach((btn) => {
            btn.disabled = true;
            btn.innerHTML = `<span class="spinner-border spinner-border-sm me-1"></span> ${btn.dataset.loading}`;
        });
    });
});
