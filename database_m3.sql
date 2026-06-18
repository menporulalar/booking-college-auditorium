-- ============================================================
-- Module 3: Calendar & Booking Engine
-- Run AFTER database_m2.sql
-- ============================================================

USE auditorium_booking;

-- ── Recurrence groups ────────────────────────────────────────
CREATE TABLE IF NOT EXISTS recurrence_groups (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    pattern         ENUM('daily','weekly','monthly','custom') NOT NULL,
    interval_value  INT          NOT NULL DEFAULT 1,
    end_date        DATE         DEFAULT NULL,
    created_at      TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ── Bookings ─────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS bookings (
    id                  INT AUTO_INCREMENT PRIMARY KEY,
    user_id             INT          NOT NULL,
    auditorium_id       INT          NOT NULL,
    event_name          VARCHAR(200) NOT NULL,
    event_description   TEXT         DEFAULT NULL,
    start_datetime      DATETIME     NOT NULL,
    end_datetime        DATETIME     NOT NULL,
    attendee_count      INT          DEFAULT NULL,
    special_requirements TEXT        DEFAULT NULL,
    status              ENUM('pending','pending_conflict','approved','rejected','cancelled')
                        NOT NULL DEFAULT 'pending',
    admin_note          TEXT         DEFAULT NULL,
    override_by         INT          DEFAULT NULL,
    override_reason     TEXT         DEFAULT NULL,
    recurrence_group_id INT          DEFAULT NULL,
    ical_uid            VARCHAR(255) DEFAULT NULL,
    created_at          TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at          TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id)             REFERENCES users(id)             ON DELETE CASCADE,
    FOREIGN KEY (auditorium_id)       REFERENCES auditoriums(id)       ON DELETE CASCADE,
    FOREIGN KEY (override_by)         REFERENCES users(id)             ON DELETE SET NULL,
    FOREIGN KEY (recurrence_group_id) REFERENCES recurrence_groups(id) ON DELETE SET NULL,
    INDEX idx_start        (start_datetime),
    INDEX idx_auditorium   (auditorium_id),
    INDEX idx_status       (status),
    INDEX idx_user         (user_id),
    INDEX idx_recurrence   (recurrence_group_id)
) ENGINE=InnoDB;

-- ── Equipment requests ───────────────────────────────────────
CREATE TABLE IF NOT EXISTS equipment_requests (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    booking_id      INT          NOT NULL,
    equipment_name  VARCHAR(100) NOT NULL,
    quantity        INT          NOT NULL DEFAULT 1,
    status          ENUM('requested','confirmed','unavailable') NOT NULL DEFAULT 'requested',
    FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE,
    INDEX idx_booking (booking_id)
) ENGINE=InnoDB;
