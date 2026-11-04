#!/usr/bin/env python3
"""Independent result-level recomputation from Toulour Index Maintenance S3 saved benchmark JSON files.
No DB access, no lexical corpus, no third-party dependencies. Not an independent ground-truth index oracle.
"""
import json, sys, pathlib, statistics, collections
root=pathlib.Path(__file__).resolve().parent
files=sorted((root/'raw_reports').glob('STAGE22[CD]_B*_30PAIRS_*.json'))
assert len(files)==6, f'Expected exactly six final paired-run JSONs; found {len(files)}'
by_stage={}; pairs=0
for filename in files:
    data=json.loads(filename.read_text(encoding='utf-8-sig'))
    assert data['status']=='PASS' and data['reps']==30 and data['batch'] in (1,10,100)
    stage=data['stage']; batch=data['batch']
    names=('selective_refresh','differential') if stage=='22C' else ('B2_refresh_matched','C2_delta_matched')
    runs=data['benchmark']['runs']; assert len(runs)==30
    wins=collections.Counter(); writes=[0,0]; times=[[],[]]; first=collections.Counter(); fixture=set()
    for run in runs:
        assert run['parity']=='PASS'
        first[run['order'][0]]+=1
        assert run['mutation_id_sha256'] not in fixture
        fixture.add(run['mutation_id_sha256'])
        a,b=(run['methods'][n] for n in names)
        assert a['index_digest_sha256']==b['index_digest_sha256']
        times[0].append(a['maintenance_ms']);times[1].append(b['maintenance_ms'])
        for j,v in enumerate((a,b)):
            op=v['index_operations']
            if stage=='22C':
                z=op if j==0 else op['write_counts']
                writes[j]+=z['postings_deleted']+z['postings_inserted']
            else:
                writes[j]+=op['postings_total_writes']
        wins[names[0] if times[0][-1]<times[1][-1] else names[1]]+=1
    assert sorted(first.values())==[15,15]
    reduction=100*(1-writes[1]/writes[0])
    print(f'{stage} batch={batch:>3} parity=30/30 B/C medians={statistics.median(times[0]):.4f}/{statistics.median(times[1]):.4f} ms writes={writes[0]}/{writes[1]} reduction={reduction:.4f}% wins={dict(wins)}')
    by_stage[(stage,batch)]={'fixture_sha':{r['mutation_id_sha256']:r['methods'][names[0]]['index_digest_sha256'] for r in runs},'writes':writes}
    pairs+=len(runs)
for batch in (1,10,100):
    assert by_stage[('22C',batch)]['fixture_sha']==by_stage[('22D',batch)]['fixture_sha']
    assert by_stage[('22C',batch)]['writes']==by_stage[('22D',batch)]['writes']
assert pairs==180
print('PASS: 180 paired comparisons in two separately analyzed protocols; matching digests and writes across protocols.')
print('IMPORTANT: this confirms saved-report agreement, not independent regeneration from the underlying dictionary.')
