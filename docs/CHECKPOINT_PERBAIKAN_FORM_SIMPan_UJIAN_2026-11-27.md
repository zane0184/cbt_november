# CHECKPOINT PERBAIKAN FORM SIMPAN UJIAN — 2026-11-27

## AUDIT
- Screenshot form pengguna menunjukkan tidak ada input Nama Kegiatan dan Tujuan Asesmen.
- Response POST Livewire HTTP 200 memuat errors:
  - `activityName`: The activity name field is required.
  - `assessmentPurpose`: The assessment purpose field is required.
- Snapshot komponen Livewire menunjukkan kedua properti kosong.
- Source `ExamManagement.php::saveExam()` memang mewajibkan kedua field tersebut: activityName required|string|max:255; assessmentPurpose required|string|max:5000.
- Blade sebelumnya tidak merender kedua input tersebut. Ini ketidaksesuaian langsung antara validasi backend dan form.

## DESAIN
- Pertahankan validasi wajib karena data nama kegiatan dan tujuan asesmen menjadi bagian konteks asesmen dan audit.
- Tambahkan kedua field pada form, tepat setelah Judul Ujian.
- Tampilkan pesan validasi inline.
- Tidak mengubah aturan tenant, penugasan, taksonomi, snapshot, peserta, atau jadwal.

## IMPLEMENTASI
- File runtime diubah: `backend-laravel/resources/views/livewire/admin/exam-management.blade.php`.
- Ditambahkan:
  - input `wire:model="activityName"`, required, maxlength 255.
  - textarea `wire:model="assessmentPurpose"`, required, maxlength 5000.
  - pesan validasi untuk kedua properti.
- Backup sebelum perubahan:
  `backend-laravel/backups/exam-form-required-assessment-fields-20261127/exam-management.blade.php`.
- Backup SHA: `ea34d793bd321ed37a9ccad4e05f44886937e7b0`.
- Source setelah patch SHA: `91cea11ca52c2181eaa86139c78b61da3dfef71a`.
- Commit backup: `4eb0b9024265e2c3f473aa7d6dfce4a54d58c106`.
- Commit patch: `a175149cdde4d9335e173fb593e7db62bb9946b2`.
- Source dan backup dibaca ulang setelah commit; field baru terverifikasi di source, dan backup terverifikasi tidak berisi field tambahan.

## PENGUJIAN
- Verifikasi source/backup via GitHub selesai.
- PHPUnit dan browser runtime belum dijalankan.
- VPS belum diperbarui; keberhasilan simpan belum terbukti.

## PENERAPAN
- Backup file Blade yang sedang aktif di VPS.
- Ganti file runtime sesuai path di atas.
- Bersihkan cache view Laravel bila deployment menggunakan compiled Blade cache.
- Refresh halaman Buat Ujian dan isi dua field baru.
- Uji simpan satu kali; pastikan ujian tersimpan sebagai DRAFT.
- Tidak perlu migration database.
