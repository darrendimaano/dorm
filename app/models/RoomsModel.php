<?php
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');

require_once __DIR__ . '/../config/DatabaseConfig.php';

class RoomsModel extends Model {
    protected $table = 'rooms';
    protected $primary_key = 'id';
    protected $pdo;

    public function __construct() {
        parent::__construct();
        $dbConfig = DatabaseConfig::getInstance();
        $this->pdo = $dbConfig->getConnection();
    }

    // Get all rooms
    public function getAllRooms() {
        $stmt = $this->pdo->query("SELECT * FROM {$this->table} ORDER BY room_number ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Insert room
    public function insertRoom($data) {
        // Generate hash for picture if provided
        $picture_hash = null;
        if (!empty($data['picture'])) {
            $picture_hash = md5($data['picture'] . time() . rand()); // Create unique hash
        }
        
        $sql = "INSERT INTO {$this->table} (room_number, room_name, beds, available, payment, monthly_rate, picture, picture_hash)
                VALUES (:room_number, :room_name, :beds, :available, :payment, :monthly_rate, :picture, :picture_hash)";
        $stmt = $this->pdo->prepare($sql);
        
        // Ensure all required parameters are present
        $params = [
            'room_number' => $data['room_number'] ?? '',
            'room_name' => $data['room_name'] ?? ('Room ' . ($data['room_number'] ?? '')),
            'beds' => $data['beds'] ?? 1,
            'available' => $data['available'] ?? 1,
            'payment' => $data['payment'] ?? 0,
            'monthly_rate' => $data['monthly_rate'] ?? $data['payment'] ?? 0,
            'picture' => $data['picture'] ?? null,
            'picture_hash' => $picture_hash
        ];
        
        return $stmt->execute($params);
    }

    // Find one room (alias for consistency)
    public function find($id, $with_deleted = false) {
        return $this->findRoom($id);
    }
    
    // Find one room
    public function findRoom($id) {
        $stmt = $this->pdo->prepare("SELECT * FROM {$this->table} WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Update room
    public function update($id, $data) {
        return $this->updateRoom($id, $data);
    }
    
    // Update room
    public function updateRoom($id, $data) {
        // First get existing room data
        $existing = $this->find($id);
        if (!$existing) {
            return false;
        }
        
        // Generate hash for picture if provided, otherwise keep existing
        $picture_hash = $existing['picture_hash'] ?? null;
        if (!empty($data['picture']) && $data['picture'] !== ($existing['picture'] ?? null)) {
            $picture_hash = md5($data['picture'] . time() . rand()); // Create unique hash when picture changes
        }
        
        $sql = "UPDATE {$this->table} 
                SET room_number = :room_number, room_name = :room_name, beds = :beds, available = :available, payment = :payment, monthly_rate = :monthly_rate, picture = :picture, picture_hash = :picture_hash
                WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);

        $hasPayment = array_key_exists('payment', $data);
        $hasMonthlyRate = array_key_exists('monthly_rate', $data);

        $params = [
            'id' => $id,
            'room_number' => array_key_exists('room_number', $data) ? (string) $data['room_number'] : (string) ($existing['room_number'] ?? ''),
            'room_name' => array_key_exists('room_name', $data)
                ? (string) $data['room_name']
                : (string) ($existing['room_name'] ?? ('Room ' . ($existing['room_number'] ?? ''))),
            'beds' => array_key_exists('beds', $data) ? (int) $data['beds'] : (int) ($existing['beds'] ?? 1),
            'available' => array_key_exists('available', $data) ? (int) $data['available'] : (int) ($existing['available'] ?? 0),
            'payment' => $hasPayment ? (float) $data['payment'] : (float) ($existing['payment'] ?? 0),
            'monthly_rate' => $hasMonthlyRate
                ? (float) $data['monthly_rate']
                : ($hasPayment ? (float) ($data['payment'] ?? 0) : (float) ($existing['monthly_rate'] ?? $existing['payment'] ?? 0)),
            'picture' => array_key_exists('picture', $data) ? $data['picture'] : ($existing['picture'] ?? null),
            'picture_hash' => $picture_hash
        ];

        return $stmt->execute($params);
    }

    // Delete room (alias for consistency)
    public function delete($id) {
        return $this->deleteRoom($id);
    }
    
    // Delete room
    public function deleteRoom($id) {
        $stmt = $this->pdo->prepare("DELETE FROM {$this->table} WHERE id = ?");
        if (!$stmt->execute([$id])) {
            $error = $stmt->errorInfo();
            throw new Exception($error[2] ?? 'Unknown database error while deleting room.');
        }
        return $stmt->rowCount() > 0;
    }

    /**
     * Maintenance helper to recalculate availability for every room based on active occupancy
     * and approved reservations. Use only for manual reconciliation tasks (e.g., after bulk
     * data fixes); regular request flows must rely on explicit increments/decrements to avoid
     * double counting.
     */
    public function refreshAvailabilityCounters(): void {
        $sql = "
            UPDATE {$this->table} r
            LEFT JOIN (
                SELECT room_id, COUNT(*) AS active_count
                FROM room_occupancy
                WHERE status = 'active'
                GROUP BY room_id
            ) occ ON occ.room_id = r.id
            LEFT JOIN (
                SELECT room_id, COUNT(*) AS approved_count
                FROM reservations
                WHERE status = 'approved'
                GROUP BY room_id
            ) res ON res.room_id = r.id
            SET r.available = GREATEST(
                r.beds - GREATEST(
                    COALESCE(occ.active_count, 0),
                    COALESCE(res.approved_count, 0)
                ),
                0
            );
        ";

        try {
            $this->pdo->exec($sql);
        } catch (Throwable $e) {
            throw new Exception('Unable to refresh room availability counters: ' . $e->getMessage(), 0, $e);
        }
    }
}
