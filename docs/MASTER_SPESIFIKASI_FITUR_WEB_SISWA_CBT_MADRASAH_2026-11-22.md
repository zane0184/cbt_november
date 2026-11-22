# MASTER SPESIFIKASI FITUR WEB SISWA CBT MADRASAH
Tanggal: 22 November 2026
Repository: zane0184/cbt_november
Dokumen ini disusun untuk handover kepada Agent pengganti yang khusus menangani Web Siswa.
Status dokumen: MASTER PRODUCT + SOURCE AUDIT + IMPLEMENTATION HANDOVER.
Status produk saat audit: WEB SISWA NOT READY FOR OPERATIONAL EXAM.
Status implementasi dokumen: DOCUMENTATION ONLY — tidak mengubah source runtime.

## 1. TUJUAN DAN CARA MEMBACA DOKUMEN

Dokumen ini menjelaskan seluruh pengalaman Siswa yang harus tersedia pada Web Siswa, membedakan:
1. Perilaku yang benar-benar ditemukan pada source yang diperiksa.
2. Keputusan produk yang wajib dipertahankan.
3. Fitur yang belum ada atau belum lengkap.
4. Risiko keamanan/integritas yang menghalangi pemakaian operasional.
5. Acceptance test yang harus lulus sebelum fitur dinyatakan siap.

Istilah status:
- ADA DI SOURCE: route, view, controller, service, atau API ditemukan.
- ADA — BELUM TERUJI: implementasi ditemukan tetapi belum ada bukti runtime/browser/CI pada SHA yang sama.
- PARSIAL: sebagian alur ada, tetapi belum memenuhi kontrak produk.
- BLOCKED: tidak boleh diaktifkan untuk ujian operasional sebelum dependency/keamanan ditutup.
- BELUM ADA: tidak ditemukan sebagai fitur Web Siswa pada source yang diperiksa.
- KEPUTUSAN PRODUK: perilaku wajib, sekalipun source sekarang belum memenuhinya.
- DEFERRED: sengaja ditunda dari scope sekarang.

Jangan mengubah label ADA DI SOURCE menjadi SIAP hanya karena route, tombol, atau test method ditemukan. Jumlah metode test statis bukan hasil pengujian.

## 2. KEPUTUSAN PRODUK FINAL — WAJIB DIPERTAHANKAN

### 2.1 Scope platform sampai Juni 2027
- Fokus aktif: Web Admin Madrasah, Web Guru, dan Web Siswa.
- Android native, Android Studio, Android API/client, APK/AAB, dan Web Super Admin ditunda sampai Juni 2027; bukan blocker penyelesaian Web CBT pada tahap sekarang.
- Web Siswa saat ini harus dapat diakses melalui browser pada perangkat yang menjadi target fase Web. Pengalaman wajib responsive dan mobile-first, tanpa mengorbankan desktop.
- Keputusan jangka panjang yang telah ditetapkan: setelah fase Android dimulai, peserta Android wajib memakai aplikasi Android native, bukan browser/PWA untuk ujian Android. Peserta iOS sementara dapat menggunakan Web CBT karena belum ada pipeline pengembangan/pengujian iOS.
- Web Siswa bukan secure kiosk/exam browser. Jangan menjanjikan browser dapat mencegah screenshot, screen recording, berpindah aplikasi, membuka Home, atau memutus akses sistem operasi.

### 2.2 Portal, role dan identitas
- Portal operasional hanya ADMIN_MADRASAH, GURU, SISWA.
- Siswa hanya peserta ujian, bukan Guru, Admin, atau Proktor.
- Hanya akun dengan tepat satu role SISWA yang boleh memakai seluruh Student Web/API/policy/service.
- Role campuran SISWA+GURU, SISWA+ADMIN_MADRASAH, SISWA+KAMAD, SISWA+role legacy, role kosong, role tidak dikenal, akun nonaktif, dan akun soft-deleted wajib ditolak.
- KAMAD dibekukan dari seluruh akses/capability CBT. Histori administratif tidak dihapus, tetapi tidak boleh menjadi jalur otorisasi.
- Proktor bukan role permanen dan tidak memiliki portal/login sendiri. Proktor adalah akun Guru dengan penugasan sementara per Exam+Room yang dikelola Admin dan menunya berada di Web Guru.
- Tidak boleh ada menu, route, action, API client, atau capability Proktor di Web Siswa. Guru Proktor tidak boleh memakai Student API sebagai peserta.

### 2.3 Instalasi dan isolasi data
- Satu ZIP = satu instalasi = satu madrasah = satu database akademik sendiri.
- Tidak ada shared academic database dan tidak ada sinkronisasi akademik lintas instalasi.
- Database, kredensial, konfigurasi, domain, session, cache, queue, storage, log, export, file sementara dan backup harus terisolasi per instalasi.
- tenant_id tetap menjadi defense-in-depth pada seluruh query/resource; tenant header/selector dari browser bukan sumber otoritas.
- Web Siswa hanya melihat identitas, peserta, ujian, Attempt, jawaban, dan hasil dari instalasinya sendiri.

### 2.4 Model ujian serentak — koreksi wajib
- Ujian menggunakan MODEL SERENTAK.
- Satu Exam adalah satu pelaksanaan ujian serentak.
- Jadwal resmi berada pada Exam.start_at dan Exam.end_at; waktu server adalah acuan.
- Tidak ada gelombang, nomor sesi, ExamSession, SessionRoom, atau lifecycle sesi operasional untuk membuat beberapa gelombang pelaksanaan.
- Room hanya untuk penempatan peserta dan cakupan monitoring Proktor. Room bukan admission gate untuk memulai atau melanjutkan Attempt.
- Jangan mengikuti bagian dokumen Agent 4 historis yang masih menyebut satu Exam = satu ExamSession operasional; bagian itu sudah dikoreksi oleh keputusan terbaru koordinator pada Issue #10 komentar 7081422150 dan 7081440808.
- Attempt IN_PROGRESS yang masih sah, belum expired, pemiliknya benar, peserta masih eligible, dan device/security state valid dapat melanjutkan Attempt yang sama setelah Exam ditutup. Tidak boleh membuat Attempt baru setelah jadwal tutup.

### 2.5 Token dan waktu
- Token dibuat otomatis ketika Guru menerbitkan/publish ujian.
- Token dapat dilihat Guru sebelum jadwal dimulai.
- Siswa hanya memasukkan token jika Exam mensyaratkannya; Web Siswa tidak menampilkan token rahasia.
- Token yang benar tidak mengizinkan siswa memulai sebelum jadwal.
- Waktu server menentukan admission, start, expiry, dan status terminal. Jam perangkat hanya untuk indikator visual.
- Durasi Attempt dibatasi oleh durasi Exam, Exam.end_at, dan eligible_until peserta jika ada.

### 2.6 Kamera dan pengawasan
- Kamera bukan syarat CBT standar.
- Tidak ada kewajiban webcam, foto, atau video pengawasan sebagai prasyarat ujian.
- Pengawasan Proktor adalah fitur terpisah di Web Guru, berdasarkan assignment dan Room. Tidak boleh dipindahkan ke Web Siswa.

