// Tracking: poll 5s -> backend advance() -> gerakkan marker + update timeline
(function () {
  function initTracking() {
    const el = document.getElementById('track-map');
    if (!el || !window.L) return;
    const code = el.dataset.code;
    const csrf = document.querySelector('meta[name="csrf"]')?.content || '';
    const destLat = parseFloat(el.dataset.dlat), destLng = parseFloat(el.dataset.dlng);
    const map = L.map('track-map').setView([destLat, destLng], 14);
    L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19, attribution: '&copy; OpenStreetMap' }).addTo(map);
    const destIcon = L.divIcon({ html: '<div style="width:34px;height:34px;border-radius:50%;background:#202020;border:1px solid #383838;display:flex;align-items:center;justify-content:center;font-size:17px;color:#F5F5F5;"><i class="fa-solid fa-toilet"></i></div>', className: '', iconSize: [34, 34], iconAnchor: [17, 17] });
    const vehIcon = L.divIcon({ html: '<div style="width:34px;height:34px;border-radius:50%;background:#F5F5F5;border:1px solid #D4D4D4;display:flex;align-items:center;justify-content:center;font-size:16px;color:#171717;"><i class="fa-solid fa-truck-fast"></i></div>', className: '', iconSize: [34, 34], iconAnchor: [17, 17] });
    const destMarker = L.marker([destLat, destLng], { icon: destIcon }).addTo(map).bindPopup('Titik toilet kamu');
    const vehMarker = L.marker([destLat, destLng], { icon: vehIcon }).addTo(map).bindPopup('Armada Seled');
    let routeLine = null;
    let osrmTried = false;

    async function drawRoute(oLat, oLng) {
      if (osrmTried) return;
      osrmTried = true;
      try {
        const url = `https://router.project-osrm.org/route/v1/driving/${oLng},${oLat};${destLng},${destLat}?overview=full&geometries=geojson`;
        const res = await fetch(url);
        if (!res.ok) throw new Error('osrm');
        const j = await res.json();
        const coords = j.routes?.[0]?.geometry?.coordinates || [];
        if (coords.length) {
          const latlngs = coords.map(c => [c[1], c[0]]);
          routeLine = L.polyline(latlngs, { color: '#D4D4D4' }).addTo(map);
          map.fitBounds(routeLine.getBounds().pad(0.2));
          document.getElementById('route-note').textContent = 'Rute OSRM (demo, bisa gagal sewaktu-waktu).';
          return;
        }
        throw new Error('empty');
      } catch {
        routeLine = L.polyline([[oLat, oLng], [destLat, destLng]], { color: '#D4D4D4', dashArray: '6 6' }).addTo(map);
        map.fitBounds(routeLine.getBounds().pad(0.3));
        document.getElementById('route-note').textContent = 'Rute simulasi garis lurus (fallback, bukan routing nyata).';
      }
    }

    const statusLabel = { pending: 'Baru', searching_driver: 'Mencari armada', assigned: 'Armada ditetapkan', driver_arriving: 'Armada meluncur', arrived: 'Armada tiba', in_service: 'Layanan berjalan', completed: 'Selesai', cancelled: 'Dibatalkan' };

    async function poll() {
      try {
        const res = await fetch(`/api/tracking/${code}`, { headers: { 'X-CSRF-TOKEN': csrf } });
        const j = await res.json();
        if (!res.ok) throw new Error(j.message || 'gagal');
        const d = j.data;
        vehMarker.setLatLng([d.vehicle_lat, d.vehicle_lng]);
        await drawRoute(d.origin_lat, d.origin_lng);
        // Jika fallback garis lurus, update ujung garis ke posisi terkini
        if (routeLine && routeLine.getLatLngs().length === 2) routeLine.setLatLngs([[d.vehicle_lat, d.vehicle_lng], [destLat, destLng]]);
        document.getElementById('t-status').textContent = statusLabel[d.status] || d.status;
        document.getElementById('t-progress').style.width = Math.round(d.progress * 100) + '%';
        document.getElementById('t-progress-label').textContent = Math.round(d.progress * 100) + '% • ' + d.distance_km + ' km lagi • ETA ±' + d.eta_min + ' mnt (simulasi)';
        const tl = document.getElementById('timeline');
        if (tl && d.logs) {
          tl.innerHTML = d.logs.map(l => `<li class="flex gap-2 text-sm py-1"><span class="badge">${MS.fmtDate(l.created_at)}</span><span>${l.old_status || '—'} → <strong>${l.new_status}</strong>${l.note ? ' • ' + l.note : ''}</span></li>`).join('');
        }
        if (['completed', 'cancelled'].includes(d.status)) {
          document.getElementById('poll-note').textContent = 'Perjalanan berakhir (' + d.status + '). Polling dihentikan.';
          return; // stop
        }
      } catch (e) {
        document.getElementById('poll-note').textContent = 'Gagal memuat update. Coba muat ulang.';
      }
      setTimeout(poll, 5000);
    }
    poll();
  }
  document.addEventListener('DOMContentLoaded', initTracking);
})();
