# CHECKPOINT CBT MADRASAH — 22 November 2026

## Posisi aktual

- Repository: `zane0184/cbt_november`.
- Stage4B/4C branch: `work/stage4b-room-session-20261122`.
  - HEAD: `4e663cd84038b7c3337d776f702c499f20d8f537`.
  - Source CI: `f1d01ea6fa4b9a100d00680a1b70c2a8d92b9af5`.
  - CI run `44984303003`: 35/35 steps PASS.
  - 4 suite target: 24 tests / 120 assertions PASS.
- Web Guru branch: `work/web-guru-foundation-20261122`.
  - HEAD: `25fe5e74a0754bcd110ceffac20141880c549ca8`.
  - Source CI: `e9c6867d40b134538e791bbbba02f5743afec7ea`.
  - CI run `44984094200`: 11 tests / 44 assertions PASS.
  - 49 PHPUnit doc-comment metadata deprecation warnings remain.

## Status per pekerjaan

| Area | Status |
|---|---|
| Stage4B CI/rollback/concurrency | PASS teknis |
| Stage4B design | BLOCKED: source memakai ExamSession, bertentangan dengan MODEL SERENTAK tanpa sesi |
| Stage4C Teacher Subject Assignment | PASS suite target |
| Admin CBT boundary | Core PASS; direct API/export coverage belum lengkap |
| Web Guru foundation | PASS suite target; fitur portal belum lengkap |
| Web Guru checkpoint | STALE; harus diperbarui dari 10277d ke 25fe5 dan mencatat run 449840 |
| Stage4C standalone checkpoint | STALE; hitungan 7 test harus diselaraskan menjadi 9 feature tests + 1 concurrency test |
| Branch integration | Belum merge; `routes/web.php` adalah file overlap A2/A3 |
| VPS/production | Tidak disentuh |
| Android | Tidak dikerjakan pada tahap ini |

## Keputusan yang wajib dipertahankan

- Ujian MODEL SERENTAK; tidak ada gelombang atau sesi pelaksanaan.
- Proktor bukan portal permanen; hanya penugasan sementara dan lingkup akses terbatas.
- Admin tidak boleh mengakses CBT authoring/operational data.
- Isolasi tenant, audit trail, idempotency, dan histori akademik wajib dipertahankan.
- Tidak ada migration produksi, merge, PR, deployment, atau distribusi tanpa persetujuan.

## Langkah berikutnya

1. Agent 1: buat desain pengganti Stage4B tanpa sesi; lengkapi tes negatif API/export/Livewire; belum patch sampai desain diaudit.
2. Agent 2: buat branch khusus Stage4D–4G dari HEAD `4e663cd...`; pertahankan Stage4C; jangan sentuh domain Stage4B atau file Teacher.
3. Agent 3: lanjutkan branch Web Guru; update checkpoint dan report; jangan edit `routes/web.php`; serahkan route snippet kepada Agent 2.
4. ChatGPT: audit hasil agent, review desain, periksa test log, dan siapkan handover.

## Lingkungan dan sumber

- `/mnt/data/CBT_MADRASAH_RECONSTRUCTION/` tidak ada pada runtime.
- ZIP reassembled baseline dan arsip workspace ditemukan di Library, tetapi raw-byte materialization ditolak oleh akses Project. Audit historis sebelumnya sudah mencatat baseline 16.080 entries dan CRC PASS.
- Tidak ada source atau database yang diubah pada sesi ini.

**Checkpoint status: LANJUT DARI POSISI INI; JANGAN RESET PROYEK.**