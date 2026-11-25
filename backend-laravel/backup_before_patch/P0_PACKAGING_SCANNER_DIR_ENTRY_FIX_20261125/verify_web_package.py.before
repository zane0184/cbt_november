#!/usr/bin/env python3
"""Fail-closed distribution scanner and regression fixtures for Web CBT ZIP."""
from __future__ import annotations

import hashlib
import re
import shutil
import stat
import sys
import tempfile
import zipfile
from pathlib import Path, PurePosixPath

PACKAGE_ROOT = "CBT_MADRASAH_WEB_DEPLOYMENT"
ENV_EXAMPLE_STAGE = "backend-laravel/.env.example"
ENV_EXAMPLE_ZIP = f"{PACKAGE_ROOT}/backend-laravel/.env.example"
FORBIDDEN_SUFFIXES = {
    ".before", ".bak", ".backup", ".orig", ".old", ".swp", ".swo", ".tmp", ".temp",
    ".log", ".sql", ".dump", ".sqlite", ".sqlite3", ".db", ".db3", ".mdb", ".accdb",
    ".ibd", ".frm", ".myd", ".myi", ".pem", ".key", ".p12", ".pfx", ".jks",
    ".keystore", ".crt", ".cer", ".der", ".zip", ".tar", ".gz", ".tgz", ".7z",
    ".rar", ".bz2", ".xz", ".zst", ".core", ".dmp",
}
FORBIDDEN_DIRS = {".git", ".phpunit.cache", "tests"}
FORBIDDEN_NAMES = {
    ".npmrc", ".pypirc", ".netrc", "id_rsa", "id_ed25519", "authorized_keys",
    "credentials", "credentials.json", "secrets.json", "secrets.yml", "secrets.yaml",
}
BACKUP_COMPONENT = re.compile(r"(?i)(?:^|[^a-z0-9])backups?(?:$|[^a-z0-9])")
BACKUP_FILE_MARKER = re.compile(r"(?i)(?:^|[._-])backups?(?:[._-]|$)")
SECRET_PATTERNS = [
    re.compile(rb"-----BEGIN (?:RSA |EC |OPENSSH |DSA )?PRIVATE KEY-----"),
    re.compile(rb"-----BEGIN CERTIFICATE-----"),
    re.compile(rb"(?im)^[ \t]*(?:APP_KEY|CBT_INSTALLER_BOOTSTRAP_TOKEN|DB_PASSWORD|REDIS_PASSWORD|MAIL_PASSWORD|AWS_SECRET_ACCESS_KEY)[ \t]*=[ \t]*\"?([A-Za-z0-9+/=_-]{16,})\"?[ \t]*(?:#.*)?$"),
    re.compile(rb"(?im)^[ \t]*(?:[A-Z0-9_]*(?:PASSWORD|SECRET|TOKEN|API_KEY|ACCESS_KEY)[A-Z0-9_]*)[ \t]*[:=][ \t]*\"?([A-Za-z0-9/+_.=-]{20,})\"?[ \t]*(?:#.*)?$"),
    re.compile(rb"\bAKIA[0-9A-Z]{16}\b"),
    re.compile(rb"\bgh[pousr]_[A-Za-z0-9]{20,}\b"),
    re.compile(rb"\bxox[baprs]-[A-Za-z0-9-]{10,}\b"),
    re.compile(rb"\bsk_live_[A-Za-z0-9]{16,}\b"),
    re.compile(rb"(?i)Authorization\s*:\s*Bearer\s+[A-Za-z0-9._~+/=-]{20,}"),
]
README_REQUIRED = [
    "Windows Explorer", "PHP 8.3", "MySQL", ".env.example", "install/authorize",
    "storage/", "bootstrap/cache", "backup", "bukan sertifikat siap produksi",
]
RUNTIME_PREFIXES = (
    "backend-laravel/storage/logs/",
    "backend-laravel/storage/reports/",
    "backend-laravel/storage/framework/cache/",
    "backend-laravel/storage/framework/sessions/",
    "backend-laravel/storage/framework/views/",
    "backend-laravel/storage/app/private/",
)


