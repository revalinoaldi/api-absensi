<?php
defined('BASEPATH') OR exit('No direct script access allowed');
use chriskacerguis\RestServer\RestController;
class Key extends RestController {

	public function __construct()
	{
		parent::__construct();
		date_default_timezone_set('Asia/Jakarta');
		$this->methods['index_get']['limit'] = 500; // 500 requests per hour per user/key
        $this->methods['index_post']['limit'] = 100; // 100 requests per hour per user/key
        $this->methods['update_post']['limit'] = 100; // 100 requests per hour per user/key
        $this->methods['index_delete']['limit'] = 50; // 50 requests per hour per user/key

        $this->load->model('MToken','token');
    }
    
	public function index_put()
    {
        // Build a new key
        $key = $this->_generate_key();

        // If no key level provided, provide a generic key
        $level = $this->put('level') ? $this->put('level') : 1;
        $ignore_limits = ctype_digit($this->put('ignore_limits')) ? (int) $this->put('ignore_limits') : 1;

        // Insert the new key
        if ($this->_insert_key($key, ['level' => $level, 'ignore_limits' => $ignore_limits]))
        {
            $this->response([
                'status' => TRUE,
                'key' => $key
            ], RestController::HTTP_CREATED); // CREATED (201) being the HTTP response code
        }
        else
        {
            $this->response([
                'status' => FALSE,
                'message' => 'Could not save the key'
            ], RestController::HTTP_INTERNAL_SERVER_ERROR); // INTERNAL_SERVER_ERROR (500) being the HTTP response code
        }
    }

    /**
     * Remove a key from the database to stop it working
     *
     * @access public
     * @return void
     */
    public function index_delete()
    {
        $key = $this->delete('key');

        // Does this key exist?
        if (!$this->_key_exists($key))
        {
            // It doesn't appear the key exists
            $this->response([
                'status' => FALSE,
                'message' => 'Invalid API key'
            ], RestController::HTTP_BAD_REQUEST); // BAD_REQUEST (400) being the HTTP response code
        }

        // Destroy it
        $this->_delete_key($key);

        // Respond that the key was destroyed
        $this->response([
            'status' => TRUE,
            'message' => 'API key was deleted'
            ], RestController::HTTP_NO_CONTENT); // NO_CONTENT (204) being the HTTP response code
    }

    /**
     * Change the level
     *
     * @access public
     * @return void
     */
    public function level_post()
    {
        $key = $this->post('key');
        $new_level = $this->post('level');

        // Does this key exist?
        if (!$this->_key_exists($key))
        {
            // It doesn't appear the key exists
            $this->response([
                'status' => FALSE,
                'message' => 'Invalid API key'
            ], RestController::HTTP_BAD_REQUEST); // BAD_REQUEST (400) being the HTTP response code
        }

        // Update the key level
        if ($this->_update_key($key, ['level' => $new_level]))
        {
            $this->response([
                'status' => TRUE,
                'message' => 'API key was updated'
            ], RestController::HTTP_OK); // OK (200) being the HTTP response code
        }
        else
        {
            $this->response([
                'status' => FALSE,
                'message' => 'Could not update the key level'
            ], RestController::HTTP_INTERNAL_SERVER_ERROR); // INTERNAL_SERVER_ERROR (500) being the HTTP response code
        }
    }

    /**
     * Suspend a key
     *
     * @access public
     * @return void
     */
    public function suspend_post()
    {
        $key = $this->post('key');

        // Does this key exist?
        if (!$this->_key_exists($key))
        {
            // It doesn't appear the key exists
            $this->response([
                'status' => FALSE,
                'message' => 'Invalid API key'
            ], RestController::HTTP_BAD_REQUEST); // BAD_REQUEST (400) being the HTTP response code
        }

        // Update the key level
        if ($this->_update_key($key, ['level' => 0]))
        {
            $this->response([
                'status' => TRUE,
                'message' => 'Key was suspended'
            ], RestController::HTTP_OK); // OK (200) being the HTTP response code
        }
        else
        {
            $this->response([
                'status' => FALSE,
                'message' => 'Could not suspend the user'
            ], RestController::HTTP_INTERNAL_SERVER_ERROR); // INTERNAL_SERVER_ERROR (500) being the HTTP response code
        }
    }

    /**
     * Regenerate a key
     *
     * @access public
     * @return void
     */
    public function regenerate_post()
    {
        $old_key = $this->post('key');
        $key_details = $this->_get_key($old_key);

        // Does this key exist?
        if (!$key_details)
        {
            // It doesn't appear the key exists
            $this->response([
                'status' => FALSE,
                'message' => 'Invalid API key'
            ], RestController::HTTP_BAD_REQUEST); // BAD_REQUEST (400) being the HTTP response code
        }

        // Build a new key
        $new_key = $this->_generate_key();

        // Insert the new key
        if ($this->_insert_key($new_key, ['level' => $key_details->level, 'ignore_limits' => $key_details->ignore_limits]))
        {
            // Suspend old key
            $this->_update_key($old_key, ['level' => 0]);

            $this->response([
                'status' => TRUE,
                'key' => $new_key
            ], RestController::HTTP_CREATED); // CREATED (201) being the HTTP response code
        }
        else
        {
            $this->response([
                'status' => FALSE,
                'message' => 'Could not save the key'
            ], RestController::HTTP_INTERNAL_SERVER_ERROR); // INTERNAL_SERVER_ERROR (500) being the HTTP response code
        }
    }

	
}

/* End of file Key.php */
/* Location: ./application/controllers/Api/Key.php */