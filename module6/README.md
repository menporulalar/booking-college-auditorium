# Module 6 — Admin Approval Workflow

## What's included

| File | Purpose |
|---|---|
| `app/Models/Booking.php` | Extended with admin queries: `allPending()`, `allForAdmin()`, `pendingCount()`, `conflictCount()`, `todayCount()`, series helpers, approve/reject/override actions, bulk actions, equipment status updates |
| `app/Controllers/AdminBookingController.php` | Pending list, all-bookings list, detail/review, approve, reject, edit, suggest-alternate, series approve/reject, bulk actions, equipment status |
| `views/admin/bookings/pending.php` | Pending approvals — grouped by recurring series, conflict bookings highlighted, bulk select/approve/reject, quick-approve for non-conflict bookings |
| `views/admin/bookings/show.php` | Full review page — conflict panel, approve (with override reason if conflicted), reject (reason required), suggest alternate time, equipment status editor, series occurrence list |
| `views/admin/bookings/edit.php` | Admin edit form — event details, date/time, attendees, special requirements |
| `views/admin/bookings/index.php` | All bookings — filterable (status, hall, date range, search), paginated |
| `views/admin/dashboard.php` | Updated with live pending/conflict/today counts |
| `views/layouts/admin-header.php` | Sidebar now shows a live pending-count badge |
| `public/index.php` | Router updated with all admin booking routes |

No new database migration needed — uses tables from Module 3.

---

## Key workflows

### Pending approvals (`/admin/bookings/pending`)
- Conflict bookings (`pending_conflict`) sorted to the top and visually flagged
- Recurring series grouped into a single card showing all occurrences
- Bulk approve/reject for non-conflict bookings (checkbox selection)
- Conflict bookings are excluded from bulk actions — admin must open "Review"

### Review page (`/admin/bookings/{id}`)
- **Approve**: if the booking has a conflict, an **override reason is mandatory** and gets permanently logged (`override_by`, `override_reason`)
- **Reject**: reason is mandatory, stored in `admin_note`
- **Suggest alternate time**: marks booking rejected with the suggested slot noted in `admin_note` — staff can submit a new request
- **Edit details**: admin can modify event name, date/time, attendees, etc. on behalf of staff — if still pending, conflict status is re-evaluated after saving
- **Equipment status**: per-item dropdown (Requested / Confirmed / Unavailable)
- Shows all occurrences if part of a recurring series, with quick links

### Series actions
- `POST /admin/bookings/series/{groupId}/approve` — approves all pending occurrences in one action
- `POST /admin/bookings/series/{groupId}/reject` — rejects all pending occurrences (reason required)
- If any occurrence in the series has a conflict, series-approve is disabled in the UI — individual review required first

### All Bookings (`/admin/bookings`)
- Filters: search (event/staff name), status, auditorium, date range
- Paginated (15/page)

---

## Admin logging
Every action is recorded via `AdminLog::record()`:
- `booking_approved` / `booking_approved_override`
- `booking_rejected`
- `booking_edited_by_admin`
- `booking_alternate_suggested`
- `series_approved` / `series_rejected`
- `bulk_approved` / `bulk_rejected`
- `equipment_status_updated`

---

## Routes added

| Route | Method | Purpose |
|---|---|---|
| `/admin/bookings/pending` | GET | Pending approvals dashboard |
| `/admin/bookings` | GET | All bookings (filterable) |
| `/admin/bookings/{id}` | GET | Review/detail page |
| `/admin/bookings/{id}/approve` | POST | Approve (with override if conflict) |
| `/admin/bookings/{id}/reject` | POST | Reject (reason required) |
| `/admin/bookings/{id}/edit` | GET/POST | Admin edit form |
| `/admin/bookings/{id}/suggest-alternate` | POST | Suggest alternate & reject |
| `/admin/bookings/{id}/equipment` | POST | Update equipment availability |
| `/admin/bookings/series/{groupId}/approve` | POST | Approve entire series |
| `/admin/bookings/series/{groupId}/reject` | POST | Reject entire series |
| `/admin/bookings/bulk-action` | POST | Bulk approve/reject |

---

## Still pending (future modules)
- Module 8: iCal export for approved bookings
- Module 9: Email notifications on approve/reject/override/reminder
- Module 10: Reports & analytics (utilization, equipment usage, override log)
