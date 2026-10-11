<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Models\Order;

/** Simulasi progress-on-poll: maju setiap kali API tracking dipanggil. */
class TrackingService
{
    public static function haversineKm(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $R = 6371.0;
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        $a = sin($dLat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon / 2) ** 2;
        return 2 * $R * asin(min(1, sqrt($a)));
    }

    /** Maju-kan simulasi order + kembalikan snapshot untuk API. */
    public static function advance(array $order): array
    {
        $pdo = Database::connection();
        $cfg = require BASE_PATH . '/config/app.php';
        $speed = (float)($cfg['sim_speed_per_sec'] ?? 0.02);

        $status = $order['status'];
        $terminal = in_array($status, ['completed', 'cancelled'], true);

        $originLat = $order['origin_latitude'] ?? null;
        $originLng = $order['origin_longitude'] ?? null;
        $destLat = (float)$order['destination_latitude'];
        $destLng = (float)$order['destination_longitude'];

        // Jika belum ada origin (antrean tanpa kendaraan), pakai titik dummy dekat tujuan
        if ($originLat === null || $originLng === null) {
            $originLat = $destLat + 0.03;
            $originLng = $destLng + 0.03;
        } else {
            $originLat = (float)$originLat;
            $originLng = (float)$originLng;
        }

        $now = time();
        $lastSim = isset($order['last_sim_at']) && $order['last_sim_at']
            ? strtotime($order['last_sim_at'] . ' UTC') : strtotime($order['updated_at'] . ' UTC');
        if (!$lastSim) $lastSim = $now;
        $elapsed = max(0, $now - $lastSim);
        // Batasi lompatan (misal user kembali setelah 1 jam) agar tidak langsung selesai
        $elapsed = min($elapsed, 30);

        $progress = (float)($order['progress'] ?? 0);

        if (!$terminal && $order['vehicle_id']) {
            $progress = min(1.0, $progress + $elapsed * $speed);
            $newStatus = $status;
            if ($progress >= 1.0) {
                $newStatus = 'arrived';
            } elseif ($progress >= 0.05 && in_array($status, ['assigned'], true)) {
                $newStatus = 'driver_arriving';
            }
            // Auto: arrived -> in_service setelah 20 detik di titik (pakai kolom in_service_at sederhana via note)
            if ($status === 'arrived' && $elapsed >= 0) {
                // Biarkan driver/admin menyelesaikan; tapi untuk demo: jika progress 1 dan >25 dtk sejak arrived, jadikan in_service
                $logs = Order::logs((int)$order['id']);
                $arrivedAt = null;
                foreach (array_reverse($logs) as $l) {
                    if ($l['new_status'] === 'arrived') { $arrivedAt = strtotime($l['created_at'] . ' UTC'); break; }
                }
                if ($arrivedAt && ($now - $arrivedAt) > 25) {
                    $newStatus = 'in_service';
                }
            } elseif ($status === 'in_service') {
                $logs = Order::logs((int)$order['id']);
                $svcAt = null;
                foreach (array_reverse($logs) as $l) {
                    if ($l['new_status'] === 'in_service') { $svcAt = strtotime($l['created_at'] . ' UTC'); break; }
                }
                if ($svcAt && ($now - $svcAt) > 45) {
                    $newStatus = 'completed';
                }
            }

            $vehLat = $originLat + ($destLat - $originLat) * $progress;
            $vehLng = $originLng + ($destLng - $originLng) * $progress;

            $pdo->beginTransaction();
            try {
                $nowStr = gmdate('Y-m-d H:i:s');
                if ($newStatus !== $status) {
                    if (!Order::canTransition($status, $newStatus)) {
                        $newStatus = $status; // jangan langgar state machine
                    } else {
                        $pdo->prepare('UPDATE orders SET status = ?, progress = ?, last_sim_at = ?, updated_at = ? WHERE id = ?')
                            ->execute([$newStatus, $progress, $nowStr, $nowStr, $order['id']]);
                        DispatchService::log($pdo, (int)$order['id'], $status, $newStatus, null, 'Simulasi perjalanan (' . round($progress * 100) . '%).');
                        if ($newStatus === 'completed') {
                            $pdo->prepare('UPDATE orders SET completed_at = ? WHERE id = ?')->execute([$nowStr, $order['id']]);
                            $pdo->prepare("UPDATE vehicles SET status = 'available', current_order_id = NULL, latitude = ?, longitude = ?, updated_at = ? WHERE id = ?")
                                ->execute([$destLat, $destLng, $nowStr, $order['vehicle_id']]);
                            $pdo->prepare("UPDATE payments SET status = 'paid' WHERE order_id = ? AND status IN ('unpaid','pending')")
                                ->execute([$order['id']]);
                        } else {
                            $pdo->prepare('UPDATE vehicles SET latitude = ?, longitude = ?, status = ?, updated_at = ? WHERE id = ?')
                                ->execute([$vehLat, $vehLng, $newStatus === 'arrived' || $newStatus === 'in_service' ? 'on_trip' : 'assigned', $nowStr, $order['vehicle_id']]);
                        }
                        $status = $newStatus;
                    }
                } else {
                    $pdo->prepare('UPDATE orders SET progress = ?, last_sim_at = ? WHERE id = ?')
                        ->execute([$progress, $nowStr, $order['id']]);
                    if ($order['vehicle_id']) {
                        // Throttle vehicle_location_logs: tulis max 1x per advance call (poll 5s -> wajar)
                        $pdo->prepare('UPDATE vehicles SET latitude = ?, longitude = ?, updated_at = ? WHERE id = ?')
                            ->execute([$vehLat, $vehLng, $nowStr, $order['vehicle_id']]);
                        $pdo->prepare('INSERT INTO vehicle_location_logs(vehicle_id, order_id, latitude, longitude, recorded_at) VALUES(?,?,?,?,?)')
                            ->execute([$order['vehicle_id'], $order['id'], $vehLat, $vehLng, $nowStr]);
                    }
                }
                $pdo->commit();
            } catch (\Throwable $e) {
                if ($pdo->inTransaction()) $pdo->rollBack();
            }
            $order['status'] = $status;
            $order['progress'] = $progress;
        }

        $vehLat = $originLat + ($destLat - $originLat) * min(1, (float)$order['progress']);
        $vehLng = $originLng + ($destLng - $originLng) * min(1, (float)$order['progress']);
        // Jika kendaraan punya posisi terbaru di DB, pakai itu
        if (!empty($order['vehicle_id'])) {
            $st = $pdo->prepare('SELECT latitude, longitude FROM vehicles WHERE id = ?');
            $st->execute([$order['vehicle_id']]);
            if ($v = $st->fetch()) {
                $vehLat = (float)$v['latitude'];
                $vehLng = (float)$v['longitude'];
            }
        }
        $distKm = self::haversineKm($vehLat, $vehLng, $destLat, $destLng);
        $totalKm = self::haversineKm($originLat, $originLng, $destLat, $destLng);
        // Estimasi: asumsi 25 km/jam perkotaan simulasi
        $etaMin = $distKm / 25 * 60;

        return [
            'progress' => round((float)$order['progress'], 4),
            'status' => $order['status'],
            'vehicle_lat' => $vehLat,
            'vehicle_lng' => $vehLng,
            'origin_lat' => $originLat,
            'origin_lng' => $originLng,
            'dest_lat' => $destLat,
            'dest_lng' => $destLng,
            'distance_km' => round($distKm, 2),
            'total_km' => round($totalKm, 2),
            'eta_min' => (int)ceil($etaMin),
            'is_simulated' => true,
        ];
    }
}
