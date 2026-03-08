document.addEventListener('DOMContentLoaded', function() {
  const soaTableRows = document.querySelectorAll('.soa-table-row');

  soaTableRows.forEach(row => {
    row.addEventListener('click', function() {
      // Toggle the selected state
      this.classList.toggle('soa-row-selected');
    });
  });
});