## 3. PETA FITUR WEB SISWA — PENGALAMAN PENGGUNA

### 3.1 Halaman login Siswa
Tujuan: Siswa masuk ke instalasi CBT madrasah yang benar dengan identitas yang sudah didaftarkan Admin.

Perilaku yang ditemukan:
- Form login bersama ada di /login.
- Field identitas bersama menerima Username/NIP/NIK/NISN, tetapi teks petunjuk menyatakan Siswa menggunakan NISN.
- Siswa login menggunakan NISN dan password; email tidak diwajibkan.
- Password dapat ditampilkan/disembunyikan.
- Ada opsi Ingat Saya di Perangkat Ini.
- Ada validasi dan pesan kesalahan autentikasi generik.
- Ada rate limit 5 percobaan per key dalam 60 detik pada controller yang diperiksa.
- Login sukses untuk role SISWA diarahkan ke /student/exams.
- Logout mengakhiri sesi dan mengarahkan ke /login.

Ketentuan keamanan:
- Login siswa harus mencari NISN di StudentProfile tenant instalasi yang tepat.
- NISN sama pada dua instalasi tidak boleh membuat sistem menebak madrasah; instalasi harus memiliki satu LocalInstallationIdentity dan satu tenant yang konsisten.
- Password salah dan identitas tidak ditemukan harus menghasilkan pesan generik agar tidak menjadi enumerasi akun.
- Akun dinonaktifkan/soft-deleted tidak boleh dapat login.
- Sesi lama, remember-me cookie, dan Sanctum token harus invalid setelah deactivation/reactivation; login baru diperlukan untuk memperoleh sesi baru.
- Semua Student entrypoint tetap wajib exact-one SISWA. Login exact-one saja tidak cukup.

Temuan source P0:
- LoginIdentityResolver memeriksa tepat satu role, tetapi daftar role yang diizinkan masih memasukkan KAMAD.
- AuthWebController juga masih memasukkan KAMAD dalam allow-list dan redirectForUser memiliki jalur KAMAD ke root.
- Ini melanggar keputusan KAMAD frozen. Owner backend (Agent 2) harus menutupnya; Agent Web Siswa tidak boleh membuat bypass di sisi UI.

Status: ADA DI SOURCE — BELUM TERUJI; role/KAMAD P0 BLOCKED.

### 3.2 Dashboard / Ujian Saya
Tujuan: menampilkan seluruh ujian yang memang ditugaskan kepada Siswa dan status yang dapat dipahami.

Perilaku yang ditemukan:
- Route GET /student/exams.
- Halaman berjudul Ujian Saya.
- Menampilkan sapaan dan nama Siswa.
- Jika belum ada tugas, menampilkan keadaan kosong dan instruksi menghubungi Guru/Admin.
- Setiap kartu ujian menampilkan judul, jadwal mulai, jadwal selesai, durasi, jumlah percobaan, batas percobaan, dan status.
- Status yang dihitung pada controller antara lain:
  - Sedang dikerjakan
  - Selesai
  - Belum diterbitkan
  - Di luar waktu akses
  - Jadwal belum lengkap
  - Terjadwal
  - Ditutup
  - Batas percobaan habis
  - Siap dikerjakan
- Jika ada Attempt aktif yang masih eligible dan belum expired, tombol menjadi Lanjutkan Ujian.
- Jika ujian dapat dimulai, tombol menjadi Buka Ujian.
- Jika hasil sudah tersedia, tombol menjadi Lihat Hasil Ujian.
- Jika tidak memenuhi syarat, tombol dinonaktifkan.

Definisi daftar ujian:
- Data bersumber dari ExamParticipant milik user saat ini pada tenant yang sama.
- Ujian tanpa Exam terkait tidak ditampilkan.
- Waktu eligibility peserta berasal dari eligible_from dan eligible_until.
- Batas Attempt menggunakan attempt_limit_override peserta jika ada; jika tidak memakai attempt_limit Exam.
- Hasil hanya ditandai tersedia jika Exam.show_result aktif, Attempt berstatus SUBMITTED atau EXPIRED, dan current score berstatus FINALIZED.
- Daftar yang ditemukan menampilkan ringkasan Attempt terbaru dan jumlah percobaan; bukan riwayat detail seluruh Attempt.

Hal yang belum ditemukan pada dashboard:
- Tidak ada kartu profil akademik Siswa.
- Tidak ada kelas/Rombel/tahun pelajaran yang ditampilkan.
- Tidak ada ringkasan mata pelajaran, Guru, kehadiran, tugas pembelajaran, atau rapor.
- Tidak ada notifikasi terpusat/pengumuman Siswa yang terbukti pada halaman ini.
- Tidak ada kalender akademik terpisah.
- Tidak ada pengaturan akun mandiri.

Status: ADA DI SOURCE — BELUM TERUJI. Dashboard saat ini adalah daftar ujian, bukan dashboard akademik lengkap.

### 3.3 Detail ujian dan instruksi sebelum mulai
Tujuan: Siswa membaca aturan, memastikan jadwal, durasi, token, dan kelayakan sebelum membuat Attempt.

Perilaku yang ditemukan:
- Route GET /student/exams/{exam}.
- Menampilkan nama peserta, judul ujian, durasi, instruksi, jadwal mulai, jadwal selesai, jumlah/batas percobaan, dan apakah token diperlukan.
- Jika instruksi kosong, tampil fallback instruksi umum.
- Jika eligibility di luar waktu, halaman memberi peringatan.
- Jika ujian belum dapat dimulai atau limit habis, halaman memberi penjelasan.
- Jika token diwajibkan, tersedia input Token Ujian.
- Tombol Mulai Ujian atau Lanjutkan Ujian.
- Ada tombol kembali ke Daftar Ujian.
- Status awal menjelaskan bahwa waktu resmi mengikuti server.

Ketentuan produk:
- Siswa boleh melihat metadata/instruksi yang memang boleh dilihat sebelum ujian.
- Siswa tidak boleh menerima snapshot soal, opsi, jawaban benar, accepted answers, rubric, scoring guide, explanation privat, atau data peserta lain sebelum admission yang sah.
- Token bukan pengganti jadwal dan eligibility.
- Room tidak menjadi syarat admission; siswa tidak boleh ditolak memulai hanya karena Room assignment belum menjadi gate.
- Attempt aktif yang sah harus dilanjutkan sebagai Attempt yang sama.

Status: ADA DI SOURCE — BELUM TERUJI; F-01 admission/snapshot release BLOCKED.

