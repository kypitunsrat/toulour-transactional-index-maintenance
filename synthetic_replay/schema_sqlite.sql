-- Stage 4: SAFE synthetic demonstrator only. SQLite :memory:; NEVER the historical MariaDB DB.
-- Names mirror the frozen Stage-2 W/M/T materialization tables; types are SQLite adaptations.
PRAGMA foreign_keys = ON;
CREATE TABLE dictionary (
    id INTEGER PRIMARY KEY,
    category_id INTEGER NOT NULL,
    status TEXT NOT NULL CHECK(status IN ('disetujui','pending')),
    tolour_word TEXT NOT NULL,
    indonesian_meaning TEXT NOT NULL
);
CREATE TABLE idx_stage2_entries (
    dictionary_id INTEGER PRIMARY KEY,
    category_id INTEGER NOT NULL,
    normalized_word TEXT NOT NULL,
    normalized_meaning TEXT NOT NULL,
    content_sha256 TEXT NOT NULL CHECK(length(content_sha256)=64)
);
CREATE INDEX idx_stage2_cat ON idx_stage2_entries(category_id);
CREATE TABLE idx_stage2_terms (
    dictionary_id INTEGER NOT NULL,
    kind TEXT NOT NULL CHECK(kind IN ('W','M','T')),
    term_sha256 TEXT NOT NULL CHECK(length(term_sha256)=64),
    normalized_term TEXT NOT NULL,
    PRIMARY KEY(dictionary_id,kind,term_sha256),
    FOREIGN KEY(dictionary_id) REFERENCES idx_stage2_entries(dictionary_id) ON DELETE CASCADE
);
CREATE INDEX idx_stage2_lookup ON idx_stage2_terms(kind,term_sha256);
CREATE TABLE idx_stage2_meta (
    id INTEGER PRIMARY KEY CHECK(id=1),
    schema_version TEXT NOT NULL,
    baseline_dictionary_sha256 TEXT NOT NULL,
    materialized_entries INTEGER NOT NULL,
    materialized_terms INTEGER NOT NULL
);
