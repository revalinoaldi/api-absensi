<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class MKaryawan extends CI_Model {
	private $tbl = 'tbl_karyawan';
	private $token = 'v_employee';
	
	public function show($where='')
	{
		$this->db->select('*');
		$this->db->from($this->tbl);
		if (@$where && $where != null) {
			$this->db->where($where);
		}
		$this->db->order_by('nama_karyawan', 'asc');
		return $this->db->get();
	}

	public function showfield($where='')
	{
		$this->db->select('*');
		$this->db->from($this->token);
		if (@$where && $where != null) {
			$this->db->where($where);
		}
		$this->db->order_by('id_karyawan', 'asc');
		return $this->db->get();
	}

	public function insert($object)
	{
		$this->db->insert($this->tbl, $object);
		return(($this->db->affected_rows() > 0) ? true : false);
	}

	public function update($where,$object)
	{
		$this->db->where($where);
		$this->db->update($this->tbl, $object);
		return(($this->db->affected_rows() > 0) ? true : false);
	}

	public function delete($where)
	{
		$this->db->where($where);
		$this->db->delete($this->tbl);
		return(($this->db->affected_rows() > 0) ? true : false);
	}
}

/* End of file MKaryawan.php */
/* Location: ./application/models/MKaryawan.php */