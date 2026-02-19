-- ==================================================
-- Stress test scenarios targeted:
--   1) Perfect Sweep & Tie Check (Elimination):
--      - Lyrical Nova receives near-perfect 100s from every judge
--      - Neon Pulse and Prismatic Tide end with identical totals to verify tie-aware rankings
--   2) Photo Finish Finals:
--      - Solaris Drift vs Aurora Bloom separated by a single point across 3 judges
--   3) Depth Check:
--      - Large slate of bands to verify pagination and top-N logic
--   4) Multi-Judge Variance:
--      - Three judges with distinct scoring styles to surface averaging precision (two decimals)
--   5) Large-field tie:
--      - 15 total bands with a 4-way tie cluster in the elimination round
-- ==================================================
--
-- Use in phpMyAdmin or mysql CLI:  SOURCE test_data.sql;
-- This will:
--   * Reset scores, bands, and judge users (keeps admin)
--   * Insert 15 bands (10 elimination, 5 grand finals)
--   * Insert 10 judges with bcrypt-hashed passwords
--   * Insert sample finalized scores for both rounds covering the scenarios above
-- Admin stays: admin@botb.com / Admin@2026
-- Judge creds:
--   - judge1@botb.com  / Judge1@2026
--   - judge2@botb.com  / Judge2@2026
--   - judge3@botb.com  / Judge3@2026
--   - judge4@botb.com  / Judge4@2026
--   - judge5@botb.com  / Judge5@2026
--   - judge6@botb.com  / Judge6@2026
--   - judge7@botb.com  / Judge7@2026
--   - judge8@botb.com  / Judge8@2026
--   - judge9@botb.com  / Judge9@2026
--   - judge10@botb.com / Judge10@2026
-- ==================================================

USE botb_tabulator;
SET FOREIGN_KEY_CHECKS = 0;

-- Clear data (keep rounds and criteria)
DELETE FROM scores;
DELETE FROM bands;
DELETE FROM users WHERE role = 'judge';

-- Reset AUTO_INCREMENT so IDs are predictable (admin assumed id=1)
ALTER TABLE users AUTO_INCREMENT = 1;
ALTER TABLE bands AUTO_INCREMENT = 1;
ALTER TABLE scores AUTO_INCREMENT = 1;

SET FOREIGN_KEY_CHECKS = 1;

INSERT INTO users (name, email, password, role) VALUES
  ('Judge One',   'judge1@botb.com', '$2y$12$4v2e6mzf81kywmQYwD73FO3eSEBhJq7wRmv4lwpTjshV1W9rv3tt2', 'judge');
SET @judge1_id = LAST_INSERT_ID();

INSERT INTO users (name, email, password, role) VALUES
  ('Judge Two',   'judge2@botb.com', '$2y$12$C/BwIQM4yygb5LTFpC/4qeJjG29RaWdCm6DxjPEx6C5ps2wVjPlx2', 'judge');
SET @judge2_id = LAST_INSERT_ID();

INSERT INTO users (name, email, password, role) VALUES
  ('Judge Three', 'judge3@botb.com', '$2y$12$gD6R6IdbEFyGkASx.uVKAeI9iGZJTmTO89TrCB2PzE3WeI9PREHNW', 'judge');
SET @judge3_id = LAST_INSERT_ID();

-- Additional judges for stress testing (no initial scores assigned)
INSERT INTO users (name, email, password, role) VALUES
  ('Judge Four',   'judge4@botb.com',  '$2y$12$gD6R6IdbEFyGkASx.uVKAeI9iGZJTmTO89TrCB2PzE3WeI9PREHNW', 'judge');
SET @judge4_id = LAST_INSERT_ID();

INSERT INTO users (name, email, password, role) VALUES
  ('Judge Five',   'judge5@botb.com',  '$2y$12$gD6R6IdbEFyGkASx.uVKAeI9iGZJTmTO89TrCB2PzE3WeI9PREHNW', 'judge');
SET @judge5_id = LAST_INSERT_ID();

INSERT INTO users (name, email, password, role) VALUES
  ('Judge Six',    'judge6@botb.com',  '$2y$12$gD6R6IdbEFyGkASx.uVKAeI9iGZJTmTO89TrCB2PzE3WeI9PREHNW', 'judge');
SET @judge6_id = LAST_INSERT_ID();

INSERT INTO users (name, email, password, role) VALUES
  ('Judge Seven',  'judge7@botb.com',  '$2y$12$gD6R6IdbEFyGkASx.uVKAeI9iGZJTmTO89TrCB2PzE3WeI9PREHNW', 'judge');
SET @judge7_id = LAST_INSERT_ID();

INSERT INTO users (name, email, password, role) VALUES
  ('Judge Eight',  'judge8@botb.com',  '$2y$12$gD6R6IdbEFyGkASx.uVKAeI9iGZJTmTO89TrCB2PzE3WeI9PREHNW', 'judge');
SET @judge8_id = LAST_INSERT_ID();

