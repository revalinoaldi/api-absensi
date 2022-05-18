<?php
defined('BASEPATH') OR exit('No direct script access allowed');
use chriskacerguis\RestServer\RestController;
use \Firebase\JWT\JWT;

class KaryawanController extends RestController {

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
    $origin = @$_SERVER['HTTP_ORIGIN'] ? $_SERVER['HTTP_ORIGIN'] : '*';
        header("Access-Control-Allow-Origin: ".$origin);
        header('Access-Control-Allow-Credentials: true');
		$this->methods['index_get']['limit'] = 500; // 500 requests per hour per user/key
        $this->methods['index_post']['limit'] = 100; // 100 requests per hour per user/key
        $this->methods['update_post']['limit'] = 100; // 100 requests per hour per user/key
        $this->methods['index_delete']['limit'] = 50; // 50 requests per hour per user/key

        if (!$this->_key_exists($this->input->request_headers()['token'])){
          $this->response([
            'status' => FALSE,
            'message' => 'Invalid API key'
          ], RestController::HTTP_BAD_REQUEST);
        }else{
          $this->key = $this->input->request_headers()['token'];
          $this->tokenkey = $this->input->request_headers()['tokenkey'];
          $this->_checkToken();
        }

        $this->load->model('MKaryawan','emp');
        $this->load->model('MToken','token');
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

      public function index_get($id='')
      {
        if (@$id) {
          $where = ['id_karyawan' => $id];
          $get = $this->emp->show($where);
          $data = $get->row();
        }else{
          $get = $this->emp->show();
          $data = $get->result();
        }
        if ($get->num_rows() > 0) {
          $this->response( [
           'status' => TRUE,
           'title' => 'Success get Employee',
           'data' => $data
         ], RestController::HTTP_OK );
        }else{
          $this->response( [
           'status' => FALSE,
           'title' => 'Employee not found',
           'date' => []
         ], RestController::HTTP_NOT_FOUND );
        }
      }

      public function index_delete($id)
      {
        if (@$id) {
          $id = ['id_karyawan' => $id];
          $get = $this->emp->show($id);
          $data = $get->row();
          if ($get->num_rows() == 1) {
           $del = $this->emp->delete($id);
           if ($del) {
            $this->response( [
             'status' => TRUE,
             'title' => 'Success delete one Employee',
             'massage' => 'Employee nip : '.$id['id_karyawan'].' was deleted!'
           ], RestController::HTTP_OK );
          }else{
            $this->response( [
             'status' => FALSE,
             'title' => "Employee can't deleted",
             'massage' => "Can't delete Employee"
           ], RestController::HTTP_BAD_REQUEST );
          }
        }else{
          $this->response( [
            'status' => FALSE,
            'title' => 'Employee not found',
            'massage' => "NIP Employee can't found"
          ], RestController::HTTP_NOT_FOUND );
        }
      }else{
        $this->response( [
         'status' => FALSE,
         'title' => 'NIP was required',
         'massage' => "NIP must be required"
       ], RestController::HTTP_BAD_REQUEST );
      }
    }

    private function _generate_token($payload)
    {
        $issuedAt   = new DateTimeImmutable();
        $expire     = $issuedAt->modify('+1 month')->getTimestamp();
        $forToken = [
            'iat'  => $issuedAt->getTimestamp(),
            'iss'  => 'api.absensi.com',              
            'nbf'  => $issuedAt->getTimestamp(),
            'exp'  => $expire
        ];

        $data = [
            'jwt' => JWT::encode(array_merge($payload,$forToken),$payload['key'],'HS256'),
            'expire' => $expire
        ];
        return $data;
    }

    private function _regenerate_get($payload)
    {
        if (@$this->_key_exists($this->key) && @$this->tokenkey){
            $this->_checkToken();

            $check = array(
                'id_karyawan' => $payload['id'], 
                'email' => $payload['email'],
                'secretkey' => $payload['key']
            );

            $data = $this->emp->showfield($check);
            if ($data->num_rows() == 1) {
                $row = $data->row();
                $newpayload = [
                    'id' => $row->id_karyawan,
                    'nama' => $row->nama_karyawan,
                    'email' => $row->email,
                    'level' => $row->level,
                    'key' => $row->secretkey
                ];
                $jwt = $this->_generate_token($newpayload);
                $arr = [
                    'token' => $jwt['jwt'],
                    'update_at' => date('Y-m-d H:i:s'),
                    'expire_at' => date('Y-m-d H:i:s', $jwt['expire'])
                ];
                $id = ['id_karyawan' => $row->id_karyawan];
                $this->token->update($id,$arr);

                $newpayload['token'] = $jwt['jwt'];
                $newpayload['expire'] = $jwt['expire'];

                return $newpayload;
            }
        }else{
            $this->response([
                'status' => FALSE,
                'message' => 'Token Key invalid'
            ], RestController::HTTP_BAD_REQUEST);  
        }
    }

