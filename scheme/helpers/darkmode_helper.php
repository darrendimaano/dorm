<?php
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');

/**
 * Dark Mode Helper
 * Simple functions for dark mode (using embedded CSS/JS approach)
 */

if (!function_exists('get_dark_mode_class')) {
    /**
     * Get dark mode CSS class (simplified version)
     * @param string $type - 'admin' or 'user'
     * @return string 'dark' if dark mode should be enabled, empty string otherwise
     */
    function get_dark_mode_class($type = 'admin') {
        // For now, return empty since we're using JavaScript to manage dark mode
        // This prevents server-side errors while maintaining compatibility
        return '';
    }
}

if (!function_exists('include_dark_mode_css')) {
    /**
     * Include dark mode CSS styles
     * @param string $type - 'admin' or 'user'
     * @return string CSS styles for dark mode
     */
    function include_dark_mode_css($type = 'admin') {
        if($type === 'admin') {
            return '
            <style>
            .dark #sidebar {
                background: #1a1a1a !important;
            }
            .dark body {
                background: #111111 !important;
            }
            .dark .main-content, .dark .content-area {
                background: #1a1a1a !important;
                color: #e5e5e5 !important;
            }
            .dark .card, .dark .admin-card {
                background: #2a2a2a !important;
                border-color: #404040 !important;
                color: #e5e5e5 !important;
            }
            .dark input, .dark select, .dark textarea {
                background: #333333 !important;
                border-color: #555555 !important;
                color: #e5e5e5 !important;
            }
            .dark h1, .dark h2, .dark h3, .dark h4, .dark h5, .dark h6, .dark label, .dark p {
                color: #e5e5e5 !important;
            }
            .dark #sidebar a {
                color: #e5e5e5 !important;
            }
            .dark table {
                background: #2a2a2a !important;
                color: #e5e5e5 !important;
            }
            .dark th {
                background: #333333 !important;
                color: #e5e5e5 !important;
                border-color: #555555 !important;
            }
            .dark td {
                border-color: #555555 !important;
            }
            .dark .header-section {
                background: #1a1a1a !important;
                color: #e5e5e5 !important;
            }
            </style>';
        } else {
            return '
            <style>
            .dark #sidebar {
                background: #1a1a1a !important;
            }
            .dark body {
                background: #111111 !important;
            }
            .dark .main-content, .dark .content-area {
                background: #1a1a1a !important;
                color: #e5e5e5 !important;
            }
            .dark .user-card {
                background: #2a2a2a !important;
                border-color: #404040 !important;
                color: #e5e5e5 !important;
            }
            .dark input, .dark select, .dark textarea {
                background: #333333 !important;
                border-color: #555555 !important;
                color: #e5e5e5 !important;
            }
            .dark h1, .dark h2, .dark h3, .dark h4, .dark h5, .dark h6, .dark label, .dark p {
                color: #e5e5e5 !important;
            }
            .dark #sidebar a {
                color: #e5e5e5 !important;
            }
            .dark .header-section {
                background: #1a1a1a !important;
                color: #e5e5e5 !important;
            }
            </style>';
        }
    }
}

if (!function_exists('include_dark_mode_js')) {
    /**
     * Include dark mode JavaScript
     * @param string $type - 'admin' or 'user'
     * @return string JavaScript for dark mode toggle
     */
    function include_dark_mode_js($type = 'admin') {
        $storageKey = $type === 'admin' ? 'adminDarkMode' : 'userDarkMode';
        
        return '
        <script>
        // Dark mode functionality
        function initDarkMode() {
            const darkModeToggle = document.getElementById("darkModeToggle");
            const darkModeIcon = document.getElementById("darkModeIcon");
            const mainBody = document.body;
            
            if (!darkModeToggle) return;
            
            // Check for saved dark mode preference
            const isDarkMode = localStorage.getItem("' . $storageKey . '") === "true";
            if (isDarkMode) {
                mainBody.classList.add("dark");
                if(darkModeIcon) darkModeIcon.className = "fa-solid fa-sun";
            }
            
            darkModeToggle.addEventListener("click", () => {
                mainBody.classList.toggle("dark");
                const isDark = mainBody.classList.contains("dark");
                
                // Save preference
                localStorage.setItem("' . $storageKey . '", isDark);
                
                // Update icon
                if(darkModeIcon) {
                    darkModeIcon.className = isDark ? "fa-solid fa-sun" : "fa-solid fa-moon";
                }
                
                // Update database setting via AJAX
                fetch("' . site_url('settings/update') . '", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/x-www-form-urlencoded",
                    },
                    body: "dark_mode_' . $type . '=" + (isDark ? "1" : "0") + "&ajax=1"
                });
            });
        }
        
        // Initialize when DOM is ready
        if (document.readyState === "loading") {
            document.addEventListener("DOMContentLoaded", initDarkMode);
        } else {
            initDarkMode();
        }
        </script>';
    }
}