INSERT INTO users (name, email, password, role) VALUES
  ('Judge Nine',   'judge9@botb.com',  '$2y$12$gD6R6IdbEFyGkASx.uVKAeI9iGZJTmTO89TrCB2PzE3WeI9PREHNW', 'judge');
SET @judge9_id = LAST_INSERT_ID();

INSERT INTO users (name, email, password, role) VALUES
  ('Judge Ten',    'judge10@botb.com', '$2y$12$gD6R6IdbEFyGkASx.uVKAeI9iGZJTmTO89TrCB2PzE3WeI9PREHNW', 'judge');
SET @judge10_id = LAST_INSERT_ID();

INSERT INTO bands (name, round_id, performance_order, is_active) VALUES
  -- Elimination round
  ('Echo Forge',      1, 1, 0),
  ('Neon Pulse',      1, 2, 0),
  ('Prismatic Tide',  1, 3, 0),
  ('Lyrical Nova',    1, 4, 0),
  ('Crimson Horizon', 1, 5, 0),
  ('Velvet Storm',    1, 6, 0),
  ('Golden Reverb',   1, 7, 0),
  ('Static Pulse',    1, 8, 0),
  ('Emberline',       1, 9, 0),
  ('Pulse Theory',    1, 10, 0),
  -- Grand finals
  ('Solaris Drift',   2, 1, 0),
  ('Aurora Bloom',    2, 2, 0),
  ('Midnight Riff',   2, 3, 0),
  ('Silver Echo',     2, 4, 0),
  ('Harbor Lights',   2, 5, 0);

-- Sample scores (finalized = 1)
-- Elimination criteria IDs: 1 Musicality (50), 2 Originality (30), 3 Stage Presence (20)
-- Grand Finals criteria IDs: 4 Musicality (30), 5 Creativity & Originality (25), 6 Stage Presence & Audience Engagement (20), 7 Overall Impact (10), 8 Original Composition (15)

-- Scores are now capped at the criteria weight (not 0-100)
-- Elimination: Musicality(50), Originality(30), Stage Presence(20)
-- Grand Finals: Musicality(30), Creativity(25), Stage Presence(20), Overall Impact(10), Original Composition(15)

-- Judge 1 scores
INSERT INTO scores (judge_id, band_id, criteria_id, score, is_finalized) VALUES
  -- Elimination (criteria 1-3)
  (@judge1_id, 1, 1, 45, 1), (@judge1_id, 1, 2, 26, 1), (@judge1_id, 1, 3, 18, 1),   -- Echo Forge
  (@judge1_id, 2, 1, 42, 1), (@judge1_id, 2, 2, 22, 1), (@judge1_id, 2, 3, 17, 1),   -- Neon Pulse
  (@judge1_id, 3, 1, 42, 1), (@judge1_id, 3, 2, 22, 1), (@judge1_id, 3, 3, 17, 1),   -- Prismatic Tide (tie with Neon)
  (@judge1_id, 4, 1, 50, 1), (@judge1_id, 4, 2, 30, 1), (@judge1_id, 4, 3, 20, 1),   -- Lyrical Nova perfect
  (@judge1_id, 5, 1, 34, 1), (@judge1_id, 5, 2, 20, 1), (@judge1_id, 5, 3, 15, 1),   -- Crimson Horizon
  (@judge1_id, 6, 1, 39, 1), (@judge1_id, 6, 2, 21, 1), (@judge1_id, 6, 3, 16, 1),   -- Velvet Storm
  (@judge1_id, 7, 1, 35, 1), (@judge1_id, 7, 2, 20, 1), (@judge1_id, 7, 3, 15, 1),   -- Golden Reverb (part of 4-way tie)
  (@judge1_id, 8, 1, 35, 1), (@judge1_id, 8, 2, 20, 1), (@judge1_id, 8, 3, 15, 1),   -- Static Pulse tie set
  (@judge1_id, 9, 1, 35, 1), (@judge1_id, 9, 2, 20, 1), (@judge1_id, 9, 3, 15, 1),   -- Emberline tie set
  (@judge1_id, 10, 1, 35, 1), (@judge1_id, 10, 2, 20, 1), (@judge1_id, 10, 3, 15, 1), -- Pulse Theory tie set
  -- Grand Finals (criteria 4-8)
  (@judge1_id, 11, 4, 28, 1), (@judge1_id, 11, 5, 23, 1), (@judge1_id, 11, 6, 18, 1), (@judge1_id, 11, 7, 9, 1), (@judge1_id, 11, 8, 13, 1), -- Solaris Drift
  (@judge1_id, 12, 4, 29, 1), (@judge1_id, 12, 5, 22, 1), (@judge1_id, 12, 6, 19, 1), (@judge1_id, 12, 7, 9, 1), (@judge1_id, 12, 8, 14, 1), -- Aurora Bloom
  (@judge1_id, 13, 4, 26, 1), (@judge1_id, 13, 5, 20, 1), (@judge1_id, 13, 6, 16, 1), (@judge1_id, 13, 7, 8, 1), (@judge1_id, 13, 8, 12, 1), -- Midnight Riff
  (@judge1_id, 14, 4, 27, 1), (@judge1_id, 14, 5, 22, 1), (@judge1_id, 14, 6, 17, 1), (@judge1_id, 14, 7, 9, 1), (@judge1_id, 14, 8, 13, 1), -- Silver Echo
  (@judge1_id, 15, 4, 24, 1), (@judge1_id, 15, 5, 19, 1), (@judge1_id, 15, 6, 15, 1), (@judge1_id, 15, 7, 7, 1), (@judge1_id, 15, 8, 11, 1); -- Harbor Lights