### 3.4 Mulai ujian / admission
Alur yang diharapkan:
1. Siswa menekan Mulai Ujian.
2. Browser mengirim permintaan start ke backend.
3. Backend memeriksa exact-one role SISWA, akun aktif, tenant/instalasi, Exam, ExamParticipant, eligibility window, jadwal server, token bila diwajibkan, attempt limit, status Exam, serta state Attempt.
4. Backend membuat satu Attempt baru secara transaksional jika semua syarat terpenuhi.
5. Backend mengikat Attempt ke identitas peserta dan device/security state sesuai kontrak.
6. Backend mengembalikan Attempt dan execution context yang hanya berisi data yang boleh dilihat Siswa.
7. Jika Attempt yang sama masih IN_PROGRESS dan sah, backend mengembalikan Attempt yang sama untuk resume; tidak membuat duplikat.
8. Jika Exam sudah ditutup dan tidak ada Attempt aktif yang sah, Attempt baru ditolak.

Perilaku source yang ditemukan:
- Browser memanggil POST /api/v1/exams/{exam}/attempts.
- Jika Exam memerlukan token, token dikirim pada body.
- Header X-Device-Binding-ID dikirim dari browser.
- API menampilkan pesan 409 bila Attempt aktif ditemukan pada browser/perangkat lain.
- Attempt memiliki attempt_number, status, started_at, expires_at, device_fingerprint, question_order dan options_order.
- Source Attempt controller mengunci Exam → Participant → Attempt untuk lifecycle start/resume/submit yang diperiksa.
- Attempt aktif pada device binding yang sama dapat di-resume sebelum validasi token baru; detail aturan token resume harus ditetapkan backend.

Blocker:
- ExamController::show pada source yang diperiksa memuat snapshot berdasarkan canStartAttempt() dan sanitasi field sensitif, tetapi belum membuktikan admission lengkap berdasarkan token/Attempt/device/lease/fencing. Sanitasi answer key tidak sama dengan admission aman.
- ExamPolicy::startAttempt menggunakan hasRole('SISWA'), bukan exact-one SISWA.
- StudentExamController juga memakai hasRole('SISWA') dan hanya mengecualikan ADMIN_MADRASAH; role campuran SISWA+GURU atau SISWA+KAMAD masih berisiko melewati predicate.
- Device Lease/fencing end-to-end belum terbukti.

Status: PARSIAL — P0 BLOCKED sampai F-01, exact-one SISWA, dan device/lease/fencing ditutup.

### 3.5 Runner / ruang pengerjaan ujian
Tujuan: menyediakan layar ujian yang sederhana, fokus, mudah digunakan, dan tidak membingungkan Siswa.

Komponen UI yang ditemukan:
- Header judul ujian dan tombol Daftar Ujian.
- Panel sticky berisi Sisa waktu, status penyimpanan, dan progres.
- Area soal menampilkan nomor soal, total soal, poin, media, teks soal, dan kontrol jawaban.
- Tombol Sebelumnya dan Berikutnya.
- Navigasi nomor soal yang dapat dipilih langsung.
- Tombol Kirim Ujian.
- Panel selesai setelah server menerima submit menurut respons yang diterima client.
- Area error untuk menampilkan kegagalan.

Ketentuan UX:
- Fokus pada soal, jawaban, timer, status simpan, navigasi, dan submit.
- Jangan menampilkan jawaban benar, kunci, accepted answers, rubric, scoring guide, atau explanation internal.
- Siswa harus dapat berpindah soal tanpa kehilangan jawaban.
- Status jawaban harus membedakan tersimpan lokal, menunggu sinkronisasi, sedang dikirim, diterima server (receipt terverifikasi), konflik, dan gagal.
- Jangan memakai label Tersimpan jika yang diketahui hanya tersimpan di memory browser.
- Jangan menampilkan hijau Server accepted tanpa bukti receipt yang diverifikasi.
- Tombol submit harus memberi konfirmasi sebelum pengiriman manual.
- UI harus mencegah double submit dan tidak menutup halaman jika hasil submit belum diketahui.

Status: UI dasar ADA DI SOURCE; keamanan/persistensi/submit barrier PARSIAL dan BLOCKED.

### 3.6 Timer dan batas waktu
Perilaku source:
- Runner menerima server_now dari execution response.
- Timer memperkirakan waktu server dengan serverEpoch + selisih performance.now(), bukan Date.now() perangkat sebagai otoritas.
- Sisa waktu dihitung dari Attempt.expires_at.
- UI memperbarui timer setiap detik.
- Ketika sisa waktu nol, runner menjalankan auto-submit.

Ketentuan:
- Server adalah sumber kebenaran expiry dan status Attempt.
- Waktu lokal hanya indikator. Perubahan jam perangkat tidak boleh memperpanjang ujian.
- Attempt expiry harus serial dengan save answer dan submit.
- Attempt expiry dibatasi oleh durasi Exam, Exam.end_at, dan eligible_until peserta.
- Jika waktu habis, UI tidak boleh mengklaim semua jawaban tersimpan jika masih ada request/pending/conflict.
- Resume hanya untuk Attempt yang sama, IN_PROGRESS, belum expired, masih eligible, dan device/security state sah.
- Attempt baru setelah Exam.end_at ditolak.

Temuan risiko:
- Source runner saat ini tetap mencoba submit otomatis meskipun flush jawaban gagal pada jalur expiry. Pesan completion mengakui jawaban terakhir mungkin tidak tercatat. Ini belum memenuhi submit barrier.
Status: timer dasar ADA; expiry/submit race BLOCKED.

### 3.7 Tipe soal dan kontrol jawaban
Enam tipe yang ditetapkan produk:
1. MULTIPLE_CHOICE — satu pilihan; kontrol radio.
2. MULTIPLE_RESPONSE — lebih dari satu pilihan; kontrol checkbox.
3. TRUE_FALSE — pilihan benar/salah; kontrol radio sesuai option snapshot.
4. MATCHING — memasangkan item kiri dengan item kanan; kontrol select per item kiri.
5. SHORT_ANSWER — jawaban singkat teks; tetap BLOCKED sampai accepted answers tersimpan pada QuestionVersion/snapshot immutable dan scoring teruji.
6. ESSAY — jawaban uraian teks; penilaian manual oleh Guru yang berwenang.

Perilaku source renderer:
- MC, MULTIPLE_CHOICE, TRUE_FALSE dirender sebagai radio.
- MULTIPLE_RESPONSE dirender sebagai checkbox.
- MATCHING membaca option_key LEFT dan matching_key RIGHT; jika data pasangan tidak lengkap, UI menampilkan peringatan.
- SHORT_ANSWER dan ESSAY saat ini sama-sama dirender sebagai textarea.
- Tipe lain menampilkan pesan belum didukung.

Keputusan:
- SHORT_ANSWER tidak boleh aktif di ujian operasional hanya karena textarea ada. Backend saat ini belum membuktikan accepted_answers dari authoring → QuestionVersion → ExamSnapshot → ScoringService.
- ESSAY disimpan sebagai jawaban teks dan memerlukan manual review; jangan menganggap auto-score final.
- Tidak boleh menambah tipe soal baru atau mengubah schema payload tanpa kontrak Agent 2/3.
- Option ID/identitas pasangan harus stabil dan terikat pada snapshot; kunci benar tidak boleh dikirim ke browser.
- Payload answer divalidasi server sesuai tipe dan snapshot yang ditugaskan.

