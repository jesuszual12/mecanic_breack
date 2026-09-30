import './bootstrap';

document.querySelectorAll('.table-wrap table').forEach((table) => {
	const headers = Array.from(table.tHead?.rows[0]?.cells ?? []).map((header) => header.textContent.trim() || 'Acciones');

	table.querySelectorAll('tbody tr').forEach((row) => {
		Array.from(row.cells).forEach((cell, index) => {
			if (!cell.hasAttribute('data-label') && headers[index]) {
				cell.dataset.label = headers[index];
			}
		});
	});

	table.classList.add('responsive-table');
});
