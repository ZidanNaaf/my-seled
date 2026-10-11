// Helper global My Seled
window.MS = {
  csrf: document.querySelector('meta[name="csrf"]')?.content || '',
  async api(url, opts = {}) {
    const res = await fetch(url, {
      headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': this.csrf, ...(opts.headers || {}) },
      ...opts,
    });
    const data = await res.json().catch(() => ({}));
    if (!res.ok) throw { status: res.status, data };
    return data;
  },
  toast(msg, type = 'info') {
    if (window.Swal) {
      Swal.fire({ toast: true, position: 'top-end', timer: 2800, showConfirmButton: false, icon: type === 'error' ? 'error' : type === 'success' ? 'success' : 'info', title: msg });
    } else alert(msg);
  },
  rupiah(n) {
    return 'Rp' + Number(n || 0).toLocaleString('id-ID');
  },
  fmtDate(utc) {
    if (!utc) return '-';
    try { return new Date(utc.replace(' ', 'T') + 'Z').toLocaleString('id-ID'); } catch { return utc; }
  }
};
document.addEventListener('DOMContentLoaded', () => {
  // Font Awesome via CDN tidak perlu init JS
  // flash -> sweetalert toast
  document.querySelectorAll('[data-flash]').forEach(el => {
    MS.toast(el.dataset.flash, el.dataset.type || 'info');
  });
});
