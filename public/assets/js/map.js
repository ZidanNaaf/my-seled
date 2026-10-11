// Peta order: Leaflet + OSM + Nominatim (debounce) + draggable pin
(function () {
  function initOrderMap() {
    const el = document.getElementById('order-map');
    if (!el || !window.L) return;
    const latInput = document.getElementById('latitude');
    const lngInput = document.getElementById('longitude');
    const addrInput = document.getElementById('address');
    const searchInput = document.getElementById('map-search');
    const searchBtn = document.getElementById('map-search-btn');
    const locateBtn = document.getElementById('btn-locate');

    let lat = parseFloat(latInput.value) || -6.2;
    let lng = parseFloat(lngInput.value) || 106.816666;
    const map = L.map('order-map').setView([lat, lng], 14);
    L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19, attribution: '&copy; OpenStreetMap' }).addTo(map);
    const marker = L.marker([lat, lng], { draggable: true }).addTo(map);
    const circle = L.circle([lat, lng], { radius: 300 }).addTo(map);

    function sync(l1, l2) {
      latInput.value = l1.toFixed(6);
      lngInput.value = l2.toFixed(6);
      document.getElementById('latlng-label').textContent = `${l1.toFixed(5)}, ${l2.toFixed(5)}`;
      marker.setLatLng([l1, l2]);
      circle.setLatLng([l1, l2]);
    }
    marker.on('dragend', () => { const p = marker.getLatLng(); sync(p.lat, p.lng); map.panTo(p); });
    map.on('click', (e) => sync(e.latlng.lat, e.latlng.lng));

    locateBtn?.addEventListener('click', () => {
      if (!navigator.geolocation) return MS.toast('Geolocation tidak didukung browser', 'error');
      locateBtn.disabled = true;
      navigator.geolocation.getCurrentPosition((pos) => {
        const { latitude, longitude } = pos.coords;
        map.setView([latitude, longitude], 16);
        sync(latitude, longitude);
        locateBtn.disabled = false;
      }, () => {
        MS.toast('Izin lokasi ditolak. Pilih manual di peta.', 'error');
        locateBtn.disabled = false;
      }, { timeout: 8000 });
    });

    let t = null;
    async function search(q) {
      if (!q || q.length < 3) return;
      try {
        const url = 'https://nominatim.openstreetmap.org/search?format=json&limit=5&countrycodes=id&q=' + encodeURIComponent(q);
        const res = await fetch(url, { headers: { 'Accept': 'application/json' } });
        const arr = await res.json();
        const box = document.getElementById('search-results');
        box.innerHTML = '';
        if (!arr.length) { box.innerHTML = '<div class="muted text-sm p-2">Tidak ketemu. Coba kata kunci lain.</div>'; return; }
        arr.forEach(r => {
          const b = document.createElement('button');
          b.type = 'button';
          b.className = 'w-full text-left p-2 hover:bg-[#292929] rounded text-sm';
          b.textContent = r.display_name.slice(0, 120);
          b.onclick = () => {
            const la = parseFloat(r.lat), lo = parseFloat(r.lon);
            map.setView([la, lo], 16);
            sync(la, lo);
            if (addrInput && !addrInput.value) addrInput.value = r.display_name.slice(0, 200);
            box.innerHTML = '';
          };
          box.appendChild(b);
        });
      } catch { MS.toast('Pencarian gagal (rate limit OSM?). Coba lagi.', 'error'); }
    }
    searchInput?.addEventListener('input', () => { clearTimeout(t); t = setTimeout(() => search(searchInput.value.trim()), 600); });
    searchBtn?.addEventListener('click', () => search(searchInput.value.trim()));
  }
  document.addEventListener('DOMContentLoaded', initOrderMap);
})();
