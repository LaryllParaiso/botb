-- ==================================================
-- Grand Finals Stress Test Data
-- ==================================================
-- Scenarios covered:
--   1) Photo-finish winner (1–2 point spread across judges)
--   2) Three-way tie in the middle of the table
--   3) Clear last-place band
--
-- Usage (phpMyAdmin or mysql CLI):
--   SOURCE test_data_finals.sql;
--
-- Notes:
--   * Assumes database name: botb_tabulator
--   * Assumes bands already exist with names:
--       - Solaris Drift
--       - Aurora Bloom
--       - Midnight Riff
--       - Silver Echo
--       - Harbor Lights
--     and are assigned to the GRAND FINALS round (round_id = 2).
--   * Assumes judges already exist with emails:
--       - judge1@botb.com
--       - judge2@botb.com
--       - judge3@botb.com
--   * Only GRAND FINALS scores are reset; elimination scores are untouched.
--
-- Criteria for grand finals (by id):
--   4) Musicality (30)
--   5) Creativity & Originality (25)
--   6) Stage Presence & Audience Engagement (20)
--   7) Overall Impact (10)
--   8) Original Composition (15)
-- ==================================================

USE botb_tabulator;
SET FOREIGN_KEY_CHECKS = 0;

-- Ensure required grand finals bands exist (idempotent, round_id = 2)
INSERT INTO bands (name, round_id, performance_order, is_active)
SELECT 'Solaris Drift', 2, 1, 0
WHERE NOT EXISTS (SELECT 1 FROM bands WHERE name = 'Solaris Drift' AND round_id = 2);

INSERT INTO bands (name, round_id, performance_order, is_active)
SELECT 'Aurora Bloom', 2, 2, 0
WHERE NOT EXISTS (SELECT 1 FROM bands WHERE name = 'Aurora Bloom' AND round_id = 2);

INSERT INTO bands (name, round_id, performance_order, is_active)
SELECT 'Midnight Riff', 2, 3, 0
WHERE NOT EXISTS (SELECT 1 FROM bands WHERE name = 'Midnight Riff' AND round_id = 2);

INSERT INTO bands (name, round_id, performance_order, is_active)
SELECT 'Silver Echo', 2, 4, 0
WHERE NOT EXISTS (SELECT 1 FROM bands WHERE name = 'Silver Echo' AND round_id = 2);

INSERT INTO bands (name, round_id, performance_order, is_active)
SELECT 'Harbor Lights', 2, 5, 0
WHERE NOT EXISTS (SELECT 1 FROM bands WHERE name = 'Harbor Lights' AND round_id = 2);

-- Ensure required judges exist (idempotent)
INSERT INTO users (name, email, password, role)
SELECT 'Judge One', 'judge1@botb.com', '$2y$12$4v2e6mzf81kywmQYwD73FO3eSEBhJq7wRmv4lwpTjshV1W9rv3tt2', 'judge'
WHERE NOT EXISTS (SELECT 1 FROM users WHERE email = 'judge1@botb.com');

INSERT INTO users (name, email, password, role)
SELECT 'Judge Two', 'judge2@botb.com', '$2y$12$C/BwIQM4yygb5LTFpC/4qeJjG29RaWdCm6DxjPEx6C5ps2wVjPlx2', 'judge'
WHERE NOT EXISTS (SELECT 1 FROM users WHERE email = 'judge2@botb.com');

INSERT INTO users (name, email, password, role)
SELECT 'Judge Three', 'judge3@botb.com', '$2y$12$gD6R6IdbEFyGkASx.uVKAeI9iGZJTmTO89TrCB2PzE3WeI9PREHNW', 'judge'
WHERE NOT EXISTS (SELECT 1 FROM users WHERE email = 'judge3@botb.com');

-- Resolve band IDs by name (safe even if IDs changed)
SET @band_solaris  = (SELECT id FROM bands WHERE name = 'Solaris Drift'   LIMIT 1);
SET @band_aurora   = (SELECT id FROM bands WHERE name = 'Aurora Bloom'    LIMIT 1);
SET @band_midnight = (SELECT id FROM bands WHERE name = 'Midnight Riff'   LIMIT 1);
SET @band_silver   = (SELECT id FROM bands WHERE name = 'Silver Echo'     LIMIT 1);
SET @band_harbor   = (SELECT id FROM bands WHERE name = 'Harbor Lights'   LIMIT 1);

