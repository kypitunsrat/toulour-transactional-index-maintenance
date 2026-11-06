#!/usr/bin/env python3
"""Read-only verification of six Stage22C/D archived report headers.

Checks a public manifest against unchanged JSON reports. This is NOT a new
hash calculation from private SQL or a PHP/MariaDB rerun.
"""
import json
from pathlib import Path

ROOT = Path(__file__).resolve().parent.parent
m = json.loads((ROOT/'STAGE22_BASELINE_LOCK_MANIFEST.json').read_text(encoding='utf-8'))
assert m['manifest_version']=='STAGE22_LOGICAL_BASELINE_PUBLIC_V1'
expected = {(r['stage'],r['batch']):r for r in m['report_files']}
assert len(expected)==6 and set(expected)=={(s,b) for s in ('22C','22D') for b in (1,10,100)}
expected_count = 0
for (stage,batch),r in sorted(expected.items()):
    path=ROOT/'raw_reports'/r['report']
    assert path.is_file(), 'Missing report: '+str(path)
    d=json.loads(path.read_text(encoding='utf-8'))
    assert d['stage']==stage and d['batch']==batch and d['status']=='PASS',path.name
    assert d['initial']['source']['total']==m['source_total'],path.name
    assert d['initial']['source']['approved']==m['approved_count'],path.name
    assert d['initial']['source']['sha256']==r['source_sha256']==m['source_core_original_runner_sha256'],path.name
    assert d['initial_index_digest_sha256']==r['initial_index_digest_sha256']==m['wmt_index_original_runner_sha256'],path.name
    assert len(d['benchmark']['runs'])==r['paired_comparisons']==30,path.name
    assert d['benchmark']['all_pair_digests_identical'] is True,path.name
    expected_count+=len(d['benchmark']['runs'])
assert expected_count==180
assert m['archived_per_run_mysqldump_files_available'] is False
assert m['full_db_replay_claimed'] is False
print('PASS: six Stage22C/D reports; 180 pairs; source and initial W/M/T digests agree with archived logical baseline manifest.')
print('Scope: saved-report agreement only; no private-SQL hashing or independent DB rerun performed.')
