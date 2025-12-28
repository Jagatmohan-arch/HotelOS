-- Phase E-Lite: Performance Patch
-- Adds index to sessions table for faster tenant isolation lookups
-- Run this in your MySQL client (phpMyAdmin etc.)

ALTER TABLE `sessions` ADD INDEX `idx_session_tenant` (`tenant_id`);