-- Resolve judge IDs by email (after potential insert)
SET @judge1_id  = (SELECT id FROM users WHERE email = 'judge1@botb.com'  LIMIT 1);
SET @judge2_id  = (SELECT id FROM users WHERE email = 'judge2@botb.com'  LIMIT 1);
SET @judge3_id  = (SELECT id FROM users WHERE email = 'judge3@botb.com'  LIMIT 1);
SET @judge4_id  = (SELECT id FROM users WHERE email = 'judge4@botb.com'  LIMIT 1);
SET @judge5_id  = (SELECT id FROM users WHERE email = 'judge5@botb.com'  LIMIT 1);
SET @judge6_id  = (SELECT id FROM users WHERE email = 'judge6@botb.com'  LIMIT 1);
SET @judge7_id  = (SELECT id FROM users WHERE email = 'judge7@botb.com'  LIMIT 1);
SET @judge8_id  = (SELECT id FROM users WHERE email = 'judge8@botb.com'  LIMIT 1);
SET @judge9_id  = (SELECT id FROM users WHERE email = 'judge9@botb.com'  LIMIT 1);
SET @judge10_id = (SELECT id FROM users WHERE email = 'judge10@botb.com' LIMIT 1);

-- Delete ONLY existing grand-finals scores for these bands
DELETE FROM scores
WHERE band_id IN (@band_solaris, @band_aurora, @band_midnight, @band_silver, @band_harbor)
  AND criteria_id BETWEEN 4 AND 8;

SET FOREIGN_KEY_CHECKS = 1;

-- ==================================================
-- Insert new GRAND FINALS scores (all finalized = 1)
-- Scenario:
--   - Solaris Drift narrowly beats Aurora Bloom
--   - Aurora Bloom + Midnight Riff + Silver Echo form a 3-way tie cluster
--   - Harbor Lights clearly last
-- ==================================================

-- Judge 1 (slightly favors Solaris)
INSERT INTO scores (judge_id, band_id, criteria_id, score, is_finalized) VALUES
  -- Solaris Drift
  (@judge1_id, @band_solaris,  4, 29, 1), (@judge1_id, @band_solaris,  5, 24, 1),
  (@judge1_id, @band_solaris,  6, 19, 1), (@judge1_id, @band_solaris,  7, 9,  1),
  (@judge1_id, @band_solaris,  8, 14, 1),
  -- Aurora Bloom
  (@judge1_id, @band_aurora,   4, 28, 1), (@judge1_id, @band_aurora,   5, 24, 1),
  (@judge1_id, @band_aurora,   6, 18, 1), (@judge1_id, @band_aurora,   7, 9,  1),
  (@judge1_id, @band_aurora,   8, 14, 1),
  -- Midnight Riff
  (@judge1_id, @band_midnight, 4, 26, 1), (@judge1_id, @band_midnight, 5, 22, 1),
  (@judge1_id, @band_midnight, 6, 17, 1), (@judge1_id, @band_midnight, 7, 8,  1),
  (@judge1_id, @band_midnight, 8, 13, 1),
  -- Silver Echo
  (@judge1_id, @band_silver,   4, 26, 1), (@judge1_id, @band_silver,   5, 22, 1),
  (@judge1_id, @band_silver,   6, 17, 1), (@judge1_id, @band_silver,   7, 8,  1),
  (@judge1_id, @band_silver,   8, 13, 1),
  -- Harbor Lights
  (@judge1_id, @band_harbor,   4, 24, 1), (@judge1_id, @band_harbor,   5, 20, 1),
  (@judge1_id, @band_harbor,   6, 15, 1), (@judge1_id, @band_harbor,   7, 7,  1),
  (@judge1_id, @band_harbor,   8, 11, 1);

