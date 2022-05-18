<?php

defined('BASEPATH') OR exit('No direct script access allowed');

use chriskacerguis\RestServer\RestController;
use \Firebase\JWT\JWT;

class LoginControllers extends RestController {
    public $key = '';
    public $tokenkey = '';
    function __construct()
    {
        // Construct the parent class
        parent::__construct();
        date_default_timezone_set('Asia/Jakarta');

        $origin = @$_SERVER['HTTP_ORIGIN'] ? $_SERVER['HTTP_ORIGIN'] : '*';
        header("Access-Control-Allow-Origin: ".$origin);
        header('Access-Control-Allow-Credentials: true');
        
        $this->methods['index_post']['limit'] = 100; // 100 requests per hour per user/key

        if (@$this->_key_exists($this->input->request_headers()['token']) && @$this->input->request_headers()['tokenkey']){
            $this->key = $this->input->request_headers()['token'];
            $this->tokenkey = $this->input->request_headers()['tokenkey'];
            $this->_checkToken();
        }


        $this->load->model('MKaryawan','emp');
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

    public function regenerate_get()
    {
        if (@$this->_key_exists($this->key) && @$this->tokenkey){
            $this->_checkToken();
            $payload = JWT::decode($this->tokenkey, $this->key, array('HS256'));

            $check = array(
                'id_karyawan' => $payload->id, 
                'email' => $payload->email,
                'secretkey' => $payload->key
            );

            $data = $this->emp->showfield($check);
            if ($data->num_rows() == 1) {
                $row = $data->row();
                $newpayload = [
                    'id' => $payload->id,
                    'nama' => $payload->nama,
                    'email' => $payload->email,
                    'level' => $payload->level,
                    'key' => $payload->key
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

                $this->response([
                    'status' => TRUE,
                    'title' => 'Success Regenerate Token Key',
                    'data' => $newpayload,
                    // 'header' => $this->input->request_headers()
                ], RestController::HTTP_OK);
            }
        }else{
            $this->response([
                'status' => FALSE,
                'message' => 'Token Key invalid'
            ], RestController::HTTP_BAD_REQUEST);  
        }
    }

    public function index_post()
    {   
        $jsonArray = json_decode($this->input->raw_input_stream,true); 
        $postReal = $this->form_validation->set_data($jsonArray);
        // $this->input->raw_input_stream;
        $this->form_validation->set_rules('email', 'Email', 'trim|required|min_length[5]|valid_email',[
            'required' => '%s Required',
            'valid_email' => '%s must be Valid',
            'min_length' => '%s must have at least 5 characters.'
        ]);
        $this->form_validation->set_rules('pass', 'Password', 'trim|required|min_length[4]',[
            'required' => '%s Required',
            'min_length' => '%s must have at least 4 characters.'
        ]);

        if ($this->form_validation->run() == FALSE) {
            $this->response([
                'status' => FALSE,
                'title' => 'Invalid input requried',
                'error' => [
                    form_error('email'),
                    form_error('pass')
                ],
            ], RestController::HTTP_BAD_REQUEST);
        } else {
            $email = htmlentities($jsonArray['email']);
            $pass = htmlentities(htmlspecialchars($jsonArray['pass']));
            $check = array('email' => $email );
            
            $row = $this->emp->showfield($check);
            if ($row->num_rows() < 1) {
                $this->response([
                    'status' => FALSE,
                    'title' => 'Email or Password incorrect!',
                    'message' => 'Check your email or password'
                ], RestController::HTTP_NOT_FOUND);
            }else{
                $show = $row->row();

                if (password_verify($pass, $this->encryption->decrypt($show->password))) {
                    $payload = [
                        'id' => $show->id_karyawan,
                        'nama' => $show->nama_karyawan,
                        'email' => $show->email,
                        'level' => $show->level,
                        'key' => $show->secretkey
                    ];
                    
                    $jwt = $this->_generate_token($payload);

                    $arr = [
                        'token' => $jwt['jwt'],
                        'update_at' => date('Y-m-d H:i:s'),
                        'expire_at' => date('Y-m-d H:i:s', $jwt['expire'])
                    ];
                    $id = ['id_karyawan' => $show->id_karyawan];
                    $this->token->update($id,$arr);

                    $payload['token'] = $jwt['jwt'];
                    $payload['expire'] = $jwt['expire'];
                    
                    $this->response([
                        'status' => TRUE,
                        'title' => 'Success get Employee',
                        'data' => $payload
                    ], RestController::HTTP_OK);
                }else{
                    $this->response( [
                        'status' => FALSE,
                        'title' => 'Email or Password incorrect!',
                        'date' => []
                    ], $this->HTTP_NOT_FOUND );
                }
            }
        }
    }
}

/* End of file LoginControllers.php */
/* Location: ./application/controllers/Api/LoginControllers.php */