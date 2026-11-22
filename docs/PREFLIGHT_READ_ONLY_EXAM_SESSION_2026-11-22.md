# PREFLIGHT READ-ONLY DATA LAMA — EXAMSESSION OPERASIONAL
Tanggal: 22 November 2026
Repository: zane0184/cbt_november
Branch: work/stage4b-room-session-20261122
Status eksekusi: NOT RUN — tidak tersedia dump/disposable database yang dapat diakses

## 1. Ketersediaan sumber data
- Daftar file Project/Conversation yang diperiksa memuat dokumen audit/implementasi dan teks instruksi; tidak memuat .sql/.sql.gz/.dump/.sqlite atau ZIP baseline CBT.
- Workspace /mnt/data/CBT_MADRASAH_RECONSTRUCTION tidak tersedia pada runtime ini.
- Artifact CI 44984303003 diunduh dan diperiksa. Isinya log Composer/PHP, MySQL pre/postflight JSON, PHPUnit logs, migration rollback/restore logs; tidak ada database dump atau file database.
- Tidak ada koneksi ke database produksi dan tidak ada akses untuk mengekspor database produksi.
- Karena tidak ada salinan disposable yang terbukti berasal dari sumber asli, tidak dibuat data pengganti dan tidak ada angka agregat yang diklaim sebagai data proyek.
- Preflight database: NOT RUN. Produksi: SKIPPED / tidak disentuh.

## 2. Prinsip eksekusi
1. Gunakan salinan disposable dari backup/dump yang sudah diverifikasi hash dan tanggalnya.
2. Jalankan dengan akun read-only; pastikan host/schema bukan produksi.
3. Catat versi server, engine, SQL mode, timezone, charset, jumlah tabel, backup SHA-256, dan waktu snapshot.
4. Mulai dengan transaksi READ ONLY bila engine mendukung; jangan mengubah data.
5. Tidak menampilkan nama, NISN, email, jawaban, atau payload siswa. Laporan hanya agregat per tenant/Exam UUID untuk anomali internal.
6. Query harus menyertakan tenant_id dan join composite tenant/exam; jangan join hanya berdasarkan ID jika scope tersedia.
7. Simpan output query, checksum, dan log sebagai bukti preflight. Jangan menjalankan migration atau rekonsiliasi.

## 3. Inventaris read-only — SQL yang disiapkan

### 3.1 Jumlah Exam tanpa/satu/lebih dari satu session
~~~sql
SELECT
    e.tenant_id,
    COUNT(*) AS exams_total,
    SUM(CASE WHEN COALESCE(s.session_count, 0) = 0 THEN 1 ELSE 0 END) AS exams_without_session,
    SUM(CASE WHEN COALESCE(s.session_count, 0) = 1 THEN 1 ELSE 0 END) AS exams_with_one_session,
    SUM(CASE WHEN COALESCE(s.session_count, 0) > 1 THEN 1 ELSE 0 END) AS exams_with_multiple_sessions
FROM exams e
LEFT JOIN (
    SELECT tenant_id, exam_id, COUNT(*) AS session_count
    FROM exam_sessions
    GROUP BY tenant_id, exam_id
) s
    ON s.tenant_id = e.tenant_id
   AND s.exam_id = e.id
GROUP BY e.tenant_id
ORDER BY e.tenant_id;
~~~

### 3.2 Session menurut status dan ketidaksamaan jadwal Exam
~~~sql
SELECT
    es.tenant_id,
    es.status AS session_status,
    COUNT(*) AS session_count,
    SUM(CASE WHEN es.starts_at <> e.start_at OR es.ends_at <> e.end_at THEN 1 ELSE 0 END) AS schedule_mismatch_count
FROM exam_sessions es
JOIN exams e
  ON e.id = es.exam_id
 AND e.tenant_id = es.tenant_id
GROUP BY es.tenant_id, es.status
ORDER BY es.tenant_id, es.status;
~~~

Daftar anomali jadwal untuk rekonsiliasi internal:
~~~sql
SELECT
    es.tenant_id, es.exam_id, es.id AS session_id, es.status,
    es.starts_at AS session_starts_at, es.ends_at AS session_ends_at,
    e.start_at AS exam_starts_at, e.end_at AS exam_ends_at,
    e.status AS exam_status
FROM exam_sessions es
JOIN exams e
  ON e.id = es.exam_id
 AND e.tenant_id = es.tenant_id
WHERE es.starts_at <> e.start_at
   OR es.ends_at <> e.end_at
