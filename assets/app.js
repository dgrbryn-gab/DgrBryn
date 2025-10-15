import './bootstrap.js';
import './styles/app.css';

import $ from 'jquery';
import 'datatables.net';

// ✅ Alpine.js import and initialization
import Alpine from 'alpinejs';
window.Alpine = Alpine;
Alpine.start();

// ✅ Initialize DataTables if present
$(document).ready(function () {
    if ($('#wineInventoryTable').length) {
        $('#wineInventoryTable').DataTable({
            responsive: true,
        });
    }
});