def is_backup_dir(name: str) -> bool:
    return bool(BACKUP_COMPONENT.search(name))


def is_backup_file(name: str) -> bool:
    return bool(BACKUP_FILE_MARKER.search(name))


def is_forbidden_dir(name: str) -> bool:
    folded = name.casefold()
    return folded in FORBIDDEN_DIRS or is_backup_dir(name)


def check_file(rel: str, data: bytes, env_example: str, errors: list[str]) -> None:
    path = PurePosixPath(rel)
    name = path.name.casefold()
    if name == ".env" or (name.startswith(".env.") and rel != env_example):
        errors.append(f"environment file: {rel}")
    if name in FORBIDDEN_NAMES:
        errors.append(f"forbidden credential filename: {rel}")
    if path.suffix.casefold() in FORBIDDEN_SUFFIXES:
        errors.append(f"forbidden file suffix: {rel}")
    if is_backup_file(path.name):
        errors.append(f"backup filename: {rel}")
    for pattern in SECRET_PATTERNS:
        if pattern.search(data):
            errors.append(f"embedded secret or credential pattern: {rel}")
            break


def check_readme(path: Path, errors: list[str]) -> None:
    if not path.is_file():
        errors.append("README_PEMASANGAN.md missing")
        return
    text = path.read_text(encoding="utf-8", errors="replace").casefold()
    missing = [term for term in README_REQUIRED if term.casefold() not in text]
    if missing:
        errors.append(f"README missing required topics: {missing}")


def scan_staging(root: Path) -> list[str]:
    errors: list[str] = []
    if not root.is_dir():
        return ["staging root missing"]
    seen: dict[str, str] = {}
    for item in sorted(root.rglob("*")):
        rel = item.relative_to(root).as_posix()
        folded = rel.casefold()
        if folded in seen and seen[folded] != rel:
            errors.append(f"case collision: {seen[folded]} <> {rel}")
        seen[folded] = rel
        if item.is_symlink():
            errors.append(f"symlink: {rel}")
            continue
        if item.is_dir():
            if any(is_forbidden_dir(part) for part in PurePosixPath(rel).parts):
                errors.append(f"forbidden directory path: {rel}")
            continue
        if not item.is_file():
            errors.append(f"unsupported filesystem entry: {rel}")
            continue
        parts = PurePosixPath(rel).parts
        if any(is_forbidden_dir(part) for part in parts[:-1]):
            errors.append(f"forbidden directory path: {rel}")
        check_file(rel, item.read_bytes(), ENV_EXAMPLE_STAGE, errors)
        if rel.startswith(RUNTIME_PREFIXES) and item.name != ".gitignore":
            errors.append(f"runtime data: {rel}")
        if rel.startswith("backend-laravel/bootstrap/cache/") and item.name != ".gitignore":
            errors.append(f"generated bootstrap cache: {rel}")
    check_readme(root / "README_PEMASANGAN.md", errors)
    return errors


