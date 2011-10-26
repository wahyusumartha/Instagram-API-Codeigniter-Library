<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Welcome extends CI_Controller {

	
	public function __construct(){
		parent::__construct();
		$this->load->library('instagram');
		$this->load->helper('url');
	}
	
	
	function login(){
		/**
		* Check User has been login or not
		*/
		if ($this->instagram->is_login() === TRUE)
		{
			print_r($this->instagram->call('/users/self'));
		}
		else
		{
			$scope = array('comments', 'relationships', 'likes');
			$this->instagram->login($scope);
		}
	}
	
	function users_feed(){
		echo '<pre>';
		print_r($this->instagram->call('/users/self/feed'));
		echo '</pre>';
	}
	
	function logout(){
		$this->instagram->logout();
	}
	
	
	/**
	* Callback URL 
	*/
	public function callback(){
		if ($this->instagram->is_login() === TRUE)
		{
			print_r($this->instagram->call('/users/self'));
		}
		else
		{
			$this->instagram->request_access_token();
			redirect('welcome/users_feed');
		}
	}
}

/* End of file welcome.php */
/* Location: ./application/controllers/welcome.php */