-- Judge 2 (slightly favors Aurora, keeps totals close)
INSERT INTO scores (judge_id, band_id, criteria_id, score, is_finalized) VALUES
  -- Solaris Drift
  (@judge2_id, @band_solaris,  4, 28, 1), (@judge2_id, @band_solaris,  5, 24, 1),
  (@judge2_id, @band_solaris,  6, 18, 1), (@judge2_id, @band_solaris,  7, 9,  1),
  (@judge2_id, @band_solaris,  8, 14, 1),
  -- Aurora Bloom
  (@judge2_id, @band_aurora,   4, 29, 1), (@judge2_id, @band_aurora,   5, 24, 1),
  (@judge2_id, @band_aurora,   6, 19, 1), (@judge2_id, @band_aurora,   7, 9,  1),
  (@judge2_id, @band_aurora,   8, 14, 1),
  -- Midnight Riff (pulled up toward tie cluster)
  (@judge2_id, @band_midnight, 4, 27, 1), (@judge2_id, @band_midnight, 5, 23, 1),
  (@judge2_id, @band_midnight, 6, 18, 1), (@judge2_id, @band_midnight, 7, 8,  1),
  (@judge2_id, @band_midnight, 8, 13, 1),
  -- Silver Echo (same as Midnight for tie)
  (@judge2_id, @band_silver,   4, 27, 1), (@judge2_id, @band_silver,   5, 23, 1),
  (@judge2_id, @band_silver,   6, 18, 1), (@judge2_id, @band_silver,   7, 8,  1),
  (@judge2_id, @band_silver,   8, 13, 1),
  -- Harbor Lights
  (@judge2_id, @band_harbor,   4, 24, 1), (@judge2_id, @band_harbor,   5, 19, 1),
  (@judge2_id, @band_harbor,   6, 15, 1), (@judge2_id, @band_harbor,   7, 7,  1),
  (@judge2_id, @band_harbor,   8, 11, 1);

-- Judge 3 (breaks overall tie but keeps mid-pack very close)
INSERT INTO scores (judge_id, band_id, criteria_id, score, is_finalized) VALUES
  -- Solaris Drift
  (@judge3_id, @band_solaris,  4, 30, 1), (@judge3_id, @band_solaris,  5, 24, 1),
  (@judge3_id, @band_solaris,  6, 19, 1), (@judge3_id, @band_solaris,  7, 10, 1),
  (@judge3_id, @band_solaris,  8, 15, 1),
  -- Aurora Bloom
  (@judge3_id, @band_aurora,   4, 29, 1), (@judge3_id, @band_aurora,   5, 24, 1),
  (@judge3_id, @band_aurora,   6, 19, 1), (@judge3_id, @band_aurora,   7, 9,  1),
  (@judge3_id, @band_aurora,   8, 14, 1),
  -- Midnight Riff
  (@judge3_id, @band_midnight, 4, 27, 1), (@judge3_id, @band_midnight, 5, 22, 1),
  (@judge3_id, @band_midnight, 6, 17, 1), (@judge3_id, @band_midnight, 7, 8,  1),
  (@judge3_id, @band_midnight, 8, 13, 1),
  -- Silver Echo
  (@judge3_id, @band_silver,   4, 27, 1), (@judge3_id, @band_silver,   5, 22, 1),
  (@judge3_id, @band_silver,   6, 17, 1), (@judge3_id, @band_silver,   7, 8,  1),
  (@judge3_id, @band_silver,   8, 13, 1),
  -- Harbor Lights
  (@judge3_id, @band_harbor,   4, 24, 1), (@judge3_id, @band_harbor,   5, 19, 1),
  (@judge3_id, @band_harbor,   6, 15, 1), (@judge3_id, @band_harbor,   7, 7,  1),
  (@judge3_id, @band_harbor,   8, 11, 1);

-- Judges 4–10 reuse the same three patterns above so all judges
-- have finalized grand-finals scores for every band.

