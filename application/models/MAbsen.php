<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class MAbsen extends CI_Model {
	private $tbl = 'tbl_absen';
	private $view = 'v_absen';
	
	public function show($where='',$limit='')
	{
		$this->db->select('*');
		$this->db->from($this->view);
		if (@$where && $where != null) {
			$this->db->where($where);
		}
		if (@$limit) {
			$this->db->limit($limit);
		}
		$this->db->order_by('tgl_absen', 'desc');
		return $this->db->get();
	}
	
	public function groupBYDate($where = '')
	{
		$this->db->select('tgl_absen, count(id_karyawan) as total_absen');
		$this->db->from($this->tbl);
		if (@$where != null) {
			$this->db->where($where);
		}
		$this->db->group_by('tgl_absen');
		$this->db->order_by('tgl_absen', 'desc');
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
}

/* End of file MAbsen.php */
/* Location: ./application/models/MAbsen.php */