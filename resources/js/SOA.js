document.addEventListener('DOMContentLoaded', function () {
  const soaTableRows = document.querySelectorAll('.soa-table-row');
  const previewList = document.getElementById('soaPreviewList');
  const exportForm = document.getElementById('soaExportForm');
  const selectedItemsInput = document.getElementById('selectedItemsInput');

  const fmt = v => Number(v || 0).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

  /** Escape user/DB data before inserting into innerHTML to prevent XSS. */
  function escHtml(str) {
    if (str == null) return '';
    return String(str)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#039;');
  }

  function updatePreview() {
    if (!previewList) return;

    previewList.innerHTML = '';

    const selectedRows = document.querySelectorAll('.soa-table-row.soa-row-selected');

    if (selectedRows.length === 0) return;

    let grandTotal = 0;

    selectedRows.forEach(row => {
      const name      = row.dataset.name || '';
      const days      = row.dataset.days || 1;
      const price     = Number(row.dataset.price     || 0);
      const discount  = Number(row.dataset.discount  || 0);
      const foodTotal = Number(row.dataset.foodTotal || 0);
      const foodPax   = Number(row.dataset.foodPax   || 0);
      const pax       = Number(row.dataset.pax       || 0);

      let feeItems = [];
      try {
        feeItems = JSON.parse(row.dataset.feeItems || '[]');
      } catch (e) {
        feeItems = [];
      }

      // Accurate item total: base price + food + all fees − discount
      const feeTotal  = feeItems.reduce((sum, item) => sum + Number(item.line_total || 0), 0);
      const itemTotal = price + foodTotal + feeTotal - discount;
      grandTotal += itemTotal;

      const previewItem = document.createElement('div');
      previewItem.classList.add('soa-preview-item');

      let feeHtml = '';

      // Food sub-row (venue only, when food was ordered)
      if (foodTotal > 0) {
        feeHtml += `
          <div class="soa-preview-subrow">
            <span class="soa-preview-sublabel">* Food &times;${pax} pax</span>
            <span class="soa-preview-subprice">₱ ${fmt(foodTotal)}</span>
          </div>
        `;
      }

      if (Array.isArray(feeItems) && feeItems.length > 0) {
        feeItems.forEach(item => {
          feeHtml += `
            <div class="soa-preview-subrow">
              <span class="soa-preview-sublabel">+ ${escHtml(item.desc)} &times;${Number(item.qty) || 1}</span>
              <span class="soa-preview-subprice">₱ ${fmt(item.line_total)}</span>
            </div>
          `;
        });
      }

      if (discount > 0) {
        feeHtml += `
          <div class="soa-preview-subrow soa-preview-discount">
            <span class="soa-preview-sublabel">− Discount</span>
            <span class="soa-preview-subprice">− ₱ ${fmt(discount)}</span>
          </div>
        `;
      }

      previewItem.innerHTML = `
        <div class="soa-preview-row">
          <div class="soa-preview-room-info">
            <span class="soa-preview-room">${escHtml(name)}</span>
            <span class="soa-preview-duration">${escHtml(days)} day/night</span>
          </div>
          <span class="soa-preview-price">₱ ${fmt(price)}</span>
        </div>
        ${feeHtml}
        <div class="soa-preview-item-total">
          <span>Subtotal</span>
          <span>₱ ${fmt(itemTotal)}</span>
        </div>
      `;

      previewList.appendChild(previewItem);
    });

    // Grand total row
    const totalEl = document.createElement('div');
    totalEl.classList.add('soa-preview-grand-total');
    totalEl.innerHTML = `
      <span>Total Amount Due</span>
      <span>₱ ${fmt(grandTotal)}</span>
    `;
    previewList.appendChild(totalEl);
  }

  function updateSelectedItemsInput() {
    if (!selectedItemsInput) return;

    const selectedRows = document.querySelectorAll('.soa-table-row.soa-row-selected');

    const selectedItems = Array.from(selectedRows).map(row => ({
      id: row.dataset.id,
      type: row.dataset.type
    }));

    selectedItemsInput.value = JSON.stringify(selectedItems);
  }

  soaTableRows.forEach(row => {
    row.addEventListener('click', function () {
      const group = this.dataset.group;
      const isSelected = this.classList.contains('soa-row-selected');

      if (isSelected) {
        this.classList.remove('soa-row-selected');
      } else {
        this.classList.add('soa-row-selected');
      }

      const childRows = document.querySelectorAll(`.soa-extra-row[data-group="${group}"]`);
      childRows.forEach(child => {
        if (isSelected) {
          child.classList.remove('soa-row-selected');
        } else {
          child.classList.add('soa-row-selected');
        }
      });

      updatePreview();
      updateSelectedItemsInput();
    });
  });

  if (exportForm) {
    exportForm.addEventListener('submit', function (e) {
      updateSelectedItemsInput();

      if (!selectedItemsInput.value || selectedItemsInput.value === '[]') {
        e.preventDefault();
        window.showToast('Please select at least one reservation to export.');
      }
    });
  }
});
