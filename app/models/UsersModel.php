<?php
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');

class UsersModel extends Model {
    protected $table = 'students';
    protected $primary_key = 'id';

    // ✅ match parent model method name and signature
    public function all($with_deleted = false) {
        return $this->db->table($this->table)->get_all();
    }

    // ✅ match parent model method signature
    public function find($id, $with_deleted = false) {
        return $this->db->table($this->table)->where($this->primary_key, $id)->get();
    }

    public function insert($data) {
        return $this->db->table($this->table)->insert($data);
    }

    public function update($id, $data) {
        return $this->db->table($this->table)->where($this->primary_key, $id)->update($data);
    }

    public function delete($id) {
        return $this->db->table($this->table)->where($this->primary_key, $id)->delete();
    }

    public function emailExistsForOther($email, $excludeId = null) {
        $builder = $this->db->table($this->table)->where('email', $email);
        if ($excludeId !== null) {
            $builder->where($this->primary_key, '!=', $excludeId);
        }

        return (bool) $builder->get();
    }
}