Status:
- MC/MULTIPLE_CHOICE/TRUE_FALSE/MULTIPLE_RESPONSE/MATCHING: ADA DI SOURCE, payload dan renderer perlu integrasi test.
- SHORT_ANSWER: BLOCKED.
- ESSAY: renderer ada, manual review workflow harus dibuktikan.

### 3.8 Media soal
Perilaku renderer yang ditemukan:
- Image ditampilkan sebagai gambar.
- Audio ditampilkan dengan kontrol audio.
- Video ditampilkan dengan kontrol video.
- PDF ditampilkan sebagai tautan untuk membuka lampiran.
- Media URL dibentuk menuju GET /api/v1/media/{media_id}.
- Gambar memakai lazy loading; audio/video memakai preload terbatas.

Ketentuan:
- Hanya media yang dirujuk snapshot soal pada Attempt milik Siswa yang boleh diakses.
- Media harus berada pada instalasi/tenant yang sama.
- Media harus terikat ke snapshot yang termasuk question_order Attempt.
- Media tidak boleh dapat diakses hanya dengan menebak UUID.
- Storage path, nama internal, file tenant lain, atau media soal lain tidak boleh bocor.
- Validasi device, lease, expiry/revoke dan fencing harus mengikuti kontrak Agent 2.
- Media historis yang digunakan snapshot tidak boleh rusak karena file sumber diubah/dihapus.
- Jika file hilang/corrupt, tampilkan fallback yang jelas; jangan mengganti media diam-diam.
- Alt text/caption/transcript, ukuran file, MIME dan aksesibilitas perlu acceptance test.

Temuan risiko F-02:
- MediaController memeriksa tenant, owner dan relasi media dengan Attempt aktif milik Siswa.
- Source yang diperiksa belum membuktikan media snapshot berada dalam question_order Attempt dan belum membuktikan registered device, lease token, lease expiry/revoke serta fencing generation.
Status: media rendering ADA; authorization F-02 BLOCKED.

### 3.9 Autosave / penyimpanan jawaban
Perilaku source:
- Saat Siswa mengubah jawaban, state di browser diperbarui.
- Save dijadwalkan dengan debounce sekitar 500 ms.
- Request PUT memakai client_request_id, base_revision, answer_payload dan answer_state.
- Save per soal diserialkan melalui Promise chain di browser.
- Backend mengembalikan revision_number.
- Saat runner dimuat, browser meminta daftar jawaban dari server dan membangun ulang jawaban yang sudah tersimpan di server.
- Ada listener online yang mencoba mengirim ulang pending save saat jaringan kembali.
- Answer API memeriksa ownership Attempt, tenant, question_order, snapshot, device binding, status Attempt dan expiry.
- Answer API mengembalikan HTTP 409/current_revision/current_answer saat revision conflict pada source yang diperiksa.
- Model jawaban menyimpan exam_id, exam_attempt_id, exam_question_snapshot_id, student_id, answer_payload, answer_state, revision_number, client_request_id, answered_at dan saved_at.

Risiko source:
- State answers/pending/saving/revisions saat ini memakai JavaScript Map di memory, bukan IndexedDB.
- Reload/tab crash dapat menghilangkan jawaban yang belum diterima server.
- Pada HTTP 409, runner menghapus pending item dan mengganti local state dengan current_answer dari server; versi lokal yang belum diterima dapat hilang.
- client_request_id berada pada row answer; belum ada receipt terpisah yang dapat diverifikasi browser.
- Retry dengan request ID sama dan payload berbeda belum terbukti ditolak secara aman.
- Respons hilang setelah server commit belum memiliki receipt/lookup contract final.

Ketentuan:
- Jawaban harus disimpan lokal secara durable sebelum dikirim.
- Request retry harus menggunakan ID dan payload yang sama.
- Local answer tidak boleh dihapus hanya karena request dikirim.
- HTTP 409 harus mempertahankan kedua versi dalam conflict archive; tidak boleh silent overwrite.
- Server accepted hanya setelah receipt terverifikasi.
- IndexedDB tidak menggantikan backend/lease/fencing.
Status: PARSIAL — autosave server API ada; durable recovery dan conflict safety BLOCKED.

### 3.10 Offline, reload, crash dan multi-tab recovery
Keputusan desain Agent 4 (belum diimplementasikan):
Enam object store konseptual:
- attempt_context: scope User/Tenant/Exam/Attempt/device.
- answers: jawaban lokal, local revision, server revision, status.
- sync_queue: antrean request dengan ID/payload immutable.
- server_receipts: bukti penerimaan server yang telah diverifikasi.
- conflicts: arsip versi lokal dan server.
- local_meta: versi schema lokal dan metadata recovery.

Alur yang dirancang:
1. Tulis answer + sync queue secara atomik ke IndexedDB.
2. Baru setelah transaksi lokal sukses, kirim request.
3. Jika respons hilang, retry request identik.
4. Verifikasi receipt server.
5. Pada conflict, simpan versi lokal dan server, blok submit, jangan pilih diam-diam.
6. Pada reload/crash, pulihkan data lokal berdasarkan scope Attempt/device yang sama.
7. Revalidasi Attempt/device/lease/fencing ke server sebelum resume.
8. Pada offline→online, replay queue secara berurutan sesuai revision.
9. Multi-tab harus mengoordinasikan writer; tab coordination bukan pengganti fencing backend.
10. Jika quota penuh/IndexedDB tidak tersedia, tampilkan error dan jangan fallback diam-diam ke memory.

Status: DESIGN ONLY — NOT IMPLEMENTED / NOT TESTED. Jangan menyebut Web Siswa mendukung ujian offline saat ini.

### 3.11 Submit / kirim ujian
Perilaku source:
- Submit manual menampilkan confirm dialog.
- Runner mencoba flush pending saves sebelum POST submit.
- Jika flush manual gagal, submit ditahan dan error ditampilkan.
- Jika server merespons sukses, panel runner diganti panel completion.
- API submit memeriksa ownership, tenant, device binding dan state Attempt; Attempt menjadi terminal sesuai backend.

Risiko:
- Pada auto-submit saat expiry, source tetap melanjutkan submit walau flush gagal.
- Tidak ada client submit idempotency key yang disepakati.
- Tidak ada lookup hasil submit definitif jika response hilang.
- Belum ada Answer Receipt yang diverifikasi client.
- Lock order Answer update (Attempt→Answer) berbeda dari lifecycle Attempt (Exam→Participant→Attempt); global lock order belum disetujui.
- Double submit, save-vs-submit, Force Submit-vs-answer, expiry-vs-answer dan response-lost race belum dibuktikan.

