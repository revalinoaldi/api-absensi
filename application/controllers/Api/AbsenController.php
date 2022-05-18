<?php
defined('BASEPATH') OR exit('No direct script access allowed');
use chriskacerguis\RestServer\RestController;
use \Firebase\JWT\JWT;

class AbsenController extends RestController {

	const HTTP_OK = RestController::HTTP_OK;
	const HTTP_CREATED = RestController::HTTP_CREATED;
	const HTTP_BAD_REQUEST = RestController::HTTP_BAD_REQUEST;
	const HTTP_NOT_FOUND = RestController::HTTP_NOT_FOUND;

	private $result = false;
	public $key = '';
	public $tokenkey = '';

	public function __construct()
	{
		parent::__construct();
		date_default_timezone_set('Asia/Jakarta');
        $origin = @$_SERVER['HTTP_ORIGIN'] ? $_SERVER['HTTP_ORIGIN'] : $this->input->request_headers()['Host'];
        header("Access-Control-Allow-Origin: ".$origin);
        header('Access-Control-Allow-Credentials: true');

		$this->methods['index_get']['limit'] = 500; // 500 requests per hour per user/key
        $this->methods['index_post']['limit'] = 100; // 100 requests per hour per user/key
        $this->methods['update_post']['limit'] = 100; // 100 requests per hour per user/key
        $this->methods['index_delete']['limit'] = 50; // 50 requests per hour per user/key

        if (!$this->_key_exists($this->input->request_headers()['token']) && !$this->input->request_headers()['tokenkey']){
        	$this->response([
        		'status' => FALSE,
        		'message' => 'Invalid API key'
        	], RestController::HTTP_BAD_REQUEST);
        }else{
        	$this->key = $this->input->request_headers()['token'];
        	$this->tokenkey = $this->input->request_headers()['tokenkey'];
        	$this->_checkToken();
        }

        $this->load->model('MAbsen','absen');
        $this->load->model('MToken','token');
    }

    private function _key_exists($key)
    {
    	return $this->rest->db
    	->where(config_item('rest_key_column'), $key)
    	->count_all_results(config_item('rest_keys_table')) > 0;
    }

    private function _checkToken()
    {
    	try {
    		$payload = JWT::decode($this->tokenkey, $this->key, array('HS256'));
    		$time = new DateTimeImmutable();

    		if ($time->getTimestamp() > $payload->exp) {
    			$this->response([
    				'status' => FALSE,
    				'message' => 'API Key Expired'
    			], RestController::HTTP_BAD_REQUEST);  
    		}
    	} catch (Exception $e) {
    		$this->response([
    			'status' => FALSE,
    			'message' => $e->getMessage()
    		], RestController::HTTP_BAD_REQUEST);  
    	}
    }

    public function checkAbsenHarian_get($id)
    {
    	if (@$id) {
    		$datacek = [
    			'id_karyawan' => $id,
    			'tgl_absen' => date('Y-m-d')
    		];
    		$cek = $this->absen->show($datacek);
    		if ($cek->num_rows() < 1) {
    			$this->response( [
    				'status' => TRUE,
    			], RestController::HTTP_OK );
    		}else{
    			$this->response( [
    				'status' => FALSE,
    			], RestController::HTTP_OK );
    		}
    	}else{
    		$this->response( [
    			'status' => FALSE,
    			'title' => 'Employee was required',
    		], RestController::HTTP_NOT_FOUND );
    	}
    }

