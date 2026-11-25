# Backup manifest — Limited ZIP P0 bootstrap

Pre-change branch: `work/limited-zip-p0-20261125`
Pre-change HEAD: `aea96fcd3fabba7f82ba66a4b941025e80d9fbc7`

| Source | Byte-exact backup | Git blob SHA (equal) | SHA-256 | UTF-8 bytes |
|---|---|---|---|---:|
| `backend-laravel/app/Services/InstallerService.php` | `backend-laravel/backups/limited-zip-p0-20261125/InstallerService.php.before` | `a75350d7b0ca2be59d5cc8ffb74810ac4b232a00` | `e1603710e14c5094cefe6c0de15203cbaa2ad2e3144e845fbc134b25670eb0e7` | 51150 |
| `backend-laravel/tests/Feature/InstallerEndToEndTest.php` | `backend-laravel/backups/limited-zip-p0-20261125/InstallerEndToEndTest.php.before` | `84b6becad2a700c799b51a9a8f2e6e9ddf86b777` | `67dad53e5ddba31fa0acd76885fb3862cf6cc67deac412c0e5ef8c69dad2a7da` | 8430 |
| `.github/workflows/docx-import-parser-ci.yml` | `backend-laravel/backups/limited-zip-p0-20261125/docx-import-parser-ci.yml.before` | `d8b81d768ceb5ac5bb4308e57e6d28540ac4511a` | `99fdf23b160283c4fffb384eb43788e3af44d069bccced89eef66bf3a38043f8` | 4487 |

All three files were fetched from the same exact pre-change HEAD. Backup blobs are committed before any source patch and re-verified against the source blob SHAs.
