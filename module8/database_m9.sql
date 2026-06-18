-- ============================================================
-- Module 9: Email Notifications
-- Run AFTER database_m3.sql
-- ============================================================

USE auditorium_booking;

-- ── Notification log ─────────────────────────────────────────
CREATE TABLE IF NOT EXISTS notification_log (
    id               INT AUTO_INCREMENT PRIMARY KEY,
    booking_id       INT          DEFAULT NULL,
    trigger_event    VARCHAR(100) NOT NULL,   -- 'booking_submitted', 'booking_approved', etc.
    recipient_email  VARCHAR(150) NOT NULL,
    recipient_name   VARCHAR(100) DEFAULT NULL,
    subject          VARCHAR(255) NOT NULL,
    status           ENUM('sent','failed') NOT NULL DEFAULT 'sent',
    error_message    VARCHAR(255) DEFAULT NULL,
    sent_at          TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE SET NULL,
    INDEX idx_booking (booking_id),
    INDEX idx_event   (trigger_event),
    INDEX idx_status  (status),
    INDEX idx_sent_at (sent_at)
) ENGINE=InnoDB;

-- ── Notification settings (admin toggles per event type) ─────
CREATE TABLE IF NOT EXISTS notification_settings (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    event_key     VARCHAR(100) NOT NULL UNIQUE,
    label         VARCHAR(150) NOT NULL,
    description   VARCHAR(255) DEFAULT NULL,
    enabled       TINYINT(1)   NOT NULL DEFAULT 1,
    updated_at    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ── Seed default notification toggles ────────────────────────
INSERT IGNORE INTO notification_settings (event_key, label, description, enabled) VALUES
('booking_submitted',     'New Booking Submitted',        'Sent to admin when a staff member submits a new booking request.', 1),
('booking_approved',       'Booking Approved',             'Sent to staff when their booking is approved.', 1),
('booking_rejected',       'Booking Rejected',             'Sent to staff when their booking is rejected, with the reason.', 1),
('booking_conflict',       'Conflict Flagged',             'Sent to admin when a new booking conflicts with an existing one.', 1),
('booking_override',       'Admin Override Approved',      'Sent to staff when their conflicted booking is approved via override.', 1),
('booking_alternate',      'Alternate Time Suggested',      'Sent to staff when admin suggests an alternate slot.', 1),
('booking_reminder',       'Reminder (24hrs Before)',       'Sent to staff 24 hours before their approved event.', 1),
('booking_cancelled',      'Booking Cancelled by Staff',    'Sent to admin when a staff member cancels their booking.', 1),
('equipment_unavailable',  'Equipment Unavailable',         'Sent to staff when requested equipment is marked unavailable.', 1),
('series_approved',        'Recurring Series Approved',     'Sent to staff when an entire recurring series is approved.', 1),
('series_rejected',        'Recurring Series Rejected',     'Sent to staff when an entire recurring series is rejected.', 1);

-- ── Reminder tracking (avoid duplicate reminders) ────────────
ALTER TABLE bookings
  ADD COLUMN IF NOT EXISTS reminder_sent TINYINT(1) NOT NULL DEFAULT 0 AFTER ical_uid;
