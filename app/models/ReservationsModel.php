<?php
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');

require_once __DIR__ . '/../config/DatabaseConfig.php';

class ReservationsModel extends Model {

    protected $db;

    public function __construct() {
        parent::__construct();
        $dbConfig = DatabaseConfig::getInstance();
        $this->db = $dbConfig->getConnection();
    }

    public function getPendingReservations() {
        $stmt = $this->db->query("SELECT r.*, s.fname, s.lname, s.email, ro.room_number, ro.beds, ro.available, ro.payment 
                                 FROM reservations r 
                                 JOIN students s ON r.user_id = s.id 
                                 JOIN rooms ro ON r.room_id = ro.id 
                                 WHERE r.status = 'pending' 
                                 ORDER BY r.id DESC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getAllReservations() {
        $stmt = $this->db->query("SELECT r.*, s.fname, s.lname, s.email, ro.room_number, ro.beds, ro.available, ro.payment 
                                 FROM reservations r 
                                 JOIN students s ON r.user_id = s.id 
                                 JOIN rooms ro ON r.room_id = ro.id 
                                 ORDER BY r.id DESC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getAllWithDetails() {
        return $this->getAllReservations();
    }

    public function updateStatus($id, $status) {
        $stmt = $this->db->prepare("UPDATE reservations SET status = ? WHERE id = ?");
        return $stmt->execute([$status, $id]);
    }

    public function getReservationById($id) {
        $stmt = $this->db->prepare("SELECT r.*, s.fname, s.lname, ro.room_number, ro.available 
                                   FROM reservations r 
                                   JOIN students s ON r.user_id = s.id 
                                   JOIN rooms ro ON r.room_id = ro.id 
                                   WHERE r.id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function insert($data) {
        $stmt = $this->db->prepare("INSERT INTO reservations (user_id, room_id, status) VALUES (?, ?, ?)");
        return $stmt->execute([$data['user_id'], $data['room_id'], $data['status']]);
    }
}
