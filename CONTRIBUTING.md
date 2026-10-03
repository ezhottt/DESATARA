# Contributing to DESATARA

Terima kasih ingin berkontribusi. DESATARA adalah proyek documentation-first dan security-sensitive untuk tata kelola aset desa.

## Sebelum mulai

1. Baca `README.md`, `docs/DOCUMENT-CONTROL.md`, dan kontrak domain yang relevan.
2. Untuk perubahan besar, buka issue lebih dulu agar scope dan dampaknya dapat direview.
3. Jangan mencampur beberapa batch/domain yang tidak terkait dalam satu PR.
4. Jangan commit secret, credential, data desa nyata, atau dokumen pribadi.

## Alur kontribusi

- Buat branch yang fokus pada satu perubahan.
- Tambahkan/ubah test terlebih dahulu untuk behavior baru atau regression fix.
- Implementasikan perubahan sekecil yang diperlukan.
- Jalankan quality gate yang relevan.
- Sinkronkan dokumentasi bila contract/behavior berubah.
- Buat PR dengan alasan perubahan, risiko, evidence test, dan dampak compatibility.

## Controlled Baseline

Dokumentasi authoritative adalah **Controlled Baseline**, bukan dokumen yang kebal perubahan. Perubahan diperbolehkan melalui PR/review ketika didukung regulasi baru, verified contradiction, security finding, atau evidence implementasi.

Perubahan pada tenancy, authority, workflow, historical integrity, regulatory mapping, security boundary, atau acceptance gate harus:
- menyebut kontrak authoritative yang terdampak;
- menjelaskan alasan dan migration/compatibility impact;
- memperbarui downstream docs yang relevan;
- menyertakan regression test bila dapat dieksekusi.

## Quality gate minimum

```bash
php artisan test
./vendor/bin/pint --test
./vendor/bin/phpstan analyse
npm run build
npm audit
```

PostgreSQL adalah database test utama. SQLite-only evidence tidak cukup untuk contract yang bergantung pada PostgreSQL.

## Pull request

PR harus kecil, reviewable, dan tidak mengklaim capability yang belum tersedia. Checklist lengkap tersedia pada template PR.

## Security

Jangan membuka public issue berisi credential, exploit detail yang aktif, data pribadi, atau bukti sensitif. Responsible-disclosure channel formal akan ditetapkan sebelum public production release pertama.

## Demo data

Demo/seed data harus sintetis. Dataset onboarding lengkap baru diperkenalkan setelah B02 Tenant Boundary dan B03 RBAC tersedia agar tenant, membership, dan role dapat direpresentasikan secara benar.
