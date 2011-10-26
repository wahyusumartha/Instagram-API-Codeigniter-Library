<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Welcome extends CI_Controller {

	/**
	 * Index Page for this controller.
	 *
	 * Maps to the following URL
	 * 		http://example.com/index.php/welcome
	 *	- or -  
	 * 		http://example.com/index.php/welcome/index
	 *	- or -
	 * Since this controller is set as the default controller in 
	 * config/routes.php, it's displayed at http://example.com/
	 *
	 * So any other public methods not prefixed with an underscore will
	 * map to /index.php/welcome/<method_name>
	 * @see http://codeigniter.com/user_guide/general/urls.html
	 */
	
	public function __construct(){
		parent::__construct();
		$this->load->library('instagram');
		$this->load->helper('url');
	}
	
	
	
	function login(){
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