Submit barrier yang wajib:
- Semua jawaban lokal yang perlu disinkronkan telah tersimpan durable.
- Semua request answer memiliki receipt server terverifikasi.
- Queue kosong; tidak ada request aktif/pending/outcome unknown.
- Tidak ada conflict terbuka.
- Attempt masih sah menurut server.
- Device/lease/fencing valid.
- Question order dan snapshot cocok.
- Submit memakai idempotency contract backend.
- SUBMITTED hanya ditampilkan setelah hasil definitif server diverifikasi.
- Jika response hilang, jangan membuat Attempt baru atau mengirim key baru; lakukan lookup/retry sesuai kontrak Agent 2.

Force Submit:
- Bukan aksi Siswa.
- Hanya actor berizin di luar portal Siswa; alasan dan audit trail wajib.
- Siswa hanya menerima status akhir yang diputuskan server.
Status: submit dasar ADA; submit barrier BLOCKED.

### 3.12 Hasil ujian
Perilaku source:
- Route GET /student/results/{attempt}.
- Siswa hanya dapat melihat hasil miliknya sendiri.
- Exam harus mengizinkan show_result.
- Attempt harus berstatus SUBMITTED atau EXPIRED.
- Current score harus ada dan grading_status FINALIZED.
- Current score ditentukan oleh exam_attempt_score_current pointer yang valid; jangan memilih nilai tertinggi dari riwayat score.
- Halaman menampilkan judul ujian, mata pelajaran jika ada, nilai persentase, skor raw/max, status Final, nomor percobaan, waktu mulai, waktu dikumpulkan, dan waktu penilaian final.
- API score untuk Siswa mengembalikan summary minimal dan details kosong; detail per soal, jawaban, feedback/grader dan internal relation tidak ditampilkan.

Ketentuan:
- Hasil belum boleh muncul sebelum Guru mempublikasikan hasil.
- Hasil belum boleh muncul saat nilai masih MANUAL_REVIEW/non-final.
- Hasil harus milik Siswa yang sedang login dan tenant yang sama.
- Siswa tidak boleh melihat jawaban benar, kunci, rubric, per-question scoring detail, identitas penilai atau histori score internal.
- Perubahan nilai final hanya melalui workflow Guru yang berwenang dan audit; Siswa hanya membaca current finalized score yang dipublikasikan.

Kesenjangan:
- UI hasil yang ditemukan hanya ringkasan final.
- Tidak ditemukan halaman pembahasan jawaban atau review per soal.
- Tidak ditemukan halaman histori seluruh Attempt; dashboard hanya menampilkan latest Attempt dan jumlah percobaan.
- API daftar hasil siswa ditemukan pada route laporan, tetapi halaman histori Web Siswa yang mengonsumsinya belum ditemukan.

Status: ADA DI SOURCE — BELUM TERUJI; riwayat/pembahasan BELUM ADA pada UI yang diperiksa.

### 3.13 Profil dan data akademik Siswa
Keputusan kebutuhan:
- Data Siswa harus berasal dari StudentProfile dan relasi akademik resmi.
- Informasi yang dapat disediakan setelah endpoint/otorisasi tersedia: nama, NISN, kelas, Rombel, tahun pelajaran, dan identitas madrasah.
- Data akademik harus sesuai periode aktif dan tidak menampilkan membership/rombel lama sebagai penugasan saat ini.
- Data historis tetap dipertahankan sesuai periode akademik.

Temuan source:
- Tidak ditemukan route/profile page Siswa pada routes/web.php yang diperiksa.
- Halaman Ujian Saya hanya menampilkan user.name, bukan StudentProfile, NISN, kelas/Rombel atau tahun pelajaran.
- Halaman hasil menampilkan nama dan mata pelajaran jika tersedia.

Status: BELUM ADA sebagai halaman/profile feature pada source yang diperiksa. Agent baru tidak boleh membuat data akademik dari asumsi; koordinasikan dengan Agent 2/owner akademik.

### 3.14 Notifikasi, pengumuman, bantuan dan pengaturan
Tidak ditemukan pada source Web Siswa yang diperiksa:
- pusat notifikasi;
- pengumuman madrasah;
- pesan Guru;
- kalender ujian mandiri;
- tiket bantuan;
- penggantian password mandiri;
- edit profil;
- preferensi tema;
- riwayat perangkat;
- fitur foto/kamera;
- QR check-in;
- fitur Proktor.

Jangan membuat tombol/menu dummy. Fitur tersebut hanya dapat ditambahkan setelah requirement dan backend source disetujui.

## 4. PETA ROUTE DAN API YANG DITEMUKAN

### 4.1 Route Web Siswa
- GET /student/exams — daftar ujian Siswa.
- GET /student/exams/{exam} — instruksi dan halaman awal ujian.
- GET /student/results/{attempt} — ringkasan hasil final milik Siswa.
- POST /login — login bersama, identitas Siswa menggunakan NISN.
- POST /logout — logout.

Route Web dibungkus auth:web, tenant, dan license middleware. Namun role exact-one tetap harus diterapkan pada controller/policy/service setiap endpoint.

### 4.2 API ujian/Attempt/jawaban/media
Route yang ditemukan pada routes/api.php di grup auth:sanctum, tenant, account-session-version, dan license:
- POST /api/v1/exams/{exam}/attempts — start atau resume Attempt.
- GET /api/v1/exams/{exam}/execution — execution context/Attempt dan snapshot yang digunakan runner.
- GET /api/v1/exams/{exam}/attempts/{attempt} — detail Attempt sesuai policy.
- GET /api/v1/exams/{exam}/attempts/{attempt}/answers — memulihkan jawaban server.
- PUT /api/v1/exams/{exam}/attempts/{attempt}/answers/{snapshot} — menyimpan jawaban.
- POST /api/v1/exams/{exam}/attempts/{attempt}/submit — mengirim ujian.
- GET /api/v1/exams/{exam}/attempts/{attempt}/score — summary score; Student DTO dibatasi.
- GET /api/v1/media/{media} — membaca media yang diizinkan.

API score, report, monitoring dan media memiliki policy/authorization masing-masing. Jangan menganggap semua route dalam satu grup otomatis aman untuk Siswa.

## 5. KEAMANAN DAN INTEGRITAS — P0 ACCEPTANCE

### 5.1 Exact-one role SISWA
Wajib ditolak pada setiap Student Web/API/policy/service:
- SISWA+GURU.
- SISWA+ADMIN_MADRASAH.
- SISWA+KAMAD.
- SISWA+OPERATOR/role legacy.
- GURU saja, ADMIN saja, KAMAD saja, Proktor Guru.
- Role kosong/tidak dikenal.
- Akun nonaktif/soft-deleted.
Login gate saja tidak cukup.

### 5.2 F-01 — admission dan snapshot release
Tidak boleh ada snapshot/soal/opsi/media sebelum backend membuktikan:
- user tepat satu role SISWA dan aktif;
- instalasi/tenant benar;
- Exam benar dan telah terbit/terkunci sesuai lifecycle;
- ExamParticipant milik user dan masih eligible;
- jadwal server mengizinkan start, atau ada Attempt IN_PROGRESS sah untuk resume;
- token valid jika diwajibkan;
- attempt limit valid untuk Attempt baru;
- Attempt ownership/status/expiry valid;
- device/lease/fencing valid sesuai kontrak backend.
Sebelum admission, hanya metadata/instruksi aman boleh ditampilkan. Answer key, accepted answers, rubric, scoring guide, explanation privat, dan peserta lain tidak boleh bocor.

