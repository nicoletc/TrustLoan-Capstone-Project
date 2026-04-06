-- Run once on an existing database that still has guarantors.status = ENUM('pending','confirmed') only.
ALTER TABLE guarantors
  MODIFY COLUMN status ENUM('pending','confirmed','rejected') NOT NULL DEFAULT 'pending';

UPDATE guarantors g
INNER JOIN applications a ON a.id = g.application_id
SET g.status = 'rejected', g.confirmed_at = NULL
WHERE a.status = 'rejected' AND g.status <> 'rejected';
