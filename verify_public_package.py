#!/usr/bin/env python3
"""Verify SHA-256 inventory of published files. No network, database or writes."""
from pathlib import Path
import hashlib, json
root=Path(__file__).resolve().parent
inventory=json.loads((root/'SHA256_PUBLIC_FILES.json').read_text(encoding='utf-8'))
for entry in inventory['files']:
    path=root/entry['path']
    assert path.is_file(), 'Missing: '+str(path)
    actual=hashlib.sha256(path.read_bytes()).hexdigest()
    assert actual==entry['sha256'], 'Modified: '+str(path)
print(f"PASS: {len(inventory['files'])} archived files match frozen SHA-256 bytes")