def scan_zip(archive: Path) -> list[str]:
    errors: list[str] = []
    seen: dict[str, str] = {}
    try:
        with zipfile.ZipFile(archive) as zf:
            infos = zf.infolist()
            has_manifest = False
            for info in infos:
                name = info.filename
                raw_parts = name.split("/")
                if name.startswith("/") or "\\" in name or re.match(r"^[A-Za-z]:", name):
                    errors.append(f"absolute/backslash path: {name}")
                if any(part in ("", ".", "..") for part in raw_parts if part != "" or not name.endswith("/")):
                    errors.append(f"non-canonical or traversal path: {name}")
                parts = PurePosixPath(name).parts
                if not parts or parts[0] != PACKAGE_ROOT or ".." in parts:
                    errors.append(f"path traversal or unexpected root: {name}")
                normalized = name.rstrip("/")
                folded = normalized.casefold()
                if folded in seen:
                    errors.append(f"duplicate/case-collision: {seen[folded]} <> {name}")
                seen[folded] = name
                mode = (info.external_attr >> 16) & 0o170000
                if mode == stat.S_IFLNK:
                    errors.append(f"symlink entry: {name}")
                if any(is_forbidden_dir(part) for part in parts[:-1] if part):
                    errors.append(f"forbidden directory path: {name}")
                if info.is_dir():
                    continue
                if name == f"{PACKAGE_ROOT}/SHA256SUMS.txt":
                    has_manifest = True
                data = zf.read(info)
                rel = name[len(PACKAGE_ROOT) + 1:] if name.startswith(PACKAGE_ROOT + "/") else name
                check_file(rel, data, ENV_EXAMPLE_STAGE, errors)
                if rel.startswith(RUNTIME_PREFIXES) and PurePosixPath(rel).name != ".gitignore":
                    errors.append(f"runtime data: {name}")
                if rel.startswith("backend-laravel/bootstrap/cache/") and PurePosixPath(rel).name != ".gitignore":
                    errors.append(f"generated bootstrap cache: {name}")
                if rel == "README_PEMASANGAN.md":
                    text = data.decode("utf-8", errors="replace").casefold()
                    missing = [term for term in README_REQUIRED if term.casefold() not in text]
                    if missing:
                        errors.append(f"README missing required topics: {missing}")
            if not has_manifest:
                errors.append("SHA256SUMS.txt missing from archive")
    except (OSError, zipfile.BadZipFile) as exc:
        errors.append(f"invalid ZIP archive: {exc}")
    return errors


def valid_readme() -> str:
    return (
        "Windows Explorer Extract All. PHP 8.3 and MySQL. Configure .env.example. "
        "Run install/authorize. Prepare storage/ and bootstrap/cache. Keep backup safe. "
        "Dokumen ini bukan sertifikat siap produksi."
    )


def make_valid_tree(root: Path) -> None:
    (root / "backend-laravel").mkdir(parents=True)
    (root / "backend-laravel/.env.example").write_text(
        "APP_KEY=\nCBT_INSTALLER_BOOTSTRAP_TOKEN=\nDB_PASSWORD=\nREDIS_PASSWORD=null\n",
        encoding="utf-8",
    )
    (root / "backend-laravel/app").mkdir()
    (root / "backend-laravel/app/Example.php").write_text("<?php\nreturn ['status' => 'ok'];\n", encoding="utf-8")
    (root / "backend-laravel/resources/views").mkdir(parents=True)
    (root / "backend-laravel/resources/views/example.blade.php").write_text("<div>{{ $title }}</div>\n", encoding="utf-8")
    for directory in (
        "backend-laravel/storage/app",
        "backend-laravel/storage/framework/cache/data",
        "backend-laravel/storage/framework/sessions",
        "backend-laravel/storage/framework/views",
        "backend-laravel/storage/logs",
        "backend-laravel/bootstrap/cache",
    ):
        (root / directory).mkdir(parents=True, exist_ok=True)
        (root / directory / ".gitignore").write_text("*\n!.gitignore\n", encoding="utf-8")
    (root / "README_PEMASANGAN.md").write_text(valid_readme(), encoding="utf-8")


def make_zip(root: Path, archive: Path, extras: list[tuple[str, bytes]] | None = None) -> None:
    with zipfile.ZipFile(archive, "w", compression=zipfile.ZIP_DEFLATED) as zf:
        for item in sorted(root.rglob("*")):
            if item.is_file():
                zf.write(item, f"{PACKAGE_ROOT}/{item.relative_to(root).as_posix()}")
        zf.writestr(f"{PACKAGE_ROOT}/SHA256SUMS.txt", "fixture manifest\n")
        for name, data in extras or []:
            zf.writestr(name, data)


