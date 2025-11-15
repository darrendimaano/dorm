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
        if (!empty($data['picture'])) {
            $picture_hash = md5($data['picture'] . time() . rand()); // Create unique hash
        }
        
        $sql = "UPDATE {$this->table} 
                SET room_number = :room_number, room_name = :room_name, beds = :beds, available = :available, payment = :payment, monthly_rate = :monthly_rate, picture = :picture, picture_hash = :picture_hash
                WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        
        // Ensure all required parameters are present
        $params = [
            'id' => $id,
            'room_number' => $data['room_number'] ?? '',
            'room_name' => $data['room_name'] ?? ('Room ' . ($data['room_number'] ?? '')),
            'beds' => $data['beds'] ?? 1,
            'available' => $data['available'] ?? 1,
            'payment' => $data['payment'] ?? 0,
            'monthly_rate' => $data['monthly_rate'] ?? $data['payment'] ?? 0,
            'picture' => $data['picture'] ?? $existing['picture'],
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
        return $stmt->execute([$id]);
    }
}
