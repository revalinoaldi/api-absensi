<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class MToken extends CI_Model {

	private $tbl = 'oauth_access_token';
	
	public function update($where,$object)
	{
		$this->db->where($where);
		$this->db->update($this->tbl, $object);
		return(($this->db->affected_rows() > 0) ? true : false);
	}
}

/* End of file MToken.php */
/* Location: ./application/models/MToken.php */