-- Judge 2 scores
INSERT INTO scores (judge_id, band_id, criteria_id, score, is_finalized) VALUES
  -- Elimination
  (@judge2_id, 1, 1, 46, 1), (@judge2_id, 1, 2, 25, 1), (@judge2_id, 1, 3, 18, 1),   -- Echo Forge
  (@judge2_id, 2, 1, 43, 1), (@judge2_id, 2, 2, 24, 1), (@judge2_id, 2, 3, 18, 1),   -- Neon Pulse
  (@judge2_id, 3, 1, 41, 1), (@judge2_id, 3, 2, 25, 1), (@judge2_id, 3, 3, 19, 1),   -- Prismatic Tide
  (@judge2_id, 4, 1, 49, 1), (@judge2_id, 4, 2, 29, 1), (@judge2_id, 4, 3, 20, 1),   -- Lyrical Nova
  (@judge2_id, 5, 1, 36, 1), (@judge2_id, 5, 2, 19, 1), (@judge2_id, 5, 3, 16, 1),   -- Crimson Horizon
  (@judge2_id, 6, 1, 38, 1), (@judge2_id, 6, 2, 22, 1), (@judge2_id, 6, 3, 16, 1),   -- Velvet Storm
  (@judge2_id, 7, 1, 35, 1), (@judge2_id, 7, 2, 20, 1), (@judge2_id, 7, 3, 15, 1),   -- Golden Reverb (part of 4-way tie)
  (@judge2_id, 8, 1, 34, 1), (@judge2_id, 8, 2, 21, 1), (@judge2_id, 8, 3, 15, 1),   -- Static Pulse tie set
  (@judge2_id, 9, 1, 34, 1), (@judge2_id, 9, 2, 21, 1), (@judge2_id, 9, 3, 15, 1),   -- Emberline tie set
  (@judge2_id, 10, 1, 34, 1), (@judge2_id, 10, 2, 21, 1), (@judge2_id, 10, 3, 15, 1), -- Pulse Theory tie set
  -- Grand Finals
  (@judge2_id, 11, 4, 29, 1), (@judge2_id, 11, 5, 24, 1), (@judge2_id, 11, 6, 19, 1), (@judge2_id, 11, 7, 9, 1), (@judge2_id, 11, 8, 14, 1), -- Solaris Drift
  (@judge2_id, 12, 4, 28, 1), (@judge2_id, 12, 5, 24, 1), (@judge2_id, 12, 6, 18, 1), (@judge2_id, 12, 7, 9, 1), (@judge2_id, 12, 8, 13, 1), -- Aurora Bloom
  (@judge2_id, 13, 4, 25, 1), (@judge2_id, 13, 5, 19, 1), (@judge2_id, 13, 6, 17, 1), (@judge2_id, 13, 7, 8, 1), (@judge2_id, 13, 8, 11, 1), -- Midnight Riff
  (@judge2_id, 14, 4, 27, 1), (@judge2_id, 14, 5, 23, 1), (@judge2_id, 14, 6, 17, 1), (@judge2_id, 14, 7, 9, 1), (@judge2_id, 14, 8, 14, 1), -- Silver Echo
  (@judge2_id, 15, 4, 23, 1), (@judge2_id, 15, 5, 19, 1), (@judge2_id, 15, 6, 15, 1), (@judge2_id, 15, 7, 7, 1), (@judge2_id, 15, 8, 11, 1); -- Harbor Lights

