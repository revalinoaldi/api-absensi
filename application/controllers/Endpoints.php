<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Endpoints extends CI_Controller {

	public function index()
	{
		$data['data'] = [
			[
				'uri' => './Login',
				'method' => 'POST',
				'parameter' => [],
				'post' => [
					[
						'name' => 'email',
						'title' => 'Required'
					],
					[
						'name' => 'pass',
						'title' => 'Required'
					]
				],
				'keterangan' => 'Untuk login user'
			],
			[
				'uri' => './Api/KaryawanController/',
				'method' => 'GET',
				'parameter' => [],
				'post' => [],
				'keterangan' => 'Mengambil data karyawan'
			],
			[
				'uri' => './Api/KaryawanController/{id_karyawan}',
				'method' => 'GET',
				'parameter' => [],
				'post' => [],
				'keterangan' => 'Mengambil data karyawan berdasarkan id employee'
			],
			[
				'uri' => './Api/KaryawanController/{id_karyawan}',
				'method' => 'DELETE',
				'parameter' => [],
				'post' => [],
				'keterangan' => 'Delete karyawan berdasarkan id karyawan'
			],
			[
				'uri' => './Api/KaryawanController/',
				'method' => 'POST',
				'parameter' => [],
				'post' => [
					[
						'name' => 'id',
						'title' => 'Required'
					],
					[
						'name' => 'nama',
						'title' => 'Required'
					],
					[
						'name' => 'email',
						'title' => 'Required'
					],
					[
						'name' => 'pass',
						'title' => 'Required'
					],
				],
				'keterangan' => 'Create new employee'
			],
			[
				'uri' => './Api/KaryawanController/{id}',
				'method' => 'POST',
				'parameter' => [],
				'post' => [
					[
						'name' => 'id',
						'title' => 'Required'
					],
					[
						'name' => 'nama',
						'title' => 'Required'
					],
					[
						'name' => 'email',
						'title' => 'Required'
					],
					[
						'name' => 'pass',
						'title' => 'Required'
					],
				],
				'keterangan' => 'Update employee'
			],
		];
		$this->load->view('v_endpoint', $data, FALSE);
	}

}

/* End of file Endpoints.php */
/* Location: ./application/controllers/Endpoints.php */