-- Judge 4 (clone Judge 3 pattern)
INSERT INTO scores (judge_id, band_id, criteria_id, score, is_finalized) VALUES
  (@judge4_id, @band_solaris,  4, 30, 1), (@judge4_id, @band_solaris,  5, 24, 1),
  (@judge4_id, @band_solaris,  6, 19, 1), (@judge4_id, @band_solaris,  7, 10, 1),
  (@judge4_id, @band_solaris,  8, 15, 1),
  (@judge4_id, @band_aurora,   4, 29, 1), (@judge4_id, @band_aurora,   5, 24, 1),
  (@judge4_id, @band_aurora,   6, 19, 1), (@judge4_id, @band_aurora,   7, 9,  1),
  (@judge4_id, @band_aurora,   8, 14, 1),
  (@judge4_id, @band_midnight, 4, 27, 1), (@judge4_id, @band_midnight, 5, 22, 1),
  (@judge4_id, @band_midnight, 6, 17, 1), (@judge4_id, @band_midnight, 7, 8,  1),
  (@judge4_id, @band_midnight, 8, 13, 1),
  (@judge4_id, @band_silver,   4, 27, 1), (@judge4_id, @band_silver,   5, 22, 1),
  (@judge4_id, @band_silver,   6, 17, 1), (@judge4_id, @band_silver,   7, 8,  1),
  (@judge4_id, @band_silver,   8, 13, 1),
  (@judge4_id, @band_harbor,   4, 24, 1), (@judge4_id, @band_harbor,   5, 19, 1),
  (@judge4_id, @band_harbor,   6, 15, 1), (@judge4_id, @band_harbor,   7, 7,  1),
  (@judge4_id, @band_harbor,   8, 11, 1);

-- Judge 5 (clone Judge 2 pattern)
INSERT INTO scores (judge_id, band_id, criteria_id, score, is_finalized) VALUES
  (@judge5_id, @band_solaris,  4, 28, 1), (@judge5_id, @band_solaris,  5, 24, 1),
  (@judge5_id, @band_solaris,  6, 18, 1), (@judge5_id, @band_solaris,  7, 9,  1),
  (@judge5_id, @band_solaris,  8, 14, 1),
  (@judge5_id, @band_aurora,   4, 29, 1), (@judge5_id, @band_aurora,   5, 24, 1),
  (@judge5_id, @band_aurora,   6, 19, 1), (@judge5_id, @band_aurora,   7, 9,  1),
  (@judge5_id, @band_aurora,   8, 14, 1),
  (@judge5_id, @band_midnight, 4, 27, 1), (@judge5_id, @band_midnight, 5, 23, 1),
  (@judge5_id, @band_midnight, 6, 18, 1), (@judge5_id, @band_midnight, 7, 8,  1),
  (@judge5_id, @band_midnight, 8, 13, 1),
  (@judge5_id, @band_silver,   4, 27, 1), (@judge5_id, @band_silver,   5, 23, 1),
  (@judge5_id, @band_silver,   6, 18, 1), (@judge5_id, @band_silver,   7, 8,  1),
  (@judge5_id, @band_silver,   8, 13, 1),
  (@judge5_id, @band_harbor,   4, 24, 1), (@judge5_id, @band_harbor,   5, 19, 1),
  (@judge5_id, @band_harbor,   6, 15, 1), (@judge5_id, @band_harbor,   7, 7,  1),
  (@judge5_id, @band_harbor,   8, 11, 1);

-- Judge 6 (clone Judge 1 pattern)
INSERT INTO scores (judge_id, band_id, criteria_id, score, is_finalized) VALUES
  (@judge6_id, @band_solaris,  4, 29, 1), (@judge6_id, @band_solaris,  5, 24, 1),
  (@judge6_id, @band_solaris,  6, 19, 1), (@judge6_id, @band_solaris,  7, 9,  1),
  (@judge6_id, @band_solaris,  8, 14, 1),
  (@judge6_id, @band_aurora,   4, 28, 1), (@judge6_id, @band_aurora,   5, 24, 1),
  (@judge6_id, @band_aurora,   6, 18, 1), (@judge6_id, @band_aurora,   7, 9,  1),
  (@judge6_id, @band_aurora,   8, 14, 1),
  (@judge6_id, @band_midnight, 4, 26, 1), (@judge6_id, @band_midnight, 5, 22, 1),
  (@judge6_id, @band_midnight, 6, 17, 1), (@judge6_id, @band_midnight, 7, 8,  1),
  (@judge6_id, @band_midnight, 8, 13, 1),
  (@judge6_id, @band_silver,   4, 26, 1), (@judge6_id, @band_silver,   5, 22, 1),
  (@judge6_id, @band_silver,   6, 17, 1), (@judge6_id, @band_silver,   7, 8,  1),
  (@judge6_id, @band_silver,   8, 13, 1),
  (@judge6_id, @band_harbor,   4, 24, 1), (@judge6_id, @band_harbor,   5, 20, 1),
  (@judge6_id, @band_harbor,   6, 15, 1), (@judge6_id, @band_harbor,   7, 7,  1),
  (@judge6_id, @band_harbor,   8, 11, 1);