-- Judge 3 scores
INSERT INTO scores (judge_id, band_id, criteria_id, score, is_finalized) VALUES
  -- Elimination
  (@judge3_id, 1, 1, 47, 1), (@judge3_id, 1, 2, 27, 1), (@judge3_id, 1, 3, 19, 1),   -- Echo Forge
  (@judge3_id, 2, 1, 44, 1), (@judge3_id, 2, 2, 23, 1), (@judge3_id, 2, 3, 18, 1),   -- Neon Pulse
  (@judge3_id, 3, 1, 44, 1), (@judge3_id, 3, 2, 23, 1), (@judge3_id, 3, 3, 18, 1),   -- Prismatic Tide
  (@judge3_id, 4, 1, 50, 1), (@judge3_id, 4, 2, 30, 1), (@judge3_id, 4, 3, 20, 1),   -- Lyrical Nova perfect repeat
  (@judge3_id, 5, 1, 35, 1), (@judge3_id, 5, 2, 18, 1), (@judge3_id, 5, 3, 14, 1),   -- Crimson Horizon
  (@judge3_id, 6, 1, 40, 1), (@judge3_id, 6, 2, 21, 1), (@judge3_id, 6, 3, 15, 1),   -- Velvet Storm
  (@judge3_id, 7, 1, 35, 1), (@judge3_id, 7, 2, 20, 1), (@judge3_id, 7, 3, 15, 1),   -- Golden Reverb (part of 4-way tie)
  (@judge3_id, 8, 1, 33, 1), (@judge3_id, 8, 2, 22, 1), (@judge3_id, 8, 3, 15, 1),   -- Static Pulse tie set
  (@judge3_id, 9, 1, 33, 1), (@judge3_id, 9, 2, 22, 1), (@judge3_id, 9, 3, 15, 1),   -- Emberline tie set
  (@judge3_id, 10, 1, 33, 1), (@judge3_id, 10, 2, 22, 1), (@judge3_id, 10, 3, 15, 1), -- Pulse Theory tie set
  -- Grand Finals
  (@judge3_id, 11, 4, 30, 1), (@judge3_id, 11, 5, 24, 1), (@judge3_id, 11, 6, 19, 1), (@judge3_id, 11, 7, 10, 1), (@judge3_id, 11, 8, 15, 1), -- Solaris Drift
  (@judge3_id, 12, 4, 29, 1), (@judge3_id, 12, 5, 25, 1), (@judge3_id, 12, 6, 19, 1), (@judge3_id, 12, 7, 9, 1), (@judge3_id, 12, 8, 14, 1), -- Aurora Bloom
  (@judge3_id, 13, 4, 26, 1), (@judge3_id, 13, 5, 18, 1), (@judge3_id, 13, 6, 16, 1), (@judge3_id, 13, 7, 8, 1), (@judge3_id, 13, 8, 10, 1), -- Midnight Riff
  (@judge3_id, 14, 4, 27, 1), (@judge3_id, 14, 5, 23, 1), (@judge3_id, 14, 6, 17, 1), (@judge3_id, 14, 7, 9, 1), (@judge3_id, 14, 8, 13, 1), -- Silver Echo
  (@judge3_id, 15, 4, 24, 1), (@judge3_id, 15, 5, 18, 1), (@judge3_id, 15, 6, 15, 1), (@judge3_id, 15, 7, 7, 1), (@judge3_id, 15, 8, 11, 1); -- Harbor Lights

-- Judge 4 scores (clone Judge 3 pattern)
INSERT INTO scores (judge_id, band_id, criteria_id, score, is_finalized) VALUES
  -- Elimination
  (@judge4_id, 1, 1, 47, 1), (@judge4_id, 1, 2, 27, 1), (@judge4_id, 1, 3, 19, 1),
  (@judge4_id, 2, 1, 44, 1), (@judge4_id, 2, 2, 23, 1), (@judge4_id, 2, 3, 18, 1),
  (@judge4_id, 3, 1, 44, 1), (@judge4_id, 3, 2, 23, 1), (@judge4_id, 3, 3, 18, 1),
  (@judge4_id, 4, 1, 50, 1), (@judge4_id, 4, 2, 30, 1), (@judge4_id, 4, 3, 20, 1),
  (@judge4_id, 5, 1, 35, 1), (@judge4_id, 5, 2, 18, 1), (@judge4_id, 5, 3, 14, 1),
  (@judge4_id, 6, 1, 40, 1), (@judge4_id, 6, 2, 21, 1), (@judge4_id, 6, 3, 15, 1),
  (@judge4_id, 7, 1, 35, 1), (@judge4_id, 7, 2, 20, 1), (@judge4_id, 7, 3, 15, 1),
  (@judge4_id, 8, 1, 33, 1), (@judge4_id, 8, 2, 22, 1), (@judge4_id, 8, 3, 15, 1),
  (@judge4_id, 9, 1, 33, 1), (@judge4_id, 9, 2, 22, 1), (@judge4_id, 9, 3, 15, 1),
  (@judge4_id, 10, 1, 33, 1), (@judge4_id, 10, 2, 22, 1), (@judge4_id, 10, 3, 15, 1),
  -- Grand Finals
  (@judge4_id, 11, 4, 30, 1), (@judge4_id, 11, 5, 24, 1), (@judge4_id, 11, 6, 19, 1), (@judge4_id, 11, 7, 10, 1), (@judge4_id, 11, 8, 15, 1),
  (@judge4_id, 12, 4, 29, 1), (@judge4_id, 12, 5, 25, 1), (@judge4_id, 12, 6, 19, 1), (@judge4_id, 12, 7, 9, 1), (@judge4_id, 12, 8, 14, 1),
  (@judge4_id, 13, 4, 26, 1), (@judge4_id, 13, 5, 18, 1), (@judge4_id, 13, 6, 16, 1), (@judge4_id, 13, 7, 8, 1), (@judge4_id, 13, 8, 10, 1),
  (@judge4_id, 14, 4, 27, 1), (@judge4_id, 14, 5, 23, 1), (@judge4_id, 14, 6, 17, 1), (@judge4_id, 14, 7, 9, 1), (@judge4_id, 14, 8, 13, 1),
  (@judge4_id, 15, 4, 24, 1), (@judge4_id, 15, 5, 18, 1), (@judge4_id, 15, 6, 15, 1), (@judge4_id, 15, 7, 7, 1), (@judge4_id, 15, 8, 11, 1);

