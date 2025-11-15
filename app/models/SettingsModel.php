<?php
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');

class SettingsModel extends Model {
    protected $table = 'settings';
    protected $primary_key = 'id';

    // Get settings (first row)
    public function getSettings() {
        // Perform select
        $query = $this->db->select($this->table, '*', ['limit' => 1]);

        // Try fetching as associative array if method exists
        if (method_exists($query, 'fetch_assoc')) {
            return $query->fetch_assoc();
        } elseif (method_exists($query, 'fetch')) {
            return $query->fetch();
        }

        // If select returns array directly
        if (is_array($query) && count($query) > 0) {
            return $query[0];
        }

        return null; // fallback
    }

    // Update settings by id=1
    public function updateSettings($data) {
        return $this->db->update($this->table, $data, ['id' => 1]);
    }
}
