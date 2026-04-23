-- Run once on databases created before customer email lived in `users`.
-- phpMyAdmin → SQL, or: mysql ... < sql/migrations/003_users_add_email.sql

ALTER TABLE users
  ADD COLUMN email VARCHAR(120) NULL DEFAULT NULL AFTER password_hash;