-- Judge 5 scores (clone Judge 2 pattern)
INSERT INTO scores (judge_id, band_id, criteria_id, score, is_finalized) VALUES
  -- Elimination
  (@judge5_id, 1, 1, 46, 1), (@judge5_id, 1, 2, 25, 1), (@judge5_id, 1, 3, 18, 1),
  (@judge5_id, 2, 1, 43, 1), (@judge5_id, 2, 2, 24, 1), (@judge5_id, 2, 3, 18, 1),
  (@judge5_id, 3, 1, 41, 1), (@judge5_id, 3, 2, 25, 1), (@judge5_id, 3, 3, 19, 1),
  (@judge5_id, 4, 1, 49, 1), (@judge5_id, 4, 2, 29, 1), (@judge5_id, 4, 3, 20, 1),
  (@judge5_id, 5, 1, 36, 1), (@judge5_id, 5, 2, 19, 1), (@judge5_id, 5, 3, 16, 1),
  (@judge5_id, 6, 1, 38, 1), (@judge5_id, 6, 2, 22, 1), (@judge5_id, 6, 3, 16, 1),
  (@judge5_id, 7, 1, 35, 1), (@judge5_id, 7, 2, 20, 1), (@judge5_id, 7, 3, 15, 1),
  (@judge5_id, 8, 1, 34, 1), (@judge5_id, 8, 2, 21, 1), (@judge5_id, 8, 3, 15, 1),
  (@judge5_id, 9, 1, 34, 1), (@judge5_id, 9, 2, 21, 1), (@judge5_id, 9, 3, 15, 1),
  (@judge5_id, 10, 1, 34, 1), (@judge5_id, 10, 2, 21, 1), (@judge5_id, 10, 3, 15, 1),
  -- Grand Finals
  (@judge5_id, 11, 4, 29, 1), (@judge5_id, 11, 5, 24, 1), (@judge5_id, 11, 6, 19, 1), (@judge5_id, 11, 7, 9, 1), (@judge5_id, 11, 8, 14, 1),
  (@judge5_id, 12, 4, 28, 1), (@judge5_id, 12, 5, 24, 1), (@judge5_id, 12, 6, 18, 1), (@judge5_id, 12, 7, 9, 1), (@judge5_id, 12, 8, 13, 1),
  (@judge5_id, 13, 4, 25, 1), (@judge5_id, 13, 5, 19, 1), (@judge5_id, 13, 6, 17, 1), (@judge5_id, 13, 7, 8, 1), (@judge5_id, 13, 8, 11, 1),
  (@judge5_id, 14, 4, 27, 1), (@judge5_id, 14, 5, 23, 1), (@judge5_id, 14, 6, 17, 1), (@judge5_id, 14, 7, 9, 1), (@judge5_id, 14, 8, 14, 1),
  (@judge5_id, 15, 4, 23, 1), (@judge5_id, 15, 5, 19, 1), (@judge5_id, 15, 6, 15, 1), (@judge5_id, 15, 7, 7, 1), (@judge5_id, 15, 8, 11, 1);

-- Judge 6 scores (clone Judge 1 pattern)
INSERT INTO scores (judge_id, band_id, criteria_id, score, is_finalized) VALUES
  -- Elimination
  (@judge6_id, 1, 1, 45, 1), (@judge6_id, 1, 2, 26, 1), (@judge6_id, 1, 3, 18, 1),
  (@judge6_id, 2, 1, 42, 1), (@judge6_id, 2, 2, 22, 1), (@judge6_id, 2, 3, 17, 1),
  (@judge6_id, 3, 1, 42, 1), (@judge6_id, 3, 2, 22, 1), (@judge6_id, 3, 3, 17, 1),
  (@judge6_id, 4, 1, 50, 1), (@judge6_id, 4, 2, 30, 1), (@judge6_id, 4, 3, 20, 1),
  (@judge6_id, 5, 1, 34, 1), (@judge6_id, 5, 2, 20, 1), (@judge6_id, 5, 3, 15, 1),
  (@judge6_id, 6, 1, 39, 1), (@judge6_id, 6, 2, 21, 1), (@judge6_id, 6, 3, 16, 1),
  (@judge6_id, 7, 1, 35, 1), (@judge6_id, 7, 2, 20, 1), (@judge6_id, 7, 3, 15, 1),
  (@judge6_id, 8, 1, 35, 1), (@judge6_id, 8, 2, 20, 1), (@judge6_id, 8, 3, 15, 1),
  (@judge6_id, 9, 1, 35, 1), (@judge6_id, 9, 2, 20, 1), (@judge6_id, 9, 3, 15, 1),
  (@judge6_id, 10, 1, 35, 1), (@judge6_id, 10, 2, 20, 1), (@judge6_id, 10, 3, 15, 1),
  -- Grand Finals
  (@judge6_id, 11, 4, 28, 1), (@judge6_id, 11, 5, 23, 1), (@judge6_id, 11, 6, 18, 1), (@judge6_id, 11, 7, 9, 1), (@judge6_id, 11, 8, 13, 1),
  (@judge6_id, 12, 4, 29, 1), (@judge6_id, 12, 5, 22, 1), (@judge6_id, 12, 6, 19, 1), (@judge6_id, 12, 7, 9, 1), (@judge6_id, 12, 8, 14, 1),
  (@judge6_id, 13, 4, 26, 1), (@judge6_id, 13, 5, 20, 1), (@judge6_id, 13, 6, 16, 1), (@judge6_id, 13, 7, 8, 1), (@judge6_id, 13, 8, 12, 1),
  (@judge6_id, 14, 4, 27, 1), (@judge6_id, 14, 5, 22, 1), (@judge6_id, 14, 6, 17, 1), (@judge6_id, 14, 7, 9, 1), (@judge6_id, 14, 8, 13, 1),
  (@judge6_id, 15, 4, 24, 1), (@judge6_id, 15, 5, 19, 1), (@judge6_id, 15, 6, 15, 1), (@judge6_id, 15, 7, 7, 1), (@judge6_id, 15, 8, 11, 1);

