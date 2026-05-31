document.addEventListener('DOMContentLoaded', function () {
    const primaryContainer = document.querySelector('.main-content, .dashboard-main, .dashboard-content, .content-wrapper, .container-fluid, .page-content, .page-body');
    if (!primaryContainer) {
        return;
    }

    const widgetHost = document.createElement('div');
    widgetHost.id = 'departmentInventoryWidgetHost';
    widgetHost.className = 'dashboard-inventory-widget-wrapper mb-4';
    primaryContainer.prepend(widgetHost);

    async function loadInventoryWidget() {
        try {
            const response = await fetch('/ISNM/includes/dashboard_inventory_widget.php', {
                headers: { 'Accept': 'text/html' }
            });
            if (!response.ok) {
                widgetHost.innerHTML = '<div class="alert alert-danger">Unable to load department inventory summary.</div>';
                return;
            }
            widgetHost.innerHTML = await response.text();
            attachInventoryFormEvents(widgetHost);
        } catch (error) {
            widgetHost.innerHTML = '<div class="alert alert-danger">Inventory widget could not load.</div>';
            console.error('Inventory widget error', error);
        }
    }

    function attachInventoryFormEvents(root) {
        const form = root.querySelector('#inventoryReportForm');
        const messageEl = root.querySelector('#inventoryReportMessage');
        const itemIdInput = root.querySelector('#inventoryItemId');
        const buttons = root.querySelectorAll('.report-item-button');

        buttons.forEach(button => {
            button.addEventListener('click', function () {
                itemIdInput.value = this.dataset.itemId;
                messageEl.innerHTML = '<div class="alert alert-info">Selected item: ' + this.dataset.itemName + '.</div>';
            });
        });

        if (!form) {
            return;
        }

        form.addEventListener('submit', async function (event) {
            event.preventDefault();
            if (!itemIdInput.value) {
                messageEl.innerHTML = '<div class="alert alert-warning">Please select an item using its report button before submitting.</div>';
                return;
            }

            const payload = {
                item_id: Number(itemIdInput.value),
                report_type: root.querySelector('#reportType').value,
                report_to: root.querySelector('#reportTo').value,
                report_notes: root.querySelector('#reportNotes').value.trim()
            };

            const result = await submitInventoryReport(payload);
            if (result.status === 'success') {
                messageEl.innerHTML = '<div class="alert alert-success">' + result.message + '</div>';
                itemIdInput.value = '';
                root.querySelector('#reportNotes').value = '';
            } else {
                messageEl.innerHTML = '<div class="alert alert-danger">' + (result.message || 'Could not send report.') + '</div>';
            }
        });
    }

    async function submitInventoryReport(payload) {
        try {
            const response = await fetch('/ISNM/includes/dashboard_inventory_widget.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });
            return response.json();
        } catch (error) {
            console.error('Inventory report error:', error);
            return { status: 'error', message: 'Network error while submitting report' };
        }
    }

    loadInventoryWidget();
    appendInventoryReportsShortcut();

    function appendInventoryReportsShortcut() {
        const pageContainer = document.querySelector('.main-content, .dashboard-main, .dashboard-content');
        if (!pageContainer) {
            return;
        }

        const shortcut = document.createElement('a');
        shortcut.href = '/ISNM/dashboards/inventory-reports.php';
        shortcut.className = 'inventory-report-shortcut-btn';
        shortcut.title = 'Open Inventory Report Management';
        shortcut.innerHTML = '<i class="fas fa-clipboard-list"></i> Inventory Reports';

        document.body.appendChild(shortcut);
    }
});