ORDER BY es.tenant_id, es.exam_id, es.id;
~~~

### 3.3 Room, mapping rombel, placement aktif dan histori per session
~~~sql
SELECT
    es.tenant_id, es.exam_id, es.id AS session_id, es.status AS session_status,
    COUNT(DISTINCT esr.id) AS room_rows,
    COUNT(DISTINCT CASE WHEN esr.status = 'ACTIVE' THEN esr.id END) AS active_rooms,
    COUNT(DISTINCT CASE WHEN esrr.status = 'ACTIVE' THEN esrr.id END) AS active_rombel_mappings,
    COUNT(DISTINCT esrr.id) AS rombel_mapping_history_rows,
    COUNT(DISTINCT CASE WHEN epra.status = 'ACTIVE' THEN epra.id END) AS active_participant_placements,
    COUNT(DISTINCT epra.id) AS participant_placement_history_rows
FROM exam_sessions es
LEFT JOIN exam_session_rooms esr
  ON esr.tenant_id = es.tenant_id
 AND esr.exam_id = es.exam_id
 AND esr.exam_session_id = es.id
LEFT JOIN exam_session_room_rombels esrr
  ON esrr.tenant_id = esr.tenant_id
 AND esrr.exam_id = esr.exam_id
 AND esrr.exam_session_room_id = esr.id
LEFT JOIN exam_participant_room_assignments epra
  ON epra.tenant_id = esr.tenant_id
 AND epra.exam_id = esr.exam_id
 AND epra.exam_session_room_id = esr.id
GROUP BY es.tenant_id, es.exam_id, es.id, es.status
ORDER BY es.tenant_id, es.exam_id, es.id;
~~~

### 3.4 Assignment Proktor per room/session dan status
~~~sql
SELECT
    epa.tenant_id, epa.exam_id,
    esr.exam_session_id AS session_id,
    epa.exam_session_room_id AS session_room_id,
    epa.status AS assignment_status,
    COUNT(*) AS assignment_count,
    SUM(CASE WHEN epa.revoked_at IS NOT NULL THEN 1 ELSE 0 END) AS revoked_timestamp_count
FROM exam_proctor_assignments epa
LEFT JOIN exam_session_rooms esr
  ON esr.id = epa.exam_session_room_id
 AND esr.tenant_id = epa.tenant_id
 AND esr.exam_id = epa.exam_id
GROUP BY epa.tenant_id, epa.exam_id, esr.exam_session_id,
         epa.exam_session_room_id, epa.status
ORDER BY epa.tenant_id, epa.exam_id, esr.exam_session_id,
         epa.exam_session_room_id, epa.status;
~~~

Legacy assignment tanpa room scope harus dilaporkan terpisah:
~~~sql
SELECT tenant_id, exam_id, status, COUNT(*) AS legacy_assignment_count
FROM exam_proctor_assignments
WHERE exam_session_room_id IS NULL
GROUP BY tenant_id, exam_id, status
ORDER BY tenant_id, exam_id, status;
~~~

### 3.5 Attempt IN_PROGRESS, Answer, Score, current-score pointer
~~~sql
SELECT
    e.tenant_id,
    e.id AS exam_id,
    COUNT(DISTINCT a.id) AS attempt_count,
    COUNT(DISTINCT CASE WHEN a.status = 'IN_PROGRESS' THEN a.id END) AS attempts_in_progress,
    COUNT(DISTINCT aa.id) AS answer_rows,
    COUNT(DISTINCT sc.id) AS score_version_rows,
    COUNT(DISTINCT qs.id) AS question_score_rows,
    COUNT(DISTINCT scp.attempt_id) AS score_current_pointer_rows
FROM exams e
LEFT JOIN exam_attempts a
  ON a.tenant_id = e.tenant_id
 AND a.exam_id = e.id
LEFT JOIN exam_attempt_answers aa
  ON aa.tenant_id = a.tenant_id
 AND aa.exam_id = a.exam_id
 AND aa.exam_attempt_id = a.id
LEFT JOIN exam_attempt_scores sc
  ON sc.tenant_id = a.tenant_id
 AND sc.exam_id = a.exam_id
 AND sc.attempt_id = a.id
LEFT JOIN exam_question_scores qs
  ON qs.tenant_id = a.tenant_id
 AND qs.exam_id = a.exam_id
 AND qs.attempt_id = a.id
LEFT JOIN exam_attempt_score_current scp
  ON scp.tenant_id = a.tenant_id
 AND scp.attempt_id = a.id
