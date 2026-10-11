// My Seled — Vue 3 islands (CDN global build, tanpa npm/build).
// PHP tetap SSR; Vue mount progresif jika elemen ada. Leaflet tetap imperatif.
(function () {
  function ready(fn) {
    if (document.readyState !== 'loading') fn();
    else document.addEventListener('DOMContentLoaded', fn);
  }

  ready(function () {
    if (!window.Vue) return;
    const { createApp, ref, computed, onMounted, watch } = window.Vue;
    const MS = window.MS || {};
    const csrf = () => document.querySelector('meta[name="csrf"]')?.content || '';
    async function api(url, opts) {
      if (MS.api) return MS.api(url, opts);
      const res = await fetch(url, {
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf(), ...((opts && opts.headers) || {}) },
        ...(opts || {}),
      });
      const data = await res.json().catch(() => ({}));
      if (!res.ok) throw { status: res.status, data };
      return data;
    }
    const toast = (m, t) => (MS.toast ? MS.toast(m, t) : alert(m));
    const rupiah = (n) => (MS.rupiah ? MS.rupiah(n) : 'Rp' + Number(n || 0).toLocaleString('id-ID'));

    // ---- 1. Order: pilih paket reaktif + ringkasan ----
    (function initOrder() {
      const el = document.getElementById('vue-order');
      if (!el) return;
      const boot = window.__BOOT_ORDER__ || {};
      createApp({
        setup() {
          const packages = ref(boot.packages || []);
          const packageId = ref(String(boot.packageId || document.getElementById('package_id')?.value || ''));
          const paymentMethod = ref(boot.paymentMethod || document.querySelector('input[name="payment_method"]:checked')?.value || 'cash_on_arrival');
          const submitting = ref(false);
          const loadingPkg = ref(false);
          const selected = computed(() => packages.value.find((p) => String(p.id) === String(packageId.value)) || null);
          function select(id) {
            packageId.value = String(id);
            const hidden = document.getElementById('package_id');
            if (hidden) hidden.value = String(id);
          }
          // Sinkron lokasi dari Leaflet (map.js dispatch ms:loc)
          const lat = ref(document.getElementById('latitude')?.value || '');
          const lng = ref(document.getElementById('longitude')?.value || '');
          window.addEventListener('ms:loc', (e) => {
            if (e.detail) { lat.value = e.detail.lat; lng.value = e.detail.lng; }
          });
          ['latitude', 'longitude'].forEach((id) => {
            document.getElementById(id)?.addEventListener('input', (e) => {
              if (id === 'latitude') lat.value = e.target.value;
              else lng.value = e.target.value;
            });
          });
          function paint() {
            document.querySelectorAll('#order-form [data-package]').forEach((c) => {
              const active = String(c.dataset.package) === String(packageId.value);
              c.classList.toggle('ring-2', active);
              c.classList.toggle('ring-white', active);
              c.setAttribute('aria-pressed', active ? 'true' : 'false');
            });
          }
          watch(packageId, paint);
          async function refreshPackages() {
            loadingPkg.value = true;
            try {
              const j = await api('/api/packages');
              if (j.success && Array.isArray(j.data) && j.data.length) packages.value = j.data;
            } catch { /* fallback ke SSR */ }
            loadingPkg.value = false;
          }
          onMounted(() => {
            if (!packages.value.length) refreshPackages();
            // Progressive enhancement: hubungkan tombol SSR ke state Vue
            document.querySelectorAll('#order-form [data-package]').forEach((c) => {
              c.addEventListener('click', () => select(c.dataset.package));
            });
            paint();
          });
          function onSubmit() {
            if (submitting.value) return false;
            submitting.value = true;
            return true; // biarkan POST normal agar redirect + flash tetap jalan
          }
          return { packages, packageId, paymentMethod, submitting, loadingPkg, selected, select, lat, lng, refreshPackages, onSubmit, rupiah };
        },
      }).mount(el);
    })();

    // ---- 2. Tracking: polling reaktif, Leaflet via MS_TRACK ----
    (function initTracking() {
      const el = document.getElementById('vue-tracking');
      if (!el) return;
      const code = el.dataset.code || '';
      createApp({
        setup() {
          const status = ref(el.dataset.status || '');
          const progress = ref(parseFloat(el.dataset.progress || '0') || 0);
          const distanceKm = ref('-');
          const etaMin = ref('-');
          const vehicle = ref({ name: '-', code: '-', status: '-' });
          const logs = ref([]);
          const loading = ref(true);
          const stopped = ref(false);
          const err = ref('');
          const pct = computed(() => Math.round((progress.value || 0) * 100));
          const statusLabel = { pending: 'Baru', searching_driver: 'Mencari armada', assigned: 'Armada ditetapkan', driver_arriving: 'Armada meluncur', arrived: 'Armada tiba', in_service: 'Layanan berjalan', completed: 'Selesai', cancelled: 'Dibatalkan' };
          async function poll() {
            if (!code || stopped.value) return;
            try {
              const j = await api('/api/tracking/' + encodeURIComponent(code));
              const d = j.data || {};
              status.value = d.status || status.value;
              progress.value = d.progress ?? progress.value;
              distanceKm.value = d.distance_km ?? distanceKm.value;
              etaMin.value = d.eta_min ?? etaMin.value;
              if (d.vehicle) vehicle.value = { name: d.vehicle.name || '-', code: d.vehicle.code || '-', status: d.vehicle.status || '-' };
              if (Array.isArray(d.logs)) logs.value = d.logs;
              // Teruskan ke Leaflet imperatif (tracking.js expose window.MS_TRACK.update)
              if (window.MS_TRACK && typeof window.MS_TRACK.update === 'function') {
                window.MS_TRACK.update(d);
              }
              const badge = document.getElementById('t-status');
              if (badge) badge.textContent = statusLabel[d.status] || d.status;
              const bar = document.getElementById('t-progress');
              if (bar) bar.style.width = Math.round((d.progress || 0) * 100) + '%';
              const lbl = document.getElementById('t-progress-label');
              if (lbl) lbl.textContent = Math.round((d.progress || 0) * 100) + '% • ' + d.distance_km + ' km lagi • ETA ±' + d.eta_min + ' mnt (simulasi)';
              if (['completed', 'cancelled'].includes(d.status)) {
                stopped.value = true;
                const pn = document.getElementById('poll-note');
                if (pn) pn.textContent = 'Perjalanan berakhir (' + d.status + '). Polling dihentikan.';
                return;
              }
              err.value = '';
            } catch (e) {
              err.value = (e && e.data && e.data.message) || 'Gagal memuat update.';
            }
            loading.value = false;
            if (!stopped.value) setTimeout(poll, 5000);
          }
          onMounted(poll);
          return { status, progress, pct, distanceKm, etaMin, vehicle, logs, loading, stopped, err, statusLabel };
        },
      }).mount(el);
    })();

    // ---- 3. Orders (user): filter client-side + cancel via API ----
    (function initOrders() {
      const el = document.getElementById('vue-orders');
      if (!el) return;
      const boot = window.__BOOT_ORDERS__ || {};
      createApp({
        setup() {
          const orders = ref(boot.orders || []);
          const statusF = ref(boot.status || 'all');
          const q = ref(boot.q || '');
          const busy = ref('');
          const filtered = computed(() => {
            const needle = q.value.trim().toLowerCase();
            return orders.value.filter((o) => {
              if (statusF.value === 'active' && !['pending', 'searching_driver', 'assigned', 'driver_arriving', 'arrived', 'in_service'].includes(o.status)) return false;
              if (statusF.value !== 'all' && statusF.value !== 'active' && o.status !== statusF.value) return false;
              if (needle && !(o.order_code || '').toLowerCase().includes(needle)) return false;
              return true;
            });
          });
          async function refresh() {
            try {
              const j = await api('/api/orders');
              if (j.success && Array.isArray(j.data)) orders.value = j.data;
            } catch { /* tetap tampilkan SSR */ }
          }
          async function cancel(code) {
            if (!confirm('Batalkan pesanan ' + code + '?')) return;
            busy.value = code;
            try {
              await api('/api/orders/' + encodeURIComponent(code) + '/cancel', { method: 'POST', body: '{}' });
              toast('Pesanan ' + code + ' dibatalkan.', 'success');
              await refresh();
              window.location.reload();
            } catch (e) {
              toast((e.data && e.data.message) || 'Gagal membatalkan.', 'error');
            }
            busy.value = '';
          }
          onMounted(() => { if (!orders.value.length) refresh(); });
          return { orders, statusF, q, filtered, busy, cancel, refresh, rupiah };
        },
      }).mount(el);
    })();

    // ---- 4. Home: status aktif live ----
    (function initHome() {
      const el = document.getElementById('vue-home-active');
      if (!el) return;
      const code = el.dataset.code || '';
      if (!code) return;
      createApp({
        setup() {
          const status = ref(el.dataset.status || '');
          async function tick() {
            try {
              const j = await api('/api/tracking/' + encodeURIComponent(code));
              if (j.data && j.data.status) status.value = j.data.status;
              if (['completed', 'cancelled'].includes(j.data.status)) return;
            } catch { /* abaikan, SSR tetap ada */ }
            setTimeout(tick, 15000);
          }
          onMounted(() => setTimeout(tick, 5000));
          return { status };
        },
      }).mount(el);
    })();

    // ---- 5. Admin orders: filter + refresh via API ----
    (function initAdminOrders() {
      const el = document.getElementById('vue-admin-orders');
      if (!el) return;
      const boot = window.__BOOT_ADMIN_ORDERS__ || {};
      createApp({
        setup() {
          const orders = ref(boot.orders || []);
          const statusF = ref(boot.status || 'all');
          const q = ref(boot.q || '');
          const loading = ref(false);
          const filtered = computed(() => {
            const needle = q.value.trim().toLowerCase();
            return orders.value.filter((o) => {
              if (statusF.value !== 'all' && statusF.value !== '' && o.status !== statusF.value) return false;
              if (needle && !(o.order_code || '').toLowerCase().includes(needle)) return false;
              return true;
            });
          });
          async function refresh() {
            loading.value = true;
            try {
              const j = await api('/api/admin/orders');
              if (j.success && Array.isArray(j.data)) orders.value = j.data;
            } catch (e) { toast((e.data && e.data.message) || 'Gagal memuat.', 'error'); }
            loading.value = false;
          }
          onMounted(() => { if (!orders.value.length) refresh(); });
          return { orders, statusF, q, filtered, loading, refresh };
        },
      }).mount(el);
    })();

    // ---- 6. Driver: ambil order + update status via API ----
    (function initDriver() {
      const el = document.getElementById('vue-driver');
      if (!el) return;
      const boot = window.__BOOT_DRIVER__ || {};
      createApp({
        setup() {
          const jobs = ref(boot.jobs || []);
          const mine = ref(boot.mine || []);
          const online = ref(!!boot.online);
          const busy = ref('');
          async function post(url, body) {
            return api(url, { method: 'POST', body: JSON.stringify(body || {}) });
          }
          async function toggleOnline() {
            busy.value = 'online';
            try {
              await post('/api/driver/availability', { online: !online.value ? '1' : '0' });
              online.value = !online.value;
              toast(online.value ? 'Kamu ONLINE.' : 'Kamu OFFLINE.', 'success');
              window.location.reload();
            } catch (e) { toast((e.data && e.data.message) || 'Gagal.', 'error'); }
            busy.value = '';
          }
          async function accept(id) {
            busy.value = 'accept-' + id;
            try {
              await post('/api/driver/orders/' + id + '/accept', {});
              toast('Order diambil.', 'success');
              window.location.reload();
            } catch (e) { toast((e.data && e.data.message) || 'Gagal ambil order.', 'error'); }
            busy.value = '';
          }
          async function setStatus(id, st) {
            busy.value = 'st-' + id + st;
            try {
              await post('/api/driver/orders/' + id + '/status', { status: st });
              toast('Status → ' + st, 'success');
              window.location.reload();
            } catch (e) { toast((e.data && e.data.message) || 'Gagal update.', 'error'); }
            busy.value = '';
          }
          return { jobs, mine, online, busy, toggleOnline, accept, setStatus };
        },
      }).mount(el);
    })();

    // ---- 7. Filter generik admin (users/vehicles/packages): tanpa reload ----
    document.querySelectorAll('[data-vue-table-filter]').forEach((wrap) => {
      const input = wrap.querySelector('input[data-filter-input]');
      const rows = wrap.querySelectorAll('[data-filter-text]');
      if (!input || !rows.length) return;
      input.addEventListener('input', () => {
        const needle = input.value.trim().toLowerCase();
        rows.forEach((tr) => {
          const hay = (tr.getAttribute('data-filter-text') || '').toLowerCase();
          tr.style.display = !needle || hay.includes(needle) ? '' : 'none';
        });
      });
    });
  });
})();
