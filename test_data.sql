-- ==================================================
-- Battle of the Bands Tabulator — TEST DATA SEED
-- Use in phpMyAdmin or mysql CLI:  SOURCE test_data.sql;
-- This will:
--   * Reset scores, bands, and judge users (keeps admin)
--   * Insert 4 bands (2 per round) with performance order
--   * Insert 2 judges with bcrypt-hashed passwords
--   * Insert sample finalized scores for both rounds
-- Admin stays: admin@botb.com / Admin@2026
-- Judge creds: judge1@botb.com / Judge1@2026, judge2@botb.com / Judge2@2026
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

-- Insert judges (bcrypt hashes precomputed)
INSERT INTO users (name, email, password, role) VALUES
  ('Judge One',   'judge1@botb.com', '$2y$12$4v2e6mzf81kywmQYwD73FO3eSEBhJq7wRmv4lwpTjshV1W9rv3tt2', 'judge');
SET @judge1_id = LAST_INSERT_ID();

INSERT INTO users (name, email, password, role) VALUES
  ('Judge Two',   'judge2@botb.com', '$2y$12$C/BwIQM4yygb5LTFpC/4qeJjG29RaWdCm6DxjPEx6C5ps2wVjPlx2', 'judge');
SET @judge2_id = LAST_INSERT_ID();

-- Insert bands
INSERT INTO bands (name, round_id, performance_order, is_active) VALUES
  ('Echo Forge',            1, 1, 0), -- Elimination
  ('Neon Pulse',            1, 2, 0),
  ('Prismatic Tide',        1, 3, 0),
  ('Solaris Drift',         2, 1, 0), -- Grand Finals
  ('Crimson Horizon',       2, 2, 0);

-- Sample scores (finalized = 1)
-- Elimination criteria IDs: 1 Musicality (50), 2 Originality (30), 3 Stage Presence (20)
-- Grand Finals criteria IDs: 4 Musicality (30), 5 Creativity & Originality (25), 6 Stage Presence & Audience Engagement (20), 7 Overall Impact (10), 8 Original Composition (15)

-- Scores are now capped at the criteria weight (not 0-100)
-- Elimination: Musicality(50), Originality(30), Stage Presence(20)
-- Grand Finals: Musicality(30), Creativity(25), Stage Presence(20), Overall Impact(10), Original Composition(15)

-- Judge 1 scores
INSERT INTO scores (judge_id, band_id, criteria_id, score, is_finalized) VALUES
  (@judge1_id, 1, 1, 42, 1), (@judge1_id, 1, 2, 25, 1), (@judge1_id, 1, 3, 17, 1),   -- Echo Forge (total: 84)
  (@judge1_id, 2, 1, 38, 1), (@judge1_id, 2, 2, 22, 1), (@judge1_id, 2, 3, 16, 1),   -- Neon Pulse (total: 76)
  (@judge1_id, 3, 1, 40, 1), (@judge1_id, 3, 2, 21, 1), (@judge1_id, 3, 3, 18, 1),   -- Prismatic Tide (total: 79)
  (@judge1_id, 4, 4, 27, 1), (@judge1_id, 4, 5, 22, 1), (@judge1_id, 4, 6, 18, 1), (@judge1_id, 4, 7, 9, 1), (@judge1_id, 4, 8, 13, 1), -- Solaris Drift (total: 89)
  (@judge1_id, 5, 4, 24, 1), (@judge1_id, 5, 5, 20, 1), (@judge1_id, 5, 6, 16, 1), (@judge1_id, 5, 7, 8, 1), (@judge1_id, 5, 8, 12, 1); -- Crimson Horizon (total: 80)

-- Judge 2 scores
INSERT INTO scores (judge_id, band_id, criteria_id, score, is_finalized) VALUES
  (@judge2_id, 1, 1, 44, 1), (@judge2_id, 1, 2, 23, 1), (@judge2_id, 1, 3, 18, 1),   -- Echo Forge (total: 85)
  (@judge2_id, 2, 1, 40, 1), (@judge2_id, 2, 2, 20, 1), (@judge2_id, 2, 3, 15, 1),   -- Neon Pulse (total: 75)
  (@judge2_id, 3, 1, 39, 1), (@judge2_id, 3, 2, 22, 1), (@judge2_id, 3, 3, 17, 1),   -- Prismatic Tide (total: 78)
  (@judge2_id, 4, 4, 28, 1), (@judge2_id, 4, 5, 23, 1), (@judge2_id, 4, 6, 17, 1), (@judge2_id, 4, 7, 8, 1), (@judge2_id, 4, 8, 14, 1), -- Solaris Drift (total: 90)
  (@judge2_id, 5, 4, 25, 1), (@judge2_id, 5, 5, 21, 1), (@judge2_id, 5, 6, 15, 1), (@judge2_id, 5, 7, 7, 1), (@judge2_id, 5, 8, 11, 1); -- Crimson Horizon (total: 79)

-- Optional: set an active band for live judge polling (comment out if not needed)
-- UPDATE bands SET is_active = 1 WHERE name = 'Echo Forge';

COMMIT;

-- Totals (scores are direct point values, max = criteria weight)
-- Echo Forge (Judge1): 42+25+17 = 84    (Judge2): 44+23+18 = 85    Avg: 84.50
-- Prismatic Tide (Judge1): 40+21+18 = 79 (Judge2): 39+22+17 = 78   Avg: 78.50
-- Neon Pulse (Judge1): 38+22+16 = 76    (Judge2): 40+20+15 = 75    Avg: 75.50
-- Solaris Drift (Judge1): 27+22+18+9+13 = 89  (Judge2): 28+23+17+8+14 = 90  Avg: 89.50
-- Crimson Horizon (Judge1): 24+20+16+8+12 = 80  (Judge2): 25+21+15+7+11 = 79  Avg: 79.50

-- Expected rankings
-- Elimination: Echo Forge 84.50, Prismatic Tide 78.50, Neon Pulse 75.50 (Echo Forge leads)
-- Grand Finals: Solaris Drift 89.50, Crimson Horizon 79.50 (Solaris wins)