GROUP BY e.tenant_id, e.id
ORDER BY e.tenant_id, e.id;
~~~

Catatan: query di atas adalah baseline agregat dan dapat menghasilkan penggandaan antar-relasi one-to-many yang sudah dikurangi dengan COUNT(DISTINCT). Sebelum eksekusi, validasi nama kolom dan FK pada dump aktual; untuk angka performa yang besar, gunakan subquery agregat terpisah per tabel agar lebih efisien. Tidak ada isi answer_payload atau data siswa yang dipilih.

### 3.6 Tenant/FK anomaly checks
Session -> Exam:
~~~sql
SELECT COUNT(*) AS orphan_or_cross_tenant_sessions
FROM exam_sessions es
LEFT JOIN exams e
  ON e.id = es.exam_id
 AND e.tenant_id = es.tenant_id
WHERE e.id IS NULL;
~~~

Session-room -> session:
~~~sql
SELECT COUNT(*) AS orphan_or_cross_tenant_session_rooms
FROM exam_session_rooms esr
LEFT JOIN exam_sessions es
  ON es.id = esr.exam_session_id
 AND es.tenant_id = esr.tenant_id
 AND es.exam_id = esr.exam_id
WHERE es.id IS NULL;
~~~

Participant placement -> participant dan session-room:
~~~sql
SELECT COUNT(*) AS invalid_participant_room_assignments
FROM exam_participant_room_assignments epra
LEFT JOIN exam_participants ep
  ON ep.id = epra.participant_id
 AND ep.tenant_id = epra.tenant_id
 AND ep.exam_id = epra.exam_id
LEFT JOIN exam_session_rooms esr
  ON esr.id = epra.exam_session_room_id
 AND esr.tenant_id = epra.tenant_id
 AND esr.exam_id = epra.exam_id
WHERE ep.id IS NULL OR esr.id IS NULL;
~~~

Proctor assignment -> session-room:
~~~sql
SELECT COUNT(*) AS invalid_nonlegacy_proctor_room_assignments
FROM exam_proctor_assignments epa
LEFT JOIN exam_session_rooms esr
  ON esr.id = epa.exam_session_room_id
 AND esr.tenant_id = epa.tenant_id
 AND esr.exam_id = epa.exam_id
WHERE epa.exam_session_room_id IS NOT NULL
  AND esr.id IS NULL;
~~~

Answer -> Attempt:
~~~sql
SELECT COUNT(*) AS invalid_answer_attempt_links
FROM exam_attempt_answers aa
LEFT JOIN exam_attempts a
  ON a.id = aa.exam_attempt_id
 AND a.tenant_id = aa.tenant_id
 AND a.exam_id = aa.exam_id
WHERE a.id IS NULL;
~~~

Score -> Attempt:
~~~sql
SELECT COUNT(*) AS invalid_score_attempt_links
FROM exam_attempt_scores sc
LEFT JOIN exam_attempts a
  ON a.id = sc.attempt_id
 AND a.tenant_id = sc.tenant_id
 AND a.exam_id = sc.exam_id
WHERE a.id IS NULL;
~~~

Current score pointer -> Attempt/Score:
~~~sql
SELECT COUNT(*) AS invalid_score_current_pointers
FROM exam_attempt_score_current p
LEFT JOIN exam_attempts a
  ON a.id = p.attempt_id
 AND a.tenant_id = p.tenant_id
LEFT JOIN exam_attempt_scores sc
  ON sc.id = p.current_score_id
 AND sc.attempt_id = p.attempt_id
 AND sc.tenant_id = p.tenant_id
 AND sc.scoring_version = p.scoring_version
WHERE a.id IS NULL OR sc.id IS NULL;
~~~

## 4. Anomali yang wajib ditandai
- Exam dengan nol session operasional.
- Exam dengan lebih dari satu session.
- Session window berbeda dari Exam.
- Exam CLOSED tetapi session SCHEDULED/ACTIVE.
- Session CANCELLED sementara Exam masih PUBLISHED.
- Session SCHEDULED/ACTIVE tanpa room.
- Room fisik yang overlap lintas Exam.
- Rombel aktif dipetakan ke lebih dari satu session-room dalam Exam yang sama.
- Peserta mempunyai lebih dari satu placement ACTIVE dalam Exam yang sama.
- Proktor memiliki assignment aktif tumpang tindih.
- Assignment Proktor CONFIRMED/NEEDS_REVIEW pada session yang akan non-operational.
- Attempt IN_PROGRESS pada lebih dari satu session untuk Exam yang sama.
- Tenant/exam/session-room composite mismatch.
- Answer/Score/current pointer yang tidak memiliki parent Attempt yang sesuai.

