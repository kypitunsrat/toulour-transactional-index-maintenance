#!/usr/bin/env python3
"""Read-only archive inventory check. No network, database or file writes."""
from pathlib import Path
import hashlib, json
root = Path(__file__).resolve().parent
inventory = json.loads((root/'SHA256_PUBLIC_FILES.json').read_text(encoding='utf-8'))
listed = {e['path'] for e in inventory['files']}
actual = {p.relative_to(root).as_posix() for p in root.rglob('*') if p.is_file() and p.name!='SHA256_PUBLIC_FILES.json' and '.git' not in p.relative_to(root).parts and '__pycache__' not in p.relative_to(root).parts and p.suffix!='.pyc'}
missing = listed - actual
unlisted = actual - listed
assert not missing, 'Missing files: '+repr(sorted(missing))
assert not unlisted, 'Unlisted files: '+repr(sorted(unlisted))
for entry in inventory['files']:
    path = root / entry['path']
    blob = path.read_bytes()
    assert len(blob)==entry['bytes'], 'Changed length: '+entry['path']
    actual_sha256 = hashlib.sha256(blob).hexdigest()
    assert actual_sha256==entry['sha256'], 'Modified: '+entry['path']
print(f"PASS: {len(inventory['files'])} distributed files match SHA-256 inventory; no unlisted files.")
