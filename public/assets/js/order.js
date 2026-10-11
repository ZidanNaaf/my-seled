// Order form: pilih paket + cegah double submit + idempotency
document.addEventListener('DOMContentLoaded', () => {
  const form = document.getElementById('order-form');
  if (!form) return;
  const cards = form.querySelectorAll('[data-package]');
  const pkgInput = document.getElementById('package_id');
  const summary = document.getElementById('order-summary');
  function paint() {
    cards.forEach(c => {
      const active = c.dataset.package === pkgInput.value;
      c.classList.toggle('ring-2', active);
      c.classList.toggle('ring-white', active);
      c.setAttribute('aria-pressed', active ? 'true' : 'false');
    });
    const sel = form.querySelector(`[data-package="${pkgInput.value}"]`);
    if (sel && summary) {
      summary.innerHTML = `<div class="flex justify-between text-sm"><span>${sel.dataset.name}</span><strong>${MS.rupiah(sel.dataset.price)}</strong></div>
      <div class="muted text-xs mt-1">SIMULASI — harga fiktif, dihitung ulang di server.</div>`;
    }
  }
  cards.forEach(c => c.addEventListener('click', () => { pkgInput.value = c.dataset.package; paint(); }));
  paint();
  form.addEventListener('submit', () => {
    const btn = form.querySelector('[type="submit"]');
    if (btn) { btn.disabled = true; btn.dataset.orig = btn.innerHTML; btn.innerHTML = 'Mengirim...'; }
  });
});
