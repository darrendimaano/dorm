<?php
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');

class UsersModel extends Model {
    // ✅ match parent model method name and signature
    public function all($with_deleted = false) {
        return $this->db->table('users')->get_all();
    }

    // ✅ match parent model method signature
    public function find($id, $with_deleted = false) {
        return $this->db->table('users')->where('id', $id)->get();
    }

    public function insert($data) {
        return $this->db->table('users')->insert($data);
    }

    public function update($id, $data) {
        return $this->db->table('users')->where('id', $id)->update($data);
    }

    public function delete($id) {
        return $this->db->table('users')->where('id', $id)->delete();
    }
}