## 5. Aturan pemilihan canonical dan rekonsiliasi
1. Tidak ada pemilihan otomatis.
2. Jika hanya satu session, pertahankan ID session tersebut.
3. Jika beberapa session, siapkan manifest per Exam dan tinjau status, room, mapping, placement, assignment, Attempt, serta lifecycle.
4. Jika ada Attempt IN_PROGRESS pada lebih dari satu session, tunda seluruh cutover Exam sampai Attempt aman selesai. Jangan cabut monitoring berjalan.
5. Pertahankan ID canonical session. Session lain ditandai non-operasional, tidak dihapus.
6. Buat/reuse session-room pada canonical untuk setiap ruang yang diperlukan; jangan mengganti ID room-session yang masih punya histori.
7. Placement/mapping aktif lama dilepas dengan alasan RECONCILIATION, lalu dibuat placement aktif baru pada canonical. Histori lama tetap ada.
8. Assignment Proktor lama dicabut atau NEEDS_REVIEW; assignment baru dibuat dan dikonfirmasi ulang secara eksplisit.
9. Tidak mengubah Attempt, Answer, Score, score-current pointer, atau question score.
10. Setiap Exam direkonsiliasi dalam transaction tersendiri dengan journal before/after; batch hanya lanjut setelah invariant dan count/hash diverifikasi.
11. Rollback transaction jika belum commit. Setelah commit, gunakan compensation dari journal, bukan hard delete.

## 6. Test matrix implementasi yang akan datang
Semua test di bawah belum dijalankan pada desain ini.

| Test | Hasil yang diharapkan |
|---|---|
| Buat session pertama untuk Exam published/locked | PASS: satu row is_operational=1 |
| Buat session operasional kedua | PASS: ditolak |
| Dua request bersamaan membuat session | PASS: tepat satu berhasil; satu conflict; satu row operational |
| Unique MySQL generated guard | PASS: duplikat ditolak; banyak histori is_operational=0 diterima |
| SQLite partial unique | PASS: duplikat ditolak; banyak histori is_operational=0 diterima |
| Session mengambil jadwal Exam | PASS: timestamp sama persis |
| Jadwal session independen | PASS: ditolak di service dan UI tidak menyediakan aksi |
| Update DRAFT vs publish race | PASS: update tidak dapat menulis setelah locked |
| Perubahan jadwal Exam setelah publish | PASS: ditolak pada jalur biasa |
| Semua ruang dalam satu session | PASS |
| Dua Proktor pada dua ruang berbeda | PASS |
| Masa tugas Proktor berbeda | PASS: tidak mengubah Exam/Attempt schedule |
| Proktor A mencoba ruang B | PASS: ditolak |
| Monitoring lintas tenant | PASS: ditolak |
| Scheduler sebelum start | PASS: SCHEDULED |
| Scheduler pada Exam.start_at | PASS: ACTIVE |
| Scheduler pada Exam.end_at | PASS: COMPLETED setelah attempt aman |
| Exam CLOSED tanpa Attempt aktif | PASS: session/assignment ditutup atomik |
| Exam CLOSED dengan Attempt aktif | PASS: resume sah; monitoring tidak dicabut prematur |
| Admin cancel session operasional | PASS: ditolak |
| Pemindahan placement sebelum ujian | PASS: histori lama RELEASED dan row baru ACTIVE |
| Pemindahan saat Attempt IN_PROGRESS | PASS: ditolak kecuali workflow terotorisasi |
| Assignment terdampak scope change | PASS: NEEDS_REVIEW dan konfirmasi ulang |
| Foreign key dan tenant isolation | PASS |
| Migration rollback disposable | PASS: child-first; FK aktif; data dasar tetap |
| Attempt/Answer/Score count before-after | PASS: identik |
| Legacy duplicates reconciliation | PASS pada fixture disposable; no history loss |

## 7. Status
- Preflight database: NOT RUN.
- SQL di atas: DISIAPKAN, belum dieksekusi.
- Tidak ada database produksi/disposable yang diakses.
- Tidak ada migration atau rekonsiliasi.
- Gate berikutnya: salinan database disposable terverifikasi dan persetujuan eksplisit untuk tahap implementasi.