### 5.3 F-02 — media
Media harus terikat ke:
User → Tenant/Installation → Exam → Attempt → question_order → ExamQuestionSnapshot → QuestionVersion/MediaAsset.
Validasi harus termasuk status Attempt, device, lease token, expiry/revoke, fencing generation dan stale request. UUID media saja tidak cukup.

### 5.4 Answer, revision, receipt dan konflik
- client_request_id harus immutable per request dan retry memakai payload yang sama.
- base_revision harus monotonic dan diperiksa server.
- Same ID + same payload harus idempotent.
- Same ID + different payload harus ditolak.
- Conflict 409 harus mengarsipkan versi lokal dan server; jangan membuang pending lokal.
- Receipt harus mengikat Attempt, snapshot, request ID, payload hash, revision dan waktu server sesuai kontrak Agent 2.
- Client hanya menandai diterima server setelah receipt diverifikasi.
- Lock order harus disepakati dan konsisten untuk answer/submit/expiry/Force Submit/lease.

### 5.5 Device Lease/Fencing
Harus ada bukti lifecycle acquire, heartbeat, release, revoke, expiry, takeover, registered device, lease token dan fencing generation.
- Satu Attempt tidak boleh menerima dua writer aktif.
- Device lama harus ditolak setelah takeover/fencing.
- Tidak boleh takeover otomatis dari browser Siswa.
- Session-bound UUID pada source saat ini bukan bukti Device Lease/Fencing siap produksi.

### 5.6 Privasi data
Web Siswa tidak boleh menerima:
- answer key/correctness flag;
- accepted answers;
- rubric/scoring guide;
- explanation privat;
- jawaban siswa lain;
- detail nilai per soal/feedback Guru;
- identitas grader;
- data tenant/madrasah lain;
- storage path atau credential internal;
- Proktor assignment atau monitoring event internal.

## 6. UI/UX, RESPONSIVE DAN AKSESIBILITAS

Kebutuhan wajib:
- Bahasa Indonesia sederhana dan konsisten.
- Mobile-first dan responsive untuk HP, tablet, desktop.
- Tampilan ujian minim distraksi: soal, jawaban, timer, status simpan, navigasi, submit.
- Input mudah disentuh pada layar kecil; keyboard virtual tidak menutupi jawaban.
- Scroll posisi terjaga ketika pindah soal.
- Navigasi soal memberi status terjawab/belum terjawab/konflik tanpa membocorkan correctness.
- Timer terbaca dan tidak menghalangi soal.
- Fokus keyboard terlihat; semua input memiliki label; tombol memiliki nama yang jelas; status/error menggunakan live region yang sesuai.
- Kontras warna memadai dan tidak mengandalkan warna saja.
- Gambar memiliki alt text yang bermakna; audio/video memiliki kontrol; PDF memiliki link yang jelas.
- Arab/RTL/harakat dan campuran Arab-Indonesia harus diuji dengan bidi isolation/arah teks yang benar.
- Formula matematika harus memakai renderer yang disetujui dan fallback terbaca.
- Light/Dark Mode bukan bukti fitur selesai; jika disediakan harus konsisten dan diuji.
- Hindari animasi berat; prioritas performa dan stabilitas.

Bukti source saat audit:
- View memakai meta viewport dan Tailwind utility responsive sederhana.
- Layout runner memakai grid desktop dan panel navigasi.
- Ini hanya bukti adanya styling responsive dasar; tidak ada Browser E2E atau visual QA yang dijalankan.
- Belum ada bukti Light/Dark toggle, Arabic/RTL renderer, formula renderer, screen-reader audit atau mobile keyboard QA.

## 7. MATRIKS STATUS FITUR

| Fitur | Status source/audit | Catatan |
|---|---|---|
| Login Siswa NISN/password | ADA — BELUM TERUJI | KAMAD masih di allow-list; exact-one Student boundary belum menyeluruh |
| Logout/remember me | ADA — BELUM TERUJI | Deactivation/session epoch tests ada secara statis |
| Dashboard Ujian Saya | ADA — BELUM TERUJI | Daftar ExamParticipant, bukan dashboard akademik |
| Kartu ujian/status/jadwal/durasi | ADA — BELUM TERUJI | Status dihitung pada controller |
| Detail/instruksi ujian | ADA — BELUM TERUJI | Token required dan jadwal ditampilkan |
| Token input | ADA — BELUM TERUJI | Token tidak ditampilkan ke Siswa |
| Admission | PARSIAL / BLOCKED | F-01 snapshot release belum aman |
| Start Attempt | ADA / BLOCKED | Policy exact-role dan admission perlu hardening |
| Resume Attempt | PARSIAL / BLOCKED | Same browser/device path ada; lease/fencing/recovery belum terverifikasi |
| Timer | ADA — BELUM TERUJI | Menggunakan server_now sebagai basis perkiraan client |
| Navigasi soal | ADA — BELUM TERUJI | Previous/next dan nomor soal |
| MC/True-False | ADA — BELUM TERUJI | Radio |
| Multiple Response | ADA — BELUM TERUJI | Checkbox |
| Matching | ADA — BELUM TERUJI | Bergantung option_key/matching_key contract |
| Short Answer | BLOCKED | Accepted answers belum terbukti masuk snapshot/scoring |
| Essay | PARSIAL | Textarea ada; manual review workflow perlu dibuktikan |
| Image/audio/video/PDF | ADA — BELUM TERUJI | F-02 media auth masih BLOCKED |
| Autosave | PARSIAL | API ada, browser state masih in-memory |
| Offline durable | BELUM ADA | IndexedDB hanya desain |
| Reload/crash recovery | PARSIAL/BLOCKED | Server answers dapat dimuat; pending local hilang |
| Conflict 409 | BLOCKED | Current runner membuang pending lokal |
| Submit manual | PARSIAL | Flush ada, belum idempotent/receipt barrier |
| Auto expiry submit | BLOCKED | Dapat submit meski flush gagal |
| Submit response recovery | BELUM ADA | Belum ada definitive lookup contract |
| Hasil final summary | ADA — BELUM TERUJI | show_result + current score FINALIZED |
| Riwayat semua Attempt | BELUM ADA pada UI | Dashboard hanya latest Attempt + count |
| Review/pembahasan soal | BELUM ADA | Student score DTO details kosong |
| Profil akademik | BELUM ADA | Tidak ada route/profile page pada source yang diperiksa |
| Notifikasi/pengumuman | BELUM ADA | Jangan buat placeholder |
| Proktor di Web Siswa | TIDAK ADA / DILARANG | Proktor hanya assignment Guru di Web Guru |
| Kamera/webcam | TIDAK ADA / BUKAN SYARAT | Tidak boleh menjadi dependency CBT standar |
| Secure kiosk | TIDAK ADA / TIDAK DIJANJIKAN | Browser biasa bukan exam lockdown browser |
| Responsive dasar | ADA | Belum diuji browser |
| Light/Dark Mode | BELUM TERBUKTI | Jangan klaim |
| Arabic/RTL/harakat | BELUM TERUJI | Wajib acceptance |
| Formula matematika | BELUM TERUJI | Wajib renderer contract |
| Accessibility | BELUM TERUJI | Keyboard/screen reader/contrast |