    public function index_post($id='')
    {
      $jsonArray = json_decode($this->input->raw_input_stream,true); 
      $postReal = $this->form_validation->set_data($jsonArray);

     $this->form_validation->set_rules('id', 'NIP', 'trim|required',[
      'required' => '%s Required',
    ]);
     $this->form_validation->set_rules('nama', 'Nama Karyawan', 'trim|required',[
      'required' => '%s Required'
    ]);
     $this->form_validation->set_rules('email', 'Email', 'trim|required|min_length[5]|valid_email',[
      'required' => '%s Required',
      'valid_email' => '%s must be Valid',
      'min_length' => '%s must have at least 5 characters.'
    ]);

     if ($this->form_validation->run() == FALSE) {
      $this->response([
       'status' => FALSE,
       'title' => 'Invalid input requried',
       'message' => validation_errors()
     ], RestController::HTTP_BAD_REQUEST);
    } else {
      $arr = [
       'nama_karyawan' => $jsonArray['nama'],
       'email' => $jsonArray['email'],
     ];
      if (@$jsonArray['pass'] != null) {
        $password = password_hash($jsonArray['pass'], PASSWORD_BCRYPT);
        $arr['password'] = $this->encryption->encrypt($password);
      }
     if (!$id) {
      $arr['id_karyawan'] = $jsonArray['id'];
      $arr['create_at'] = date('Y-m-d H:i:s');
      // Check email avail
      $emailcek = ['email' => $jsonArray['email']];
      $rw = $this->emp->show($emailcek);
      if ($rw->num_rows() > 0) {
        $this->response([
          'status' => FALSE,
          'title' => 'Error Created',
          'message' => 'Email Karyawan already exist!'
        ], RestController::HTTP_BAD_REQUEST);
        // End check
      }else{
        $ins = $this->emp->insert($arr);
        if ($ins) {
          $key = $this->_generate_key();
          $tokenInsert = ['id_karyawan' => $arr['id_karyawan']];
          $insKey = $this->_insert_key($key,$tokenInsert);
          $this->response([
            'status' => TRUE,
            'title' => 'Successful Created',
            'message' => 'Karyawan was successful created!'
          ], RestController::HTTP_CREATED);
        }else{
         $this->response([
          'status' => FALSE,
          'title' => 'Error Created',
          'message' => 'Karyawan was error created!'
        ], RestController::HTTP_BAD_REQUEST);
       }
     }
   }else{
    $idf = ['id_karyawan' => $id];
    $arr['update_at'] = date('Y-m-d H:i:s');
    $upd = $this->emp->update($idf,$arr);
    if ($upd) {
      $check = array(
        'id' => $id, 
        'email' => $arr['email'],
        'key' => $this->key
      );

      $newData = $this->_regenerate_get($check);

      $this->response([
        'status' => TRUE,
        'title' => 'Successful Update',
        'message' => 'Karyawan nip : '.$idf['id_karyawan'].' was successful update!',
        'data' => $newData
      ], RestController::HTTP_OK);
    }else{
      $this->response([
        'status' => FALSE,
        'title' => 'Error Update',
        'message' => 'Karyawan was error update!'
      ], RestController::HTTP_BAD_REQUEST);
    }
  }
}
}

private function _generate_key()
{
  do
  {
            // Generate a random salt
    $salt = base_convert(bin2hex($this->security->get_random_bytes(64)), 16, 36);

            // If an error occurred, then fall back to the previous method
    if ($salt === FALSE)
    {
      $salt = hash('sha256', time() . mt_rand());
    }

    $new_key = substr($salt, 0, config_item('rest_key_length'));
  }
  while ($this->_key_exists($new_key));

  return $new_key;
}

private function _get_key($key)
{
  return $this->rest->db
  ->where(config_item('rest_key_column'), $key)
  ->get(config_item('rest_keys_table'))
  ->row();
}

private function _key_exists($key)
{
  return $this->rest->db
  ->where(config_item('rest_key_column'), $key)
  ->count_all_results(config_item('rest_keys_table')) > 0;
}

private function _insert_key($key, $data)
{
  $data[config_item('rest_key_column')] = $key;
  $data['create_at'] = date('Y-m-d H:i:s');

  return $this->rest->db
  ->set($data)
  ->insert(config_item('rest_keys_table'));
}

private function _update_key($key, $data)
{
  return $this->rest->db
  ->where(config_item('rest_key_column'), $key)
  ->update(config_item('rest_keys_table'), $data);
}

private function _delete_key($key)
{
  return $this->rest->db
  ->where(config_item('rest_key_column'), $key)
  ->delete(config_item('rest_keys_table'));
}
}

/* End of file KaryawanController.php */
/* Location: ./application/controllers/Api/KaryawanController.php */