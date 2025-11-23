<?php
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');

/**
 * Dark mode hooks now resolve to light mode only.
 */

if (!function_exists('sync_dark_mode_session')) {
	function sync_dark_mode_session() {
		if (session_status() === PHP_SESSION_NONE) {
			session_start();
		}

		$_SESSION['dark_mode_admin'] = '0';
		$_SESSION['dark_mode_user'] = '0';
	}
}

if (!function_exists('is_dark_mode_enabled')) {
	function is_dark_mode_enabled($type = 'admin') {
		sync_dark_mode_session();
		return false;
	}
}

if (!function_exists('set_dark_mode_session')) {
	function set_dark_mode_session($value, $scope = 'admin') {
		// Dark mode disabled globally; keep session flags off.
		sync_dark_mode_session();
	}
}

if (!function_exists('current_dark_mode_flag')) {
	function current_dark_mode_flag($type = 'admin') {
		return 0;
	}
}

