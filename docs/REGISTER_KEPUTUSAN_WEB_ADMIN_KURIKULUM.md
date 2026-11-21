# Register Keputusan — Master Kurikulum Web Admin

**Tanggal pencatatan:** 21 November 2026  
**Status:** Keputusan arsitektur wajib; dokumentasi saja; implementasi menunggu gate W0 dan persetujuan tahap terkait.

| ID | Keputusan | Ketentuan mengikat | Dampak |
|---|---|---|---|
| DEC-CURR-001 | Kurmer dan KBC satu kesatuan | Kurmer adalah kerangka akademik; KBC adalah landasan nilai/karakter. Tidak ada pilihan Kurmer ATAU KBC. | Identitas kurikulum, akademik, master, Guru, Bank Soal, asesmen, laporan |
| DEC-CURR-002 | KBC bukan kurikulum alternatif | Tidak membuat mapel, fase, CP, TP, ATP, atau kode RDM tandingan KBC. | Model data, UI, validasi, migration |
| DEC-CURR-003 | Master mapel MI | Admin mengelola mapel umum, rumpun PAI, Bahasa Arab, dan Mulok. | Master Subject dan integrasi RDM |
| DEC-CURR-004 | Kode RDM harus bersumber | Kode tidak boleh ditebak. Hanya VERIFIED setelah pembandingan dengan referensi resmi RDM yang relevan dan berlaku. | Data import, validasi, laporan status verifikasi |
| DEC-CURR-005 | Master CP/TP/ATP | CP terikat kurikulum/fase/mapel; TP terikat CP; ATP menyusun TP dengan konsistensi yang sama. | DB, API, dropdown Guru, snapshot |
| DEC-CURR-006 | Provenance kurikulum | Simpan sumber resmi, penerbit/nomor dokumen, versi, tahun/tanggal berlaku, status, dan histori. | Versioning, audit, reproduksibilitas |
| DEC-CURR-007 | Panca Cinta resmi | Gunakan lima nomenklatur dari KMA 1503/2025 dan Panduan KBC Kepdirjen 6077/2025; integrasikan lintas mapel. | Bank soal, pembelajaran, survei karakter |
| DEC-CURR-008 | Delapan DPL resmi | Gunakan nomenklatur delapan dimensi dalam KMA 1503/2025; jangan mengubah istilah. | Pembelajaran mendalam, asesmen, laporan |
| DEC-CURR-009 | Hak Admin master-only | ADMIN_MADRASAH dapat menambah/mengubah master kurikulum. Tidak ada delete permanen. | Policy, Livewire, API, audit |
| DEC-CURR-010 | Guru konsumsi referensi | Guru memilih CP/TP, Panca Cinta, dan DPL dari master; Guru tidak mengubah master. | Dropdown bertingkat, API read-only |
| DEC-CURR-011 | Lifecycle non-destruktif | Referensi usang dinonaktifkan/ditandai tidak berlaku; histori dan relasi dipertahankan. | Status, versioning, FK RESTRICT, audit |
| DEC-CURR-012 | Integrasi soal dan histori | Soal menyimpan referensi tervalidasi dan snapshot versi/label; perubahan master tidak merusak histori soal/asesmen. | Question mapping, snapshot, laporan |
| DEC-CURR-013 | Survei karakter dan laporan | Panca Cinta/DPL menjadi referensi untuk survei karakter dan laporan akademik sesuai desain akademik. | Domain survey/report dan kontrak data |
| DEC-CURR-014 | Referensi resmi tidak boleh direka | CP/TP/ATP, kode mapel RDM, dan deskripsi resmi hanya dimuat setelah sumber diverifikasi. | Seeder/importer, QA, audit data |
| DEC-CURR-015 | W6 wajib | Semua kriteria pada addendum spesifikasi dan matriks pengujian menjadi acceptance criteria W6. | Gate W6 |
| DEC-CURR-016 | Batas W0/W1 | Dokumen ini tidak mengesahkan W0 atau membuka W1. Source tetap tidak berubah sampai persetujuan eksplisit. | Kontrol perubahan proyek |

## Rujukan

1. KMA Nomor 1503 Tahun 2025, khususnya Lampiran I tentang kerangka dasar, Pembelajaran Mendalam, 8 DPL, dan Panca Cinta: https://sulteng.kemenag.go.id/files/show/20251020162801surat-penyampaian-kma-kurikulum-2025%2Blampiran_compressed-tzR.pdf
2. Kepdirjen Pendidikan Islam Nomor 6077 Tahun 2025, Panduan Kurikulum Berbasis Cinta: https://assets.zyrosite.com/YNqBxzZ8EVI1XRal/panduan-kurikulum-berbasis-cinta-2025-dan-pengantar-YKblkllNGvhpRq7W.pdf

## Status verifikasi eksternal

- Panca Cinta dan delapan DPL: nomenklatur utama telah dicocokkan dengan KMA 1503/2025.
- Panduan KBC: identitas Kepdirjen 6077/2025 dan daftar lima topik Panca Cinta telah diperiksa pada dokumen panduan yang tersedia.
- Kode RDM: BELUM VERIFIED. Portal RDM Kemenag pada saat audit menampilkan status pemeliharaan. Tidak ada kode yang diisi berdasarkan dugaan.
- CP/TP/ATP resmi: BELUM DISEED. Setiap dokumen referensi jenjang MI harus diverifikasi satu per satu sebelum import.
