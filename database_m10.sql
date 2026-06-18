-- ============================================================
-- Module 10: Reports & Analytics
-- Run AFTER database_m3.sql and database_m9.sql
-- ============================================================

USE auditorium_booking;

-- Extra indexes for fast report queries
ALTER TABLE bookings
  ADD INDEX idx_override  (override_by),
  ADD INDEX idx_reminder  (reminder_sent),
  ADD INDEX idx_created   (created_at);

ALTER TABLE equipment_requests
  ADD INDEX idx_eq_name   (equipment_name);

ALTER TABLE notification_log
  ADD INDEX idx_nl_event  (trigger_event),
  ADD INDEX idx_nl_sent   (sent_at);
