# Historical code snapshots: important safety notice

The Stage22A/B/B2/C/D/E PHP files are provided byte-for-byte unchanged so reviewers can inspect the update workflow. The accompanying BAT/README publication copies have documentation-only updates as listed in PUBLICATION_NOTES.md. They contain **destructive database restoration and DROP routines**, exclusively targeted at allowlisted *disposable* databases in the original controlled environment. They also assume `root` with blank password in local XAMPP.

The Stage22A preflight is designed to be read-only, but the Stage22B/B2/C/D historical runners can alter or delete disposable databases. These files **must not be run** on a production database or an arbitrary computer. They **will not execute as a standalone checkout** because source dependencies, historical runner and controlled original corpus are excluded. Do not remove safety checks to force a replay. For new replication, create a separate sandbox implementation with explicit authentication and a genuinely releasable fixture.

The code snapshot preserves the experiments' semantics but is **not a recommended security configuration**. It does not grant a software reuse license; consult `PROVENANCE_AND_RIGHTS.md`.
