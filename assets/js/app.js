document.addEventListener('DOMContentLoaded', () => {
  const dateInput    = document.getElementById('booking_date');
  const slotsBox     = document.getElementById('slots');
  const pgId         = document.getElementById('pg_id')?.value;
  const pricePerHour = parseFloat(document.getElementById('price_per_hour')?.value || 0);

  const summary      = document.getElementById('selectionSummary');
  const slotCountEl  = document.getElementById('slotCount');
  const totalPriceEl = document.getElementById('totalPrice');
  const slotListEl   = document.getElementById('slotList');
  const clearBtn     = document.getElementById('clearSlots');
  const form         = document.getElementById('bookingForm');

  if (!dateInput || !pgId) return;

  // Sinhala: selected slots ටික track කරන්න
  let selectedSlots = []; // [{start:'09:00', end:'10:00', price: 2500}]

  /* =============== LOAD SLOTS FROM AJAX =============== */
  async function loadSlots() {
    slotsBox.innerHTML = '<div class="text-muted small">Loading slots...</div>';
    selectedSlots = [];
    updateSummary();

    try {
      const url = `ajax/check_availability.php?playground_id=${pgId}&date=${dateInput.value}`;
      const res  = await fetch(url);
      const data = await res.json();

      if (!data.slots?.length) {
        slotsBox.innerHTML = '<div class="text-muted small">Slots නැහැ.</div>';
        return;
      }

      slotsBox.innerHTML = data.slots.map((s, i) => `
        <button type="button"
          class="btn ${s.available ? 'btn-outline-success' : 'btn-outline-secondary'}
                 m-1 slot-btn"
          ${s.available ? '' : 'disabled'}
          data-idx="${i}"
          data-start="${s.start}"
          data-end="${s.end}"
          data-price="${s.price}">
          ${s.start} - ${s.end}
          <br><small>${s.available ? 'Rs. ' + s.price : 'Booked'}</small>
        </button>
      `).join('');

      // Click handler — toggle selection
      slotsBox.querySelectorAll('.slot-btn:not([disabled])').forEach(btn => {
        btn.addEventListener('click', () => toggleSlot(btn));
      });

    } catch (err) {
      slotsBox.innerHTML = '<div class="text-danger small">Load කරන්න බැරි වුණා.</div>';
    }
  }

  /* =============== TOGGLE SLOT =============== */
  function toggleSlot(btn) {
    const start = btn.dataset.start;
    const end   = btn.dataset.end;

    const existing = selectedSlots.findIndex(s => s.start === start);

    if (existing >= 0) {
      // Already selected — remove
      selectedSlots.splice(existing, 1);
      btn.classList.remove('active', 'btn-success');
      btn.classList.add('btn-outline-success');
    } else {
      // Add new
      selectedSlots.push({
        start: start,
        end:   end,
        price: parseFloat(btn.dataset.price || pricePerHour)
      });
      btn.classList.remove('btn-outline-success');
      btn.classList.add('active', 'btn-success');
    }

    // Sort by start time
    selectedSlots.sort((a, b) => a.start.localeCompare(b.start));
    updateSummary();
  }

  /* =============== UPDATE SUMMARY PANEL =============== */
  function updateSummary() {
    if (selectedSlots.length === 0) {
      summary.classList.add('d-none');
      return;
    }
    summary.classList.remove('d-none');

    const total = selectedSlots.reduce((sum, s) => sum + s.price, 0);
    slotCountEl.textContent  = selectedSlots.length;
    totalPriceEl.textContent = total.toLocaleString();

    slotListEl.innerHTML = selectedSlots
      .map(s => `<span class="badge bg-success-subtle text-success-emphasis me-1 mb-1">
                    ${s.start} - ${s.end}
                 </span>`)
      .join('');
  }

  /* =============== CLEAR ALL =============== */
  clearBtn?.addEventListener('click', () => {
    selectedSlots = [];
    slotsBox.querySelectorAll('.slot-btn').forEach(btn => {
      btn.classList.remove('active', 'btn-success');
      if (!btn.disabled) btn.classList.add('btn-outline-success');
    });
    updateSummary();
  });

  /* =============== SUBMIT — inject hidden fields =============== */
  form?.addEventListener('submit', (e) => {
    if (selectedSlots.length === 0) {
      e.preventDefault();
      alert('Slot එකක්වත් තෝරලා නැහැ.');
      return;
    }

    // Remove old hidden inputs
    form.querySelectorAll('input[name="slots[]"]').forEach(el => el.remove());

    // Add each selected slot as slots[] hidden input
    selectedSlots.forEach(s => {
      const input = document.createElement('input');
      input.type  = 'hidden';
      input.name  = 'slots[]';
      input.value = `${s.start}|${s.end}`;
      form.appendChild(input);
    });
  });

  /* =============== EVENTS =============== */
  dateInput.addEventListener('change', loadSlots);
  loadSlots(); // initial load
});