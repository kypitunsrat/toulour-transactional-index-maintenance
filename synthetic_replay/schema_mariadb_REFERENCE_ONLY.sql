-- REFERENCE ONLY. DO NOT EXECUTE from the replay launcher. No DATABASE/CREATE DATABASE/DROP statements.
-- Stage-2 schema reproduced from archived IndexStage2Materializer.php; not an executable replay.
-- The standalone demonstration uses schema_sqlite.sql and an in-memory SQLite database.
CREATE TABLE idx_stage2_entries (
  dictionary_id INT NOT NULL PRIMARY KEY,
  category_id INT NOT NULL,
  normalized_word TEXT NOT NULL,
  normalized_meaning MEDIUMTEXT NOT NULL,
  content_sha256 CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  KEY idx_stage2_cat (category_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin ROW_FORMAT=DYNAMIC;
CREATE TABLE idx_stage2_terms (
  dictionary_id INT NOT NULL,
  kind CHAR(1) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  term_sha256 CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  normalized_term TEXT NOT NULL,
  PRIMARY KEY (dictionary_id, kind, term_sha256),
  KEY idx_stage2_lookup (kind, term_sha256),
  CONSTRAINT fk_idx_stage2_entries FOREIGN KEY (dictionary_id)
    REFERENCES idx_stage2_entries(dictionary_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin ROW_FORMAT=DYNAMIC;
CREATE TABLE idx_stage2_meta (
  id TINYINT NOT NULL PRIMARY KEY,
  schema_version VARCHAR(80) NOT NULL,
  baseline_dictionary_sha256 CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  materialized_entries INT NOT NULL,
  materialized_terms INT NOT NULL,
  built_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;