-- Judge 7 scores (clone Judge 1 pattern)
INSERT INTO scores (judge_id, band_id, criteria_id, score, is_finalized) VALUES
  -- Elimination
  (@judge7_id, 1, 1, 45, 1), (@judge7_id, 1, 2, 26, 1), (@judge7_id, 1, 3, 18, 1),
  (@judge7_id, 2, 1, 42, 1), (@judge7_id, 2, 2, 22, 1), (@judge7_id, 2, 3, 17, 1),
  (@judge7_id, 3, 1, 42, 1), (@judge7_id, 3, 2, 22, 1), (@judge7_id, 3, 3, 17, 1),
  (@judge7_id, 4, 1, 50, 1), (@judge7_id, 4, 2, 30, 1), (@judge7_id, 4, 3, 20, 1),
  (@judge7_id, 5, 1, 34, 1), (@judge7_id, 5, 2, 20, 1), (@judge7_id, 5, 3, 15, 1),
  (@judge7_id, 6, 1, 39, 1), (@judge7_id, 6, 2, 21, 1), (@judge7_id, 6, 3, 16, 1),
  (@judge7_id, 7, 1, 35, 1), (@judge7_id, 7, 2, 20, 1), (@judge7_id, 7, 3, 15, 1),
  (@judge7_id, 8, 1, 35, 1), (@judge7_id, 8, 2, 20, 1), (@judge7_id, 8, 3, 15, 1),
  (@judge7_id, 9, 1, 35, 1), (@judge7_id, 9, 2, 20, 1), (@judge7_id, 9, 3, 15, 1),
  (@judge7_id, 10, 1, 35, 1), (@judge7_id, 10, 2, 20, 1), (@judge7_id, 10, 3, 15, 1),
  -- Grand Finals
  (@judge7_id, 11, 4, 28, 1), (@judge7_id, 11, 5, 23, 1), (@judge7_id, 11, 6, 18, 1), (@judge7_id, 11, 7, 9, 1), (@judge7_id, 11, 8, 13, 1),
  (@judge7_id, 12, 4, 29, 1), (@judge7_id, 12, 5, 22, 1), (@judge7_id, 12, 6, 19, 1), (@judge7_id, 12, 7, 9, 1), (@judge7_id, 12, 8, 14, 1),
  (@judge7_id, 13, 4, 26, 1), (@judge7_id, 13, 5, 20, 1), (@judge7_id, 13, 6, 16, 1), (@judge7_id, 13, 7, 8, 1), (@judge7_id, 13, 8, 12, 1),
  (@judge7_id, 14, 4, 27, 1), (@judge7_id, 14, 5, 22, 1), (@judge7_id, 14, 6, 17, 1), (@judge7_id, 14, 7, 9, 1), (@judge7_id, 14, 8, 13, 1),
  (@judge7_id, 15, 4, 24, 1), (@judge7_id, 15, 5, 19, 1), (@judge7_id, 15, 6, 15, 1), (@judge7_id, 15, 7, 7, 1), (@judge7_id, 15, 8, 11, 1);

