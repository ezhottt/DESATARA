# DESATARA Backup & Restore Runbook

**Status:** controlled B17 operational artifact

Dokumen ini mendefinisikan prosedur yang dapat diisi operator tanpa mengklaim provider, host, path, credential, retention, RPO, atau RTO yang belum dipilih. Backup belum dianggap recovery capability sebelum restore drill berhasil.

## Scope backup

Backup release harus mencakup:

- database PostgreSQL;
- private documents dari disk/storage yang digunakan aplikasi;
- konfigurasi kritis yang diperlukan untuk restore, tanpa memasukkan secret ke repository atau log.

Backup harus memiliki akses terbatas, integrity check, enkripsi bila sesuai, dan strategi offsite/redundant sesuai operational plan yang disetujui.

## Procedure

1. Catat commit SHA dan deployment identifier release.
2. Hentikan atau koordinasikan write sesuai maintenance/traffic strategy yang disetujui.
3. Jalankan backup PostgreSQL menggunakan `pg_dump`/`pg_dumpall` yang sesuai topology aktual; jangan menaruh password di command line atau output.
4. Salin private documents dan metadata/configuration yang diperlukan ke target backup yang disetujui.
5. Verifikasi checksum, ukuran, keterbacaan archive, dan akses terbatas.
6. Catat evidence pada tabel di bawah.

## Restore drill

Restore hanya ke isolated target yang bukan database atau storage production.

1. Siapkan target restore yang disetujui operator.
2. Restore database dan private documents dari backup yang dicatat.
3. Jalankan migrasi/configuration verification sesuai release procedure.
4. Verifikasi database starts, application connects, tenants/assets/relationships intact, documents accessible melalui authorization, dan reports/history intact.
5. Jalankan focused smoke test pada target isolated.
6. Catat issue dan hasil; jangan menyebut backup `recoverable` bila langkah ini belum sukses.

## Evidence record

| Field | Recorded value |
|---|---|
| Backup timestamp |  |
| Restore timestamp |  |
| Backup source |  |
| Restore target |  |
| Commit SHA |  |
| Deployment identifier |  |
| Duration |  |
| Result |  |
| Verification |  |
| Issues |  |

RPO/RTO belum ditetapkan oleh baseline dan tidak boleh diisi berdasarkan asumsi.