def self_test() -> None:
    positive = 0
    negative = 0
    with tempfile.TemporaryDirectory(prefix="cbt-package-scanner-test-") as tmp:
        base = Path(tmp)
        valid = base / "valid"
        make_valid_tree(valid)
        stage_errors = scan_staging(valid)
        archive = base / "valid.zip"
        make_zip(valid, archive)
        zip_errors = scan_zip(archive)
        if stage_errors or zip_errors:
            raise SystemExit(f"Positive fixture rejected: staging={stage_errors}; zip={zip_errors}")
        positive += 1

        cases = [
            ("backup/secret.env", b"DB_PASSWORD=\n"),
            ("backup-before-patch/private.key", b"synthetic private key file\n"),
            ("BACKUPS/database.sql", b"-- synthetic database dump\n"),
            ("nested/backups_before_patch/archive.zip", b"synthetic nested archive\n"),
            (".env.production", b"APP_ENV=production\n"),
            ("secrets/private.pem", b"-----BEGIN PRIVATE KEY-----\nSYNTHETIC\n-----END PRIVATE KEY-----\n"),
            ("config/token.txt", b"CBT_INSTALLER_BOOTSTRAP_TOKEN=synthetic_token_value_0123456789\n"),
        ]
        for rel, data in cases:
            case_root = base / f"case-{negative}"
            shutil.copytree(valid, case_root)
            target = case_root / rel
            target.parent.mkdir(parents=True, exist_ok=True)
            target.write_bytes(data)
            stage_bad = scan_staging(case_root)
            case_zip = base / f"case-{negative}.zip"
            make_zip(case_root, case_zip)
            zip_bad = scan_zip(case_zip)
            if not stage_bad or not zip_bad:
                raise SystemExit(
                    f"Negative fixture unexpectedly accepted: {rel}; "
                    f"staging={stage_bad}; zip={zip_bad}"
                )
            negative += 1

        # ZIP-only structural attacks: traversal, symlink and case-collision/duplicate.
        structural = [
            ("../escape.txt", b"escape"),
            (f"{PACKAGE_ROOT}/nested/../../escape.txt", b"escape"),
            (f"{PACKAGE_ROOT}/duplicate.txt", b"one"),
            (f"{PACKAGE_ROOT}/DUPLICATE.txt", b"two"),
        ]
        for index, (name, data) in enumerate(structural):
            bad_zip = base / f"structural-{index}.zip"
            extras = [(name, data)]
            if index == 2:
                extras.append((name, b"two"))
            make_zip(valid, bad_zip, extras)
            if not scan_zip(bad_zip):
                raise SystemExit(f"ZIP structural negative fixture unexpectedly accepted: {name}")
            negative += 1

        symlink_zip = base / "symlink.zip"
        with zipfile.ZipFile(symlink_zip, "w") as zf:
            zf.writestr(f"{PACKAGE_ROOT}/SHA256SUMS.txt", "fixture manifest\n")
            info = zipfile.ZipInfo(f"{PACKAGE_ROOT}/link")
            info.create_system = 3
            info.external_attr = (stat.S_IFLNK | 0o777) << 16
            zf.writestr(info, "target")
        if not scan_zip(symlink_zip):
            raise SystemExit("ZIP symlink fixture unexpectedly accepted")
        negative += 1
    print(f"Scanner fixture suite PASS: {positive} positive fixture; {negative} negative fixtures.")
    print("Both staging and ZIP scanners rejected all seven required malicious content fixtures.")
    print("ZIP structural checks rejected traversal, duplicate/case collision, and symlink fixtures.")


def main() -> None:
    if len(sys.argv) == 2 and sys.argv[1] == "self-test":
        self_test()
        return
    if len(sys.argv) == 3 and sys.argv[1] == "staging":
        errors = scan_staging(Path(sys.argv[2]))
        if errors:
            print("\n".join(errors), file=sys.stderr)
            raise SystemExit("Staging security audit failed.")
        print(f"Staging security audit PASS: {sum(1 for p in Path(sys.argv[2]).rglob('*') if p.is_file())} files checked.")
        return
    if len(sys.argv) == 3 and sys.argv[1] == "zip":
        errors = scan_zip(Path(sys.argv[2]))
        if errors:
            print("\n".join(errors), file=sys.stderr)
            raise SystemExit("ZIP security audit failed.")
        with zipfile.ZipFile(sys.argv[2]) as zf:
            print(f"ZIP security audit PASS: {len(zf.infolist())} entries inspected.")
        return
    raise SystemExit("Usage: verify_web_package.py self-test | staging ROOT | zip ARCHIVE")


if __name__ == "__main__":
    main()