-- Judge 8 scores (clone Judge 2 pattern)
INSERT INTO scores (judge_id, band_id, criteria_id, score, is_finalized) VALUES
  -- Elimination
  (@judge8_id, 1, 1, 46, 1), (@judge8_id, 1, 2, 25, 1), (@judge8_id, 1, 3, 18, 1),
  (@judge8_id, 2, 1, 43, 1), (@judge8_id, 2, 2, 24, 1), (@judge8_id, 2, 3, 18, 1),
  (@judge8_id, 3, 1, 41, 1), (@judge8_id, 3, 2, 25, 1), (@judge8_id, 3, 3, 19, 1),
  (@judge8_id, 4, 1, 49, 1), (@judge8_id, 4, 2, 29, 1), (@judge8_id, 4, 3, 20, 1),
  (@judge8_id, 5, 1, 36, 1), (@judge8_id, 5, 2, 19, 1), (@judge8_id, 5, 3, 16, 1),
  (@judge8_id, 6, 1, 38, 1), (@judge8_id, 6, 2, 22, 1), (@judge8_id, 6, 3, 16, 1),
  (@judge8_id, 7, 1, 35, 1), (@judge8_id, 7, 2, 20, 1), (@judge8_id, 7, 3, 15, 1),
  (@judge8_id, 8, 1, 34, 1), (@judge8_id, 8, 2, 21, 1), (@judge8_id, 8, 3, 15, 1),
  (@judge8_id, 9, 1, 34, 1), (@judge8_id, 9, 2, 21, 1), (@judge8_id, 9, 3, 15, 1),
  (@judge8_id, 10, 1, 34, 1), (@judge8_id, 10, 2, 21, 1), (@judge8_id, 10, 3, 15, 1),
  -- Grand Finals
  (@judge8_id, 11, 4, 29, 1), (@judge8_id, 11, 5, 24, 1), (@judge8_id, 11, 6, 19, 1), (@judge8_id, 11, 7, 9, 1), (@judge8_id, 11, 8, 14, 1),
  (@judge8_id, 12, 4, 28, 1), (@judge8_id, 12, 5, 24, 1), (@judge8_id, 12, 6, 18, 1), (@judge8_id, 12, 7, 9, 1), (@judge8_id, 12, 8, 13, 1),
  (@judge8_id, 13, 4, 25, 1), (@judge8_id, 13, 5, 19, 1), (@judge8_id, 13, 6, 17, 1), (@judge8_id, 13, 7, 8, 1), (@judge8_id, 13, 8, 11, 1),
  (@judge8_id, 14, 4, 27, 1), (@judge8_id, 14, 5, 23, 1), (@judge8_id, 14, 6, 17, 1), (@judge8_id, 14, 7, 9, 1), (@judge8_id, 14, 8, 14, 1),
  (@judge8_id, 15, 4, 23, 1), (@judge8_id, 15, 5, 19, 1), (@judge8_id, 15, 6, 15, 1), (@judge8_id, 15, 7, 7, 1), (@judge8_id, 15, 8, 11, 1);

-- Judge 9 scores (clone Judge 3 pattern)
INSERT INTO scores (judge_id, band_id, criteria_id, score, is_finalized) VALUES
  -- Elimination
  (@judge9_id, 1, 1, 47, 1), (@judge9_id, 1, 2, 27, 1), (@judge9_id, 1, 3, 19, 1),
  (@judge9_id, 2, 1, 44, 1), (@judge9_id, 2, 2, 23, 1), (@judge9_id, 2, 3, 18, 1),
  (@judge9_id, 3, 1, 44, 1), (@judge9_id, 3, 2, 23, 1), (@judge9_id, 3, 3, 18, 1),
  (@judge9_id, 4, 1, 50, 1), (@judge9_id, 4, 2, 30, 1), (@judge9_id, 4, 3, 20, 1),
  (@judge9_id, 5, 1, 35, 1), (@judge9_id, 5, 2, 18, 1), (@judge9_id, 5, 3, 14, 1),
  (@judge9_id, 6, 1, 40, 1), (@judge9_id, 6, 2, 21, 1), (@judge9_id, 6, 3, 15, 1),
  (@judge9_id, 7, 1, 35, 1), (@judge9_id, 7, 2, 20, 1), (@judge9_id, 7, 3, 15, 1),
  (@judge9_id, 8, 1, 33, 1), (@judge9_id, 8, 2, 22, 1), (@judge9_id, 8, 3, 15, 1),
  (@judge9_id, 9, 1, 33, 1), (@judge9_id, 9, 2, 22, 1), (@judge9_id, 9, 3, 15, 1),
  (@judge9_id, 10, 1, 33, 1), (@judge9_id, 10, 2, 22, 1), (@judge9_id, 10, 3, 15, 1),
  -- Grand Finals
  (@judge9_id, 11, 4, 30, 1), (@judge9_id, 11, 5, 24, 1), (@judge9_id, 11, 6, 19, 1), (@judge9_id, 11, 7, 10, 1), (@judge9_id, 11, 8, 15, 1),
  (@judge9_id, 12, 4, 29, 1), (@judge9_id, 12, 5, 25, 1), (@judge9_id, 12, 6, 19, 1), (@judge9_id, 12, 7, 9, 1), (@judge9_id, 12, 8, 14, 1),
  (@judge9_id, 13, 4, 26, 1), (@judge9_id, 13, 5, 18, 1), (@judge9_id, 13, 6, 16, 1), (@judge9_id, 13, 7, 8, 1), (@judge9_id, 13, 8, 10, 1),
  (@judge9_id, 14, 4, 27, 1), (@judge9_id, 14, 5, 23, 1), (@judge9_id, 14, 6, 17, 1), (@judge9_id, 14, 7, 9, 1), (@judge9_id, 14, 8, 13, 1),
  (@judge9_id, 15, 4, 24, 1), (@judge9_id, 15, 5, 18, 1), (@judge9_id, 15, 6, 15, 1), (@judge9_id, 15, 7, 7, 1), (@judge9_id, 15, 8, 11, 1);