    public function index_get($id='')
    {
    	$arr = [];
    	if (@$this->input->get('date_from') && @$this->input->get('date_to')) {
    		$arr = [
    			'tgl_absen >=' => date('Y-m-d', strtotime($this->input->get('date_from'))),
    			'tgl_absen <=' => date('Y-m-d', strtotime($this->input->get('date_to')))
    		];
    	}elseif (@$this->input->get('date')) {
    		$arr = ['tgl_absen' => date('Y-m-d', strtotime($this->input->get('date')))];
    	}elseif (@$this->input->get('id')) {
            $arr = ['id' => $this->input->get('id')];
        }

    	if (@$id) {
    		$where = ['id_karyawan' => $id];
    		$get = $this->absen->show(array_merge($arr,$where));
    		$data = $get->result();
    	}else{
    		$get = $this->absen->show($arr);
    		$data = $get->result();
    	}
    	if ($get->num_rows() > 0) {
    		$this->response( [
    			'status' => TRUE,
    			'title' => 'Success get Absensi',
    			'data' => $data
    		], RestController::HTTP_OK );
    	}else{
    		$this->response( [
    			'status' => FALSE,
    			'title' => 'Absensi not found',
    		], RestController::HTTP_NOT_FOUND );
    	}
    }

    public function absenlist_get()
    {
        $arr = [];
        if (@$this->input->get('date_from') && @$this->input->get('date_to')) {
            $arr = [
                'tgl_absen >=' => date('Y-m-d', strtotime($this->input->get('date_from'))),
                'tgl_absen <=' => date('Y-m-d', strtotime($this->input->get('date_to')))
            ];
        }else{
            $arr = null;
        }

        $get = $this->absen->groupBYDate($arr);
        $data = $get->result();
        
        if ($get->num_rows() > 0) {
            $this->response( [
                'status' => TRUE,
                'title' => 'Success get List Absen by Date',
                'data' => $data
            ], RestController::HTTP_OK );
        }else{
            $this->response( [
                'status' => FALSE,
                'title' => 'List Absensi not found',
            ], RestController::HTTP_NOT_FOUND );
        }
    }

    public function absenmasuk_post()
    {
        $newData = json_decode($this->input->raw_input_stream,true); 

    	if (@$newData['id']) {
    		$id = $newData['id'];
    		$key = $newData['idkey'];

    		if (!$this->_key_exists($key)){
    			$this->response([
    				'status' => FALSE,
                    'title' => 'Error!',
    				'message' => 'Invalid API key'
    			], RestController::HTTP_BAD_REQUEST);
    		}

    		$now = new DateTimeImmutable();
    		$data = ['id_karyawan' => $id];
    		$tgl = date('Y-m-d');
    		$jam = date('H:i:s');

    		// Check duplicat absen masuk di tanggal yang sama
    		$checkDateNow = [
    			'id_karyawan' => $id,
    			'tgl_absen' => $tgl,
    			'jam_datang !=' => null
    		];
    		$checkAbsen = $this->absen->show($checkDateNow,1);
    		if ($checkAbsen->num_rows() == 1) {
    			$this->response([
    				'status' => FALSE,
    				'title' => 'Failed to Absen Masuk!',
    				'message' => 'Hari ini '.$this->_days($tgl).', '.date('d F Y', strtotime($tgl)).' anda sudah melakukan absen'
    			], RestController::HTTP_BAD_REQUEST); 
    		}
    		// End Check duplicat absen masuk di tanggal yang sama

    		$rowRecord = $this->absen->show($data,1);

            if ($rowRecord->num_rows() > 0) {
                $check = $rowRecord->row();
                if ($check->desc_kerja == null) {
                    $this->response([
                        'status' => FALSE,
                        'title' => 'Failed to Create Absen Masuk!',
                        'message' => 'Harap masukan deskripsi pekerjaan hari '.$this->_days($check->tgl_absen).', '.date('d F Y', strtotime($check->tgl_absen)),
                    ], RestController::HTTP_BAD_REQUEST); 
                }
            }
            
			$data['tgl_absen'] = $tgl;
			$data['jam_datang'] = $jam;    			
			$data['create_at'] = date('Y-m-d H:i:s');
			$ins = $this->absen->insert($data);
			if ($ins) {
				$this->response([
					'status' => TRUE,
					'title' => 'Successful Created!',
					'message' => 'Absen was successful created on '.date('d F Y', strtotime($tgl)).'!'
				], RestController::HTTP_CREATED);
			}else{
				$this->response([
					'status' => FALSE,
					'title' => 'Error Created!',
					'message' => 'Absen was error created!'
				], RestController::HTTP_BAD_REQUEST);
			}
    	}else{
    		$this->response([
    			'status' => FALSE,
                'title' => 'Error 1!',
    			'message' => 'NIP was required',
                'data'=>$newData
    		], RestController::HTTP_BAD_REQUEST); 
    	}
    }

