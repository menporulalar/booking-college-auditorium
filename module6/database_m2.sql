-- ============================================================
-- Module 2: Auditorium Management
-- Run AFTER database.sql (Module 1 migration)
-- ============================================================

USE auditorium_booking;

-- ── Add photo & floor_plan columns to auditoriums ────────────
ALTER TABLE auditoriums
  ADD COLUMN IF NOT EXISTS photo      VARCHAR(255) DEFAULT NULL AFTER status,
  ADD COLUMN IF NOT EXISTS floor_plan VARCHAR(255) DEFAULT NULL AFTER photo,
  ADD COLUMN IF NOT EXISTS description TEXT         DEFAULT NULL AFTER floor_plan,
  ADD COLUMN IF NOT EXISTS created_at  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER description,
  ADD COLUMN IF NOT EXISTS updated_at  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER created_at;

-- ── Blackout dates ───────────────────────────────────────────
CREATE TABLE IF NOT EXISTS blackout_dates (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    auditorium_id   INT          DEFAULT NULL,   -- NULL = all auditoriums
    blackout_date   DATE         NOT NULL,
    reason          VARCHAR(255) DEFAULT NULL,
    created_by      INT          NOT NULL,
    created_at      TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (auditorium_id) REFERENCES auditoriums(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by)    REFERENCES users(id),
    UNIQUE KEY uq_blackout (auditorium_id, blackout_date),
    INDEX idx_date (blackout_date)
) ENGINE=InnoDB;

-- ── Admin activity log (audit trail) ────────────────────────
CREATE TABLE IF NOT EXISTS admin_log (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    admin_id    INT          NOT NULL,
    action      VARCHAR(100) NOT NULL,   -- 'auditorium_created', 'blackout_added', etc.
    target_type VARCHAR(50)  DEFAULT NULL,
    target_id   INT          DEFAULT NULL,
    detail      TEXT         DEFAULT NULL,
    created_at  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (admin_id) REFERENCES users(id),
    INDEX idx_admin  (admin_id),
    INDEX idx_action (action)
) ENGINE=InnoDB;

-- ── Update seed data with descriptions ───────────────────────
UPDATE auditoriums SET description = 'Main auditorium for large college events, cultural programs, convocations, and seminars. Equipped with full PA system and stage lighting.' WHERE id = 1;
UPDATE auditoriums SET description = 'Dedicated seminar hall for the B.Ed department. Suitable for departmental events, workshops, guest lectures, and presentations.' WHERE id = 2;
UPDATE auditoriums SET description = 'Executive boardroom for meetings, interviews, selection panels, and small conferences. Air-conditioned with video conferencing support.' WHERE id = 3;
