<?php
/**
 * Dark Mode Toggle & Theme Controller
 * Add this to the topbar for theme switching
 */
?>

<style id="darkModeStyles">
    /* Dark Mode CSS - Applied conditionally */
    body.dark-mode {
        background-color: #1f2937;
        color: #f3f4f6;
    }

    .dark-mode .sidebar {
        background-color: #111827;
        border-right: 1px solid #374151;
    }

    .dark-mode .topbar {
        background-color: #1f2937;
        border-bottom: 1px solid #374151;
    }

    .dark-mode .card,
    .dark-mode .analytics-card,
    .dark-mode .timetable-container,
    .dark-mode .form-control,
    .dark-mode input,
    .dark-mode textarea,
    .dark-mode select {
        background-color: #374151;
        border-color: #4b5563;
        color: #f3f4f6;
    }

    .dark-mode .table {
        background-color: #374151;
        color: #f3f4f6;
    }

    .dark-mode .table thead {
        background-color: #1f2937;
    }

    .dark-mode .table-light {
        background-color: #1f2937;
    }

    .dark-mode .btn-outline-secondary {
        color: #d1d5db;
        border-color: #4b5563;
    }

    .dark-mode .btn-outline-secondary:hover {
        background-color: #4b5563;
    }

    .dark-mode a {
        color: #667eea;
    }

    .dark-mode .text-muted {
        color: #9ca3af !important;
    }

    .dark-mode .modal-content {
        background-color: #374151;
        color: #f3f4f6;
    }

    .dark-mode .modal-header {
        border-bottom-color: #4b5563;
    }

    .dark-mode .modal-footer {
        border-top-color: #4b5563;
    }
</style>

<script>
    // Dark Mode Toggle
    class DarkModeManager {
        constructor() {
            this.key = 'studentPortal_darkMode';
            this.init();
        }

        init() {
            const isDarkMode = localStorage.getItem(this.key) === 'true';
            if (isDarkMode) {
                this.enable();
            }
        }

        toggle() {
            const isDarkMode = document.body.classList.contains('dark-mode');
            if (isDarkMode) {
                this.disable();
            } else {
                this.enable();
            }
        }

        enable() {
            document.body.classList.add('dark-mode');
            localStorage.setItem(this.key, 'true');
            this.updateToggleButton(true);
        }

        disable() {
            document.body.classList.remove('dark-mode');
            localStorage.setItem(this.key, 'false');
            this.updateToggleButton(false);
        }

        updateToggleButton(isDarkMode) {
            const btn = document.getElementById('darkModeToggle');
            if (btn) {
                btn.innerHTML = isDarkMode ?
                    '<i class="fas fa-sun"></i> Light' :
                    '<i class="fas fa-moon"></i> Dark';
            }
        }
    }

    const darkModeManager = new DarkModeManager();
</script>

<!-- Add this button to the topbar -->
<div class="nav-item d-inline-block mr-2">
    <button id="darkModeToggle" class="btn btn-outline-secondary btn-sm"
        onclick="darkModeManager.toggle()" title="Toggle Dark/Light Mode">
        <i class="fas fa-moon"></i> Dark
    </button>
</div>
