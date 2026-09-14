<?php
// =========================================================================
// SESSION TERMINATION & LOGOUT HANDLING
// =========================================================================

// Include authentication functions to access session management routines
require_once __DIR__ . '/includes/auth.php';

// Terminate the active user session and clear session data
logoutUser();

// Redirect the user back to the public homepage
redirect('index.php');