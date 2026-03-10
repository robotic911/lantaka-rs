document.addEventListener('DOMContentLoaded', function() {
  const soaTableRows = document.querySelectorAll('.soa-table-row');
  const previewList = document.getElementById('soaPreviewList');
  

  function updatePreview() {
    previewList.innerHTML = '';

    const selectedRows = document.querySelectorAll('.soa-table-row.soa-row-selected');

    selectedRows.forEach(row => {
      const name = row.dataset.name;
      const days = row.dataset.days;
      const price = Number(row.dataset.price || 0);

      const previewItem = document.createElement('div');
      previewItem.classList.add('soa-preview-item');

      previewItem.innerHTML = `
        <div class="soa-preview-row">
          <span class="soa-preview-room">${name}</span>
          <span class="soa-preview-duration">${days} day/night</span>
          <span class="soa-preview-price">₱ ${price.toLocaleString()}</span>
        </div>
      `;

      previewList.appendChild(previewItem);
    });
  }

  soaTableRows.forEach(row => {
    row.addEventListener('click', function() {
      this.classList.toggle('soa-row-selected');
      updatePreview();
    });
  });
});