# LAPORAN PEMBARUAN PROMPT AGENT PENGGANTI — W2 FINAL
Tanggal: 24 November 2026 (WIB)
Repository: `zane0184/cbt_november`
Branch: `work/solo-web-cbt-completion-20261123-execution`

## TUJUAN
Memperbarui instruksi agent pengganti agar memahami kondisi produk yang sebenarnya: CBT sudah berjalan di VPS, dan tugas saat ini adalah melanjutkan penyempurnaan fitur web yang belum lengkap, bukan membangun ulang aplikasi atau mengulang pekerjaan yang sudah selesai.

## AUDIT
- Owner mengonfirmasi aplikasi telah terpasang dan berjalan pada https://cbt.min15jakarta.sch.id/login.
- ZIP aplikasi sebelumnya sudah pernah dibuat dan dipasang.
- VPS, konfigurasi, dan instalasi sebelumnya telah melalui verifikasi ketat dan dinyatakan lancar oleh owner.
- Fokus masalah saat ini adalah kelengkapan dan fungsi fitur aplikasi yang sudah ditetapkan.
- Branch kerja diverifikasi: `work/solo-web-cbt-completion-20261123-execution`.
- HEAD source sebelum pembaruan dokumen: `e894d3f4727e3bfcfdb45a29ce1da5c3abd6b598`.
- CI #1714 pada exact SHA tersebut: focused login/tenant 30 passed/99 assertions; Admin reauthorization 5/5; Teacher result reauthorization 4/8; complete PHPUnit **536 passed/2,231 assertions/0 failed**.
- Workflow #1714 berstatus FAILURE hanya karena post-check InnoDB salah membentuk query dan memicu `Unknown column 'tenants' in 'where clause'`. PHPUnit sendiri selesai PASS. Ini harus ditulis dengan akurat; jangan mengubah source aplikasi untuk memperbaiki query workflow.
- Implementasi profil dasar Admin Madrasah sudah ada dan tiga test profil lulus dalam full PHPUnit. Fitur profil lanjutan (metadata tambahan, logo/private asset, branding login, kop surat/preview) masih belum selesai sesuai laporan W2.
- Issue #20 dan master spesifikasi tetap menjadi backlog/acceptance sumber kerja; tidak semua fitur Admin/Guru/Siswa dianggap selesai hanya karena test tertentu hijau.

## DESAIN PEMBARUAN PROMPT
Prompt baru menetapkan:
1. **LANJUTKAN, JANGAN MENGULANG** sebagai instruksi paling utama.
2. Aplikasi yang sudah berjalan adalah produk yang harus disempurnakan; tidak boleh memulai ulang atau membuat ulang source dari asumsi.
3. VPS/konfigurasi server telah diverifikasi oleh owner; agent tidak boleh meminta spesifikasi VPS berulang atau mengulang audit infrastruktur generik.
4. Website live diperlakukan sebagai produksi sensitif; tidak boleh melakukan eksperimen, test destruktif, migration, upload, atau deploy tanpa otorisasi.
5. GitHub tetap menjadi satu-satunya sumber kebenaran pekerjaan.
6. Source dan keputusan yang sudah selesai harus dipertahankan; perubahan harus minimal, dengan backup byte-exact sebelum edit.
7. Fokus utama: menyelesaikan seluruh fitur Web Admin, Guru, Siswa dan backend; menguji integrasi end-to-end; kemudian menghasilkan satu ZIP lengkap yang siap upload/extract setelah acceptance gate lulus.
8. Memisahkan status SIAP DIDISTRIBUSIKAN KE VPS dari SIAP PRODUKSI.
9. Memuat status CI #1714 secara tepat dan instruksi memperbaiki hanya post-check workflow InnoDB.
10. Mempertahankan keputusan owner: instalasi/database mandiri per madrasah, role resmi ADMIN_MADRASAH/GURU/SISWA, Proktor sebagai assignment sementara pada Guru, Kamad/Operator bukan portal aktif, Kurmer+KBC satu kesatuan, DOCX import utama, XLSX question import nonaktif, SHORT_ANSWER fail-closed, Android dan Web Super Admin ditunda sampai Juni 2027.

## IMPLEMENTASI DOKUMENTASI
- Dibuat: `docs/PROMPT_AGENT_PENGGANTI_CBT_W2_FINAL_2026-11-24.md`
  - Commit: `708cc5f2ff44006fce1427986945616d110fdce1`
- Diperbarui: `docs/AGENT_START_HERE.md`
  - Blob sebelum update: `63082020675f1aed2decfdcfff08ab182e533417`
  - Commit update: `7ed7f3c3cbf77b62be3465561c9a64d74b9a97c6`
  - Blob sesudah update: `0606068570af09f6a2bcd3e6c12a417bd3c17200`
- Tidak ada source aplikasi, database, migration, file VPS, atau konfigurasi live yang diubah.
- Tidak ada test aplikasi dijalankan dalam tahap dokumentasi ini.

## PENGUJIAN / VERIFIKASI
- Verifikasi GitHub branch dan commit terbaru dilakukan sebelum pembuatan prompt.
- Prompt baru dibuat pada branch kerja yang benar.
- `AGENT_START_HERE.md` diperbarui untuk menunjuk prompt W2 final.
- Pengujian source tidak dijalankan karena perubahan hanya dokumentasi; hasil CI #1714 dicatat berdasarkan run/job logs yang sudah selesai, bukan diklaim sebagai pengujian baru.

## INSTRUKSI UNTUK AGENT BERIKUTNYA
Baca `docs/PROMPT_AGENT_PENGGANTI_CBT_W2_FINAL_2026-11-24.md` sebagai acuan utama. Verifikasi HEAD/Actions terkini, perbaiki query post-check InnoDB dengan backup workflow byte-exact, lalu langsung lanjutkan backlog fitur yang belum selesai. Jangan mengulang pekerjaan hijau atau meminta owner menjelaskan ulang spesifikasi VPS.

## STATUS
- Pembaruan prompt: **SELESAI**.
- Source aplikasi: **TIDAK DIUBAH**.
- Backlog fitur Web Admin/Guru/Siswa: **MASIH BERJALAN**.
- ZIP final: **BELUM DIBUAT / BELUM SIAP DISTRIBUSI**.
- Status produksi: **TIDAK DIKLAIM SIAP**.