-- Judge 7 (clone Judge 1 pattern)
INSERT INTO scores (judge_id, band_id, criteria_id, score, is_finalized) VALUES
  (@judge7_id, @band_solaris,  4, 29, 1), (@judge7_id, @band_solaris,  5, 24, 1),
  (@judge7_id, @band_solaris,  6, 19, 1), (@judge7_id, @band_solaris,  7, 9,  1),
  (@judge7_id, @band_solaris,  8, 14, 1),
  (@judge7_id, @band_aurora,   4, 28, 1), (@judge7_id, @band_aurora,   5, 24, 1),
  (@judge7_id, @band_aurora,   6, 18, 1), (@judge7_id, @band_aurora,   7, 9,  1),
  (@judge7_id, @band_aurora,   8, 14, 1),
  (@judge7_id, @band_midnight, 4, 26, 1), (@judge7_id, @band_midnight, 5, 22, 1),
  (@judge7_id, @band_midnight, 6, 17, 1), (@judge7_id, @band_midnight, 7, 8,  1),
  (@judge7_id, @band_midnight, 8, 13, 1),
  (@judge7_id, @band_silver,   4, 26, 1), (@judge7_id, @band_silver,   5, 22, 1),
  (@judge7_id, @band_silver,   6, 17, 1), (@judge7_id, @band_silver,   7, 8,  1),
  (@judge7_id, @band_silver,   8, 13, 1),
  (@judge7_id, @band_harbor,   4, 24, 1), (@judge7_id, @band_harbor,   5, 20, 1),
  (@judge7_id, @band_harbor,   6, 15, 1), (@judge7_id, @band_harbor,   7, 7,  1),
  (@judge7_id, @band_harbor,   8, 11, 1);

-- Judge 8 (clone Judge 2 pattern)
INSERT INTO scores (judge_id, band_id, criteria_id, score, is_finalized) VALUES
  (@judge8_id, @band_solaris,  4, 28, 1), (@judge8_id, @band_solaris,  5, 24, 1),
  (@judge8_id, @band_solaris,  6, 18, 1), (@judge8_id, @band_solaris,  7, 9,  1),
  (@judge8_id, @band_solaris,  8, 14, 1),
  (@judge8_id, @band_aurora,   4, 29, 1), (@judge8_id, @band_aurora,   5, 24, 1),
  (@judge8_id, @band_aurora,   6, 19, 1), (@judge8_id, @band_aurora,   7, 9,  1),
  (@judge8_id, @band_aurora,   8, 14, 1),
  (@judge8_id, @band_midnight, 4, 27, 1), (@judge8_id, @band_midnight, 5, 23, 1),
  (@judge8_id, @band_midnight, 6, 18, 1), (@judge8_id, @band_midnight, 7, 8,  1),
  (@judge8_id, @band_midnight, 8, 13, 1),
  (@judge8_id, @band_silver,   4, 27, 1), (@judge8_id, @band_silver,   5, 23, 1),
  (@judge8_id, @band_silver,   6, 18, 1), (@judge8_id, @band_silver,   7, 8,  1),
  (@judge8_id, @band_silver,   8, 13, 1),
  (@judge8_id, @band_harbor,   4, 24, 1), (@judge8_id, @band_harbor,   5, 19, 1),
  (@judge8_id, @band_harbor,   6, 15, 1), (@judge8_id, @band_harbor,   7, 7,  1),
  (@judge8_id, @band_harbor,   8, 11, 1);