-- Judge 10 scores (clone Judge 1 pattern)
INSERT INTO scores (judge_id, band_id, criteria_id, score, is_finalized) VALUES
  -- Elimination
  (@judge10_id, 1, 1, 45, 1), (@judge10_id, 1, 2, 26, 1), (@judge10_id, 1, 3, 18, 1),
  (@judge10_id, 2, 1, 42, 1), (@judge10_id, 2, 2, 22, 1), (@judge10_id, 2, 3, 17, 1),
  (@judge10_id, 3, 1, 42, 1), (@judge10_id, 3, 2, 22, 1), (@judge10_id, 3, 3, 17, 1),
  (@judge10_id, 4, 1, 50, 1), (@judge10_id, 4, 2, 30, 1), (@judge10_id, 4, 3, 20, 1),
  (@judge10_id, 5, 1, 34, 1), (@judge10_id, 5, 2, 20, 1), (@judge10_id, 5, 3, 15, 1),
  (@judge10_id, 6, 1, 39, 1), (@judge10_id, 6, 2, 21, 1), (@judge10_id, 6, 3, 16, 1),
  (@judge10_id, 7, 1, 35, 1), (@judge10_id, 7, 2, 20, 1), (@judge10_id, 7, 3, 15, 1),
  (@judge10_id, 8, 1, 35, 1), (@judge10_id, 8, 2, 20, 1), (@judge10_id, 8, 3, 15, 1),
  (@judge10_id, 9, 1, 35, 1), (@judge10_id, 9, 2, 20, 1), (@judge10_id, 9, 3, 15, 1),
  (@judge10_id, 10, 1, 35, 1), (@judge10_id, 10, 2, 20, 1), (@judge10_id, 10, 3, 15, 1),
  -- Grand Finals
  (@judge10_id, 11, 4, 28, 1), (@judge10_id, 11, 5, 23, 1), (@judge10_id, 11, 6, 18, 1), (@judge10_id, 11, 7, 9, 1), (@judge10_id, 11, 8, 13, 1),
  (@judge10_id, 12, 4, 29, 1), (@judge10_id, 12, 5, 22, 1), (@judge10_id, 12, 6, 19, 1), (@judge10_id, 12, 7, 9, 1), (@judge10_id, 12, 8, 14, 1),
  (@judge10_id, 13, 4, 26, 1), (@judge10_id, 13, 5, 20, 1), (@judge10_id, 13, 6, 16, 1), (@judge10_id, 13, 7, 8, 1), (@judge10_id, 13, 8, 12, 1),
  (@judge10_id, 14, 4, 27, 1), (@judge10_id, 14, 5, 22, 1), (@judge10_id, 14, 6, 17, 1), (@judge10_id, 14, 7, 9, 1), (@judge10_id, 14, 8, 13, 1),
  (@judge10_id, 15, 4, 24, 1), (@judge10_id, 15, 5, 19, 1), (@judge10_id, 15, 6, 15, 1), (@judge10_id, 15, 7, 7, 1), (@judge10_id, 15, 8, 11, 1);

-- Optional: set an active band for live judge polling (comment out if not needed)
-- UPDATE bands SET is_active = 1 WHERE name = 'Echo Forge';

COMMIT;

-- Totals (scores are direct point values, max = criteria weight)
-- Echo Forge (Judge1): 42+25+17 = 84    (Judge2): 44+23+18 = 85    Avg: 84.50
-- Prismatic Tide (Judge1): 40+21+18 = 79 (Judge2): 39+22+17 = 78   Avg: 78.50
-- Neon Pulse (Judge1): 38+22+16 = 76    (Judge2): 40+20+15 = 75    Avg: 75.50
-- Solaris Drift (Judge1): 27+22+18+9+13 = 89  (Judge2): 28+23+17+8+14 = 90  Avg: 89.50
-- Crimson Horizon (Judge1): 24+20+16+8+12 = 80  (Judge2): 25+21+15+7+11 = 79  Avg: 79.50

-- Expected rankings / quick reference
-- Elimination (avg of 3 judges):
--   1) Lyrical Nova      — 99.33 (perfect sweep stress test)
--   2) Echo Forge        — 90.33
--   3) Neon Pulse        — 83.67  \_ tie check (identical to Prismatic Tide)
--   3) Prismatic Tide    — 83.67  /
--   5) Crimson Horizon   — 69.00
-- Grand Finals:
--   1) Solaris Drift     — 94.67 (wins by single point)
--   2) Aurora Bloom      — 93.67
--   3) Midnight Riff     — 80.00
-- Use these figures to validate ranking order, tie badges, and decimal precision.
