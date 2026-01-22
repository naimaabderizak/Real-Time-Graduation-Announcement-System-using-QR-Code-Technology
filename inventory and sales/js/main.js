
/**
 * js/main.js
 * 
 * Main JavaScript File
 * 
 * Handles client-side logic such as:
 * - Table row selection for bulk actions/printing.
 * - Dynamic print state updates.
 * 
 * Author: System
 * Date: 2026-01-05
 */

document.addEventListener('DOMContentLoaded', function () {
    // Select All functionality
    const selectAllBtn = document.getElementById('selectAll');
    if (selectAllBtn) {
        selectAllBtn.addEventListener('change', function () {
            const checkboxes = document.querySelectorAll('.row-checkbox');
            checkboxes.forEach(cb => {
                cb.checked = this.checked;
                toggleRowSelection(cb);
            });
            updatePrintState();
        });
    }

    // Individual Row Selection
    document.addEventListener('change', function (e) {
        if (e.target.classList.contains('row-checkbox')) {
            toggleRowSelection(e.target);
            updatePrintState();

            // Update Select All state
            if (selectAllBtn) {
                const allCheckboxes = document.querySelectorAll('.row-checkbox');
                const allChecked = Array.from(allCheckboxes).every(cb => cb.checked);
                selectAllBtn.checked = allChecked;
            }
        }
    });

    function toggleRowSelection(checkbox) {
        const row = checkbox.closest('tr');
        if (checkbox.checked) {
            row.classList.add('selected');
        } else {
            row.classList.remove('selected');
        }
    }

    function updatePrintState() {
        const anyChecked = document.querySelectorAll('.row-checkbox:checked').length > 0;
        if (anyChecked) {
            document.body.classList.add('print-selected-only');
        } else {
            document.body.classList.remove('print-selected-only');
        }
    }
});