    public function absenpulang_post()
    {
        $newData = json_decode($this->input->raw_input_stream,true); 

    	if (@$newData['id'] && @$newData['employee'] && @$newData['desc']) {
    		$id = $newData['id'];
    		$idk = $newData['employee'];
    		$tgl = @$newData['tgl'] ? date('Y-m-d', strtotime($newData['tgl'])) : date('Y-m-d');
    		$desc = $newData['desc'];
    		$key = $newData['idkey'];
    		$jam = date('H:i:s');

    		if (!$this->_key_exists($key)){
    			$this->response([
    				'status' => FALSE,
    				'message' => 'Invalid API key'
    			], RestController::HTTP_BAD_REQUEST);
    		}
    		$data = [
    			'id' => $id,
    			'id_karyawan' => $idk,
    			'tgl_absen' => $tgl
    		];

    		$row = $this->absen->show($data);
    		// Check deskripsi pekerjaan sebelumnya
    		if ($row->num_rows() > 0) {
    			$check = $row->row();
    			if ($check->desc_kerja != null && $check->jam_pulang != null) {
    				$this->response([
    					'status' => FALSE,
    					'title' => 'Sudah melakukan absen pulang!',
    					'message' => 'sudah melakukan absen pulang pada hari '.$this->_days($tgl).', '.date('d F Y', strtotime($tgl)),
    				], RestController::HTTP_BAD_REQUEST); 
    			}else{
    				$dataUpdate = [
    					'jam_pulang' => $jam,
    					'desc_kerja' => $desc,
    					'update_at' => date('Y-m-d H:i:s')
    				];

    				$upd = $this->absen->update($data, $dataUpdate);
    				if ($upd) {
    					$this->response([
    						'status' => TRUE,
    						'title' => 'Successful Update Absen',
    						'message' => 'Absen was successful update on '.date('d F Y', strtotime($tgl)).'!'
    					], RestController::HTTP_CREATED);
    				}else{
    					$this->response([
    						'status' => FALSE,
    						'title' => 'Error Updated',
    						'message' => 'Absen was error Updated!'
    					], RestController::HTTP_BAD_REQUEST);
    				}
    			}
    		}else{
    			$this->response( [
    				'status' => FALSE,
    				'title' => 'Absensi not found',
    				'massage' => 'Periksa kembali absen anda tanggal '.date('d F Y', strtotime($tgl))
    			], RestController::HTTP_NOT_FOUND );
    		}
    	}else{
    		$this->response([
    			'status' => FALSE,
    			'message' => 'NIP was required'
    		], RestController::HTTP_BAD_REQUEST); 
    	}
    }

    private function _days($date){
    	$hari = date ("D", strtotime($date));

    	switch($hari){
    		case 'Sun':
    		$days = "Minggu";
    		break;

    		case 'Mon':			
    		$days = "Senin";
    		break;

    		case 'Tue':
    		$days = "Selasa";
    		break;

    		case 'Wed':
    		$days = "Rabu";
    		break;

    		case 'Thu':
    		$days = "Kamis";
    		break;

    		case 'Fri':
    		$days = "Jumat";
    		break;

    		case 'Sat':
    		$days = "Sabtu";
    		break;

    		default:
    		$days = "Tidak di ketahui";		
    		break;
    	}

    	return $days;
    }
}
/* End of file AbsenController.php */
/* Location: ./application/controllers/Api/AbsenController.php */