## 8. SOURCE DAN TEST INVENTORY YANG DIPERIKSA

Runtime baseline yang direview: Agent 2 branch work/stage4d-account-lifecycle-20261122. Branch tip Agent 2 pada saat audit adalah 7642f8df7d20effe82dc1ba5b086147894332767; compare terhadap runtime baseline 74bf7178f46673e9be90523671490c0749034acd menunjukkan 18 commit ahead, 0 behind dan file perubahan hanya dokumentasi Agent 2. Karena itu file runtime berikut dicatat sebagai source baseline yang diperiksa, bukan klaim bahwa seluruh branch Agent 1–4 telah terintegrasi.

File utama:
- app/Http/Controllers/AuthWebController.php — blob b8e04102858652c2fe9042f8f680c511fcbccf63.
- app/Services/LoginIdentityResolver.php — blob c1f56029da5b8fdb5d7f80380269637b7d1c3ebe.
- app/Http/Controllers/StudentExamController.php — blob 981fd29d24906c158d4dda5889950fdc43f6ec3d.
- resources/views/auth/login.blade.php — blob 2c6580cfd8df434573b6652cd527c7c932a818df.
- resources/views/student/exams/index.blade.php — blob e76c70f4646677c3b64bd2c36b333330f93cdcae.
- resources/views/student/exams/show.blade.php — blob 7283ca553d787f4505299a306e2bd487884622d3.
- resources/views/student/results/show.blade.php — blob acff7cd3bfd74839f18e348dc1bf939cfd06a44e.
- routes/web.php — blob f012b3de23575e3ef91c8f07ad1c0d7ae780fb21.
- routes/api.php — blob 2e43c37dd669a26446a44b818c1d2c8420b15aca.
- app/Http/Controllers/Api/V1/ExamController.php — blob c7c62e961f2ff47abee8f3b2468b4e7bc43d1cc9.
- app/Http/Controllers/Api/V1/ExamAttemptController.php — blob 13e8981cca4653ff4e9b9448a50e4d0ec8a74bae.
- app/Http/Controllers/Api/V1/ExamAnswerController.php — blob b4ddec14f6208b3ecde9bfc3e45168a2159e2a9c.
- app/Http/Controllers/Api/V1/MediaController.php — blob f852c1b3a7247efdbb0c309fb0413bbe5e3eaa62.
- app/Policies/ExamPolicy.php — blob c05d96ef932fdc81b67336240af4e1726864c3d6.
- app/Policies/ExamAttemptPolicy.php — blob 00a3a4119f3983de6d940fd8f352a541336c9e12.
- app/Models/ExamParticipant.php — blob feaab347ac9ab7540033e160a9a4f0d287f65398.
- app/Models/ExamAttempt.php — blob 341c8d00337ad8761dca94602da7acfbdc769fb0.
- app/Models/ExamAttemptAnswer.php — blob 9765f2750ef500c2a0477882794ca0461fa7f1f2.
- app/Models/ExamAttemptScore.php — blob b374cb57245324e0633aa78ee36ff8489ecbf438.
- app/Models/ExamAttemptScoreCurrent.php — blob d9d0841d9f8e2824b8f8d9d88b223a34c754c963.
- app/Http/Controllers/Api/V1/ScoringController.php — blob 42b96176162a6e69e3661d468a6d3362b05f59c5.

Test inventory (jumlah metode statis, bukan PASS):
- StudentWebLoginTest.php: 11 test methods.
- ExamExecutionHardeningTest.php: 6 test methods.
- ExamAnswerTest.php: 21 test methods.
- ParticipantAuthorizationAndAttemptLimitTest.php: 13 test methods.
- StudentResultVisibilityTest.php: 7 test methods.

Pengujian yang benar-benar dilakukan dalam penyusunan dokumen ini: pembacaan source GitHub, pembacaan branch/commit/blob, inspeksi route, view, controller, policy, model, dan test source. Tidak ada PHP lint, PHPUnit, MySQL 8/InnoDB runtime, browser, IndexedDB, multi-tab, offline, multi-device, concurrency, atau UAT dijalankan.

## 9. ACCEPTANCE TEST WAJIB UNTUK AGENT WEB SISWA

### 9.1 Login dan session
- SISWA single-role + NISN/password berhasil.
- Username tidak dapat dipakai sebagai identifier Siswa bila keputusan NISN tetap berlaku.
- Password salah menghasilkan pesan generik.
- NISN duplikat lintas instalasi tidak mengungkap akun.
- SISWA+GURU, SISWA+ADMIN, SISWA+KAMAD, role legacy, role kosong ditolak.
- KAMAD tidak dapat login, memperoleh session, remember cookie, Sanctum token atau redirect ke CBT.
- Akun soft-deleted/deactivated ditolak.
- Session lama/remember-me/API token tidak hidup kembali setelah restore; login baru diperlukan.
- Logout benar-benar mengakhiri sesi.

### 9.2 Dashboard dan eligibility
- Hanya ExamParticipant milik siswa yang muncul.
- Exam tenant lain, peserta lain, Exam tidak terbit dan participant non-eligible tidak bocor.
- eligible_from/eligible_until dan attempt limit dihitung sesuai waktu server.
- Status kartu sesuai state Exam/Attempt.
- Active Attempt tampil sebagai Lanjutkan, bukan Buat Attempt baru.
- Hasil hanya muncul jika show_result dan current score FINALIZED.

### 9.3 Admission, token dan snapshot
- Token kosong/salah/rotasi ditolak sesuai konfigurasi.
- Token benar sebelum jadwal hanya menghasilkan metadata aman, bukan snapshot.
- Sebelum jadwal, tidak ada Attempt baru.
- Peserta tidak terdaftar ditolak.
- Tenant/install mismatch ditolak.
- Attempt ID milik orang lain/tenant lain tidak bocor.
- Exam ditutup tanpa Attempt aktif: Attempt baru ditolak.
- Exam ditutup dengan Attempt IN_PROGRESS valid: resume Attempt sama.
- Snapshot hanya dilepas setelah admission lengkap.
- Tidak ada answer key, accepted answers, rubric, scoring guide, explanation privat atau metadata internal di response Siswa.

### 9.4 Runner, media dan question types
- Question order dan option order konsisten untuk satu Attempt.
- Snapshot yang tidak ada pada question_order ditolak.
- Enam tipe diuji; SHORT_ANSWER tetap disabled sampai chain scoring PASS.
- MATCHING payload harus sesuai schema authoring/snapshot.
- Media dari tenant, Attempt, snapshot atau question_order lain ditolak.
- Lease/fencing stale ditolak untuk execution, answer, media dan submit.
- Arab/RTL/harakat/mixed content dan formula lulus visual/browser QA.
- Missing/corrupt media menampilkan fallback.