-- Judge 9 (clone Judge 3 pattern)
INSERT INTO scores (judge_id, band_id, criteria_id, score, is_finalized) VALUES
  (@judge9_id, @band_solaris,  4, 30, 1), (@judge9_id, @band_solaris,  5, 24, 1),
  (@judge9_id, @band_solaris,  6, 19, 1), (@judge9_id, @band_solaris,  7, 10, 1),
  (@judge9_id, @band_solaris,  8, 15, 1),
  (@judge9_id, @band_aurora,   4, 29, 1), (@judge9_id, @band_aurora,   5, 24, 1),
  (@judge9_id, @band_aurora,   6, 19, 1), (@judge9_id, @band_aurora,   7, 9,  1),
  (@judge9_id, @band_aurora,   8, 14, 1),
  (@judge9_id, @band_midnight, 4, 27, 1), (@judge9_id, @band_midnight, 5, 22, 1),
  (@judge9_id, @band_midnight, 6, 17, 1), (@judge9_id, @band_midnight, 7, 8,  1),
  (@judge9_id, @band_midnight, 8, 13, 1),
  (@judge9_id, @band_silver,   4, 27, 1), (@judge9_id, @band_silver,   5, 22, 1),
  (@judge9_id, @band_silver,   6, 17, 1), (@judge9_id, @band_silver,   7, 8,  1),
  (@judge9_id, @band_silver,   8, 13, 1),
  (@judge9_id, @band_harbor,   4, 24, 1), (@judge9_id, @band_harbor,   5, 19, 1),
  (@judge9_id, @band_harbor,   6, 15, 1), (@judge9_id, @band_harbor,   7, 7,  1),
  (@judge9_id, @band_harbor,   8, 11, 1);

-- Judge 10 (clone Judge 1 pattern)
INSERT INTO scores (judge_id, band_id, criteria_id, score, is_finalized) VALUES
  (@judge10_id, @band_solaris,  4, 29, 1), (@judge10_id, @band_solaris,  5, 24, 1),
  (@judge10_id, @band_solaris,  6, 19, 1), (@judge10_id, @band_solaris,  7, 9,  1),
  (@judge10_id, @band_solaris,  8, 14, 1),
  (@judge10_id, @band_aurora,   4, 28, 1), (@judge10_id, @band_aurora,   5, 24, 1),
  (@judge10_id, @band_aurora,   6, 18, 1), (@judge10_id, @band_aurora,   7, 9,  1),
  (@judge10_id, @band_aurora,   8, 14, 1),
  (@judge10_id, @band_midnight, 4, 26, 1), (@judge10_id, @band_midnight, 5, 22, 1),
  (@judge10_id, @band_midnight, 6, 17, 1), (@judge10_id, @band_midnight, 7, 8,  1),
  (@judge10_id, @band_midnight, 8, 13, 1),
  (@judge10_id, @band_silver,   4, 26, 1), (@judge10_id, @band_silver,   5, 22, 1),
  (@judge10_id, @band_silver,   6, 17, 1), (@judge10_id, @band_silver,   7, 8,  1),
  (@judge10_id, @band_silver,   8, 13, 1),
  (@judge10_id, @band_harbor,   4, 24, 1), (@judge10_id, @band_harbor,   5, 20, 1),
  (@judge10_id, @band_harbor,   6, 15, 1), (@judge10_id, @band_harbor,   7, 7,  1),
  (@judge10_id, @band_harbor,   8, 11, 1);

COMMIT;

-- Quick reference (approximate totals per judge, max = 100):
--   Solaris Drift  ≈ high 80s / low 90s, edging out Aurora by 1–2 pts overall
--   Aurora Bloom   ≈ just behind Solaris
--   Midnight Riff  ≈ tightly clustered with Silver Echo (near‑tie mid‑pack)
--   Silver Echo    ≈ tightly clustered with Midnight Riff
--   Harbor Lights  ≈ clearly behind rest of the field

