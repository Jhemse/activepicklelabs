<?php
/**
 * helpers.php - small utilities reused across the app.
 */

/** Escape a string for safe HTML output. */
function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

/** Trim + strip a value coming from $_POST/$_GET. */
function clean(?string $value): string
{
    return trim(strip_tags($value ?? ''));
}

/** Format "2026-08-15" -> "Aug 15, 2026". */
function formatDate(string $date): string
{
    return date('M j, Y', strtotime($date));
}

/** Format "08:00:00" -> "8:00 AM". */
function formatTime(string $time): string
{
    return date('g:i A', strtotime($time));
}

/** Simple flash-message helper (stored in session, shown once). */
function setFlash(string $key, string $message): void
{
    $_SESSION['flash'][$key] = $message;
}

function getFlash(string $key): ?string
{
    if (!empty($_SESSION['flash'][$key])) {
        $msg = $_SESSION['flash'][$key];
        unset($_SESSION['flash'][$key]);
        return $msg;
    }
    return null;
}

/** A small badge class helper for booking status pills. */
function statusBadgeClass(string $status): string
{
    return match ($status) {
        'confirmed' => 'badge badge-confirmed',
        'cancelled' => 'badge badge-cancelled',
        default     => 'badge badge-pending',
    };
}