### 9.5 Answer persistence dan recovery
- Answer + queue tersimpan atomik di IndexedDB.
- Retry menggunakan request ID dan payload yang sama.
- Same ID/different payload ditolak.
- Receipt diverifikasi.
- 409 mempertahankan local dan server version.
- Response hilang setelah commit dapat dipulihkan tanpa duplikasi.
- Reload/crash memulihkan local pending.
- Offline→online mengirim queue berurutan.
- Quota penuh/IndexedDB gagal ditampilkan; tidak mengklaim tersimpan.
- Multi-tab writer conflict ditangani.
- Device berbeda tidak dapat membaca atau mengirim local data Attempt lama.
- Takeover hanya melalui otorisasi backend; tidak otomatis dari browser.

### 9.6 Submit, expiry dan hasil
- Submit menunggu semua answer receipt verified.
- Pending/unknown/conflict/storage failure menahan submit.
- Double submit satu efek final dan idempotent.
- Submit response hilang dapat diperiksa dengan lookup/retry canonical.
- Expiry vs save/submit serial dan server-authoritative.
- Auto-expiry tidak membuang local pending.
- Force Submit hanya actor berizin, alasan dan audit lengkap.
- Result tidak terlihat sebelum publish/finalize.
- Result hanya ringkasan milik siswa sendiri; tidak ada detail jawaban/kunci/penilai.
- Current score pointer authoritative; historical score tidak dipilih sembarang.

### 9.7 Browser dan usability
- Android Chrome, iOS Safari, desktop Chrome/Firefox.
- Viewport mobile, tablet dan desktop.
- Virtual keyboard, rotate screen, scroll, timer sticky, navigation, input focus.
- Offline/reconnect, reload, tab crash, dua tab.
- Keyboard-only, focus visible, screen-reader labels, contrast.
- Arabic/RTL/harakat, mixed Arabic-Indonesia, formula.
- No browser lockdown claim; kamera bukan syarat.

Semua pengujian di atas harus berjalan pada source SHA yang sama dengan disposable MySQL 8/InnoDB dan browser E2E. Jangan menggunakan hasil CI SHA lama sebagai bukti source terbaru.

## 10. PEMBAGIAN OWNERSHIP DAN KOORDINASI

- Agent 2: login identity, exact-one SISWA seluruh backend entrypoint, KAMAD frozen, ExamPolicy, F-01 admission/snapshot release, F-02 media authorization, Attempt lifecycle, Answer receipt/revision/409, submit idempotency, expiry, Force Submit, Device Lease/Fencing, audit log dan DTO API.
- Agent 1: aturan Room/placement/assignment Proktor dan scheduler/recovery yang terkait. Room tidak menjadi admission gate Siswa. Jangan mengubah model ujian menjadi gelombang/session operasional.
- Agent 3: authoring Guru, QuestionVersion, enam tipe payload, immutable snapshot, media authoring, curriculum metadata dan scoring handoff. SHORT_ANSWER tetap BLOCKED sampai diterima end-to-end.
- Agent 4 (Web Siswa): halaman login konsumsi identitas, dashboard Ujian Saya, detail instruksi/token, runner, question rendering, answer client, IndexedDB/recovery, submit barrier, result UI, responsive/accessibility/browser E2E. Agent 4 tidak mengambil alih backend owner.
- Koordinator: tetapkan official Agent 4 branch/base/owner dan buka implementation gate secara eksplisit.

Branch dokumentasi Agent 4 saat dokumen ini dibuat: work/agent4-web-siswa-audit-20261122. Branch kandidat work/import-guru-siswa-implementation-20261122 tidak boleh diasumsikan sebagai branch resmi karena diverged dan membawa perubahan file backend Agent 2-owned. Jangan merge/rebase/cherry-pick kandidat tanpa keputusan eksplisit.

## 11. LANGKAH IMPLEMENTASI UNTUK AGENT PENGGANTI

1. Baca dokumen ini dan keputusan produk terbaru Issue #10.
2. Refresh branch, HEAD, exact source SHA dan checkpoint; jangan memakai SHA lama sebagai asumsi.
3. Tentukan official Agent 4 implementation branch dan base commit melalui koordinator.
4. Baca source aktual Agent 2/3 yang menjadi dependency; minta kontrak eksplisit untuk endpoint/DTO/receipt/lease/fencing.
5. Buat matriks route → controller/view → API → policy/service → test → status.
6. Backup setiap file sebelum perubahan; verifikasi byte-exact; jangan mengubah ZIP baseline.
7. Implementasikan hanya file milik Agent 4 setelah implementation gate dibuka. Jangan edit routes/web.php atau backend Agent 2-owned tanpa koordinasi.
8. Prioritaskan keselamatan jawaban: durable persistence, conflict archive, submit barrier, response-lost recovery.
9. Jalankan PHP lint/PHPUnit/CI exact-SHA, disposable MySQL 8/InnoDB, browser E2E dan responsive/accessibility QA.
10. Update laporan AUDIT, DESAIN, IMPLEMENTASI, PENGUJIAN, RISIKO/BLOCKER, HANDOVER dan checkpoint.
11. Jangan merge/deploy sendiri; tidak ada migration produksi atau akses VPS/SSH.
12. Jangan nyatakan Web Siswa siap sampai semua P0 security, data integrity, recovery, browser, integration dan UAT lulus pada source yang sama.

## 12. STATUS AKHIR SAAT DOKUMEN DIBUAT

- Login NISN/password: ADA, tetapi KAMAD allow-list dan exact-role endpoint masih P0.
- Dashboard Ujian Saya: ADA, tetapi hanya daftar ujian dan belum browser-tested.
- Detail/instruksi/token: ADA, admission/snapshot F-01 belum aman.
- Runner: ADA, answer state masih in-memory dan submit barrier belum aman.
- Autosave: PARSIAL; 409 handling dapat membuang local pending.
- Offline/IndexedDB: DESIGN ONLY.
- Media: renderer ada, F-02 authorization belum lengkap.
- SHORT_ANSWER: BLOCKED.
- ESSAY: manual review, end-to-end belum diverifikasi.
- Hasil final: ADA, summary-only dan belum runtime-tested.
- Profil akademik, history page, review jawaban, notifikasi/pengumuman: belum ditemukan pada UI source yang diperiksa.
- Proktor pada Web Siswa: tidak ada dan dilarang.
- Camera/kiosk: bukan syarat dan tidak dijanjikan.
- Browser E2E/CI exact-SHA/PHPUnit/MySQL runtime: belum ada bukti PASS pada source baseline yang diperiksa.
- Web Siswa: NOT READY FOR OPERATIONAL EXAM.

**Dokumen ini adalah spesifikasi dan handover, bukan pernyataan bahwa fitur telah selesai diimplementasikan atau lulus pengujian.**
