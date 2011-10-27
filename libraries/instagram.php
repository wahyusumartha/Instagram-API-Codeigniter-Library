<?php
/*
* Instagram OAuth 2.0 Authentication
*/
class Instagram {
	/*
	* URL Authorize for Instagram Authentication
	*/
	private $_url_authorized = 'https://api.instagram.com/oauth/authorize/';
	private $_request_access_token_url = 'https://api.instagram.com/oauth/access_token';
	private $_api_url = 'https://api.instagram.com/v1';
	
	/**
	* @var string access token
	*/
	private $_access_token;
	
	/**
	* @var array params
	*	additional request parameters to be used for remote request
	*/
	public $params = array();
	
	/**
	* @var string 
	*	Instagram Application Registered Client ID
	*/
	public $client_id = '';
	
	/** 
	* @var string 
	*	Instagram Application Registered Client Secret
	*/
	public $client_secret = '';
	
	/**
	* @var array
	*	Instagram Application Scope 
	*/
	private $_scope = array();
	
	/**
	* @var string url 
	*	Application Redirect URI (callback)
	*/
	public $redirect_uri = '';
	
	private $_instagram_connection;
	
	/**
	* Set 
	*	@param string client id
	*		App Client Id 
	*	@param string client secret 
	*		App Client Secret 
	*	@param string redirect uri 
	*		App Redirect URI (Callback)
	*/
	public function __construct(){
		$ci =& get_instance();
		
		$ci->load->library('config');
		$ci->config->load('instagram');
		
		$this->client_id = $ci->config->item('client_id');
		$this->client_secret = $ci->config->item('client_secret');
		$this->redirect_uri = $ci->config->item('redirect_uri');
		
		$this->_instagram_connection = new InstagramConnection();
	}
	
	/**
	* Go to Instagram Authorization Page
	* @deprecated
	*/
	public function login(){
		
		$ci =& get_instance();
		$ci->load->helper('url');
		$access_token = $this->get_access_token();
		if ($access_token)
		{
			redirect($this->redirect_uri);
		}
		else
		{
			redirect($this->url_authorize($this->_scope));
		}
	}
	
	/**
	* Login Checked
	*/
	public function is_login()
	{
		$access_token = $this->get_access_token();
		if ($access_token)
		{
			return TRUE;
		}
		else 
		{
			return FALSE;
		}
	}
	
	/**
	* Logout from Instagram Connection
	*/
	function logout(){
		$ci =& get_instance();
		$ci->load->library('session');
		
		$ci->session->unset_userdata('instagram_access_token');
		$ci->session->sess_destroy();
	}
	
	/**
	* Return the authorization url 
	* @return string
	*/
	public function url_authorize(){
		$url = '';
		$url .= $this->_url_authorized;
		
		if ($this->client_id)
		{
			$url .= '?';
			$url .= 'client_id='.$this->client_id.'&';
		}
		
		if ($this->redirect_uri)
		{
			$url .= '&';
			$url .= 'redirect_uri='.$this->redirect_uri;
		}
				
		$url .= '&scope=';
		
		$i = 0;
		
		foreach($this->_scope as $value){
			$i++;
			if ($i < count($this->_scope)) 
			{
				$url .= $value . '+';
			}
			else
			{
				$url .= $value;
			}
		}
		
		$url .= '&';
		$url .= 'response_type=code';
		
		return $url;
	}
	
	/**
	*	Request Access Token
	*/
	public function request_access_token(){
		$ci =& get_instance();
		$ci->load->helper('url');
		$code = $ci->input->get('code', TRUE);
		
		if ($code)
		{
			$params = array(
				'client_id' => $this->client_id,
				'client_secret' => $this->client_secret,
				'grant_type' => 'authorization_code',
				'redirect_uri' => urlencode($this->redirect_uri),
				'code' => $code
			);
			
			$response = $this->_instagram_connection->post($this->_request_access_token_url, $params);
			$this->_set_access_token($response);
		}
		else
		{
			$scope = array('comments', 'relationships', 'likes');
			redirect($this->url_authorize($scope));
		}
	}
	
	/**
	* Set Access Token that taken from Request Access Token Response 
	* @param $response 
	*	Request Access Token Response Data
	*/
	private function _set_access_token($response){
		$response_data = json_decode($response);
		if (isset($response_data->access_token))
		{
			$access_token = $response_data->access_token;
			$this->_access_token = $access_token;
			$ci =& get_instance();
			$ci->load->library('session');
			$ci->session->set_userdata('instagram_access_token', $this->_access_token);
		}else{
			$error_exception = new Instagram_Exception($response_data);
			echo $error_exception->__toString();
		}
	}
	
	/**
	*	Get Access Token
	* @return 
	*	Access Token
	*/
	public function get_access_token(){
		$ci =& get_instance();
		$ci->load->library('session');
		
		return $ci->session->userdata('instagram_access_token'); 
	}
	
	/**
	* API Call 
	* @param $endpoint string 
	*	API Endpoint Ex : /users/{user-id}
	* @param $method string 
	* 	API Method Request
	*/
	public function call($endpoint, $method = 'GET'){
		switch ($method)
		{
			case 'GET' :
				$response  = $this->_instagram_connection->get($this->_api_url, $endpoint, $this->get_access_token());
				$response = json_decode($response);
				return $response;
				break;
			case 'POST':
				break;
		}
	}
	
	/**
	* Scope Authentication
	* @param array 
	*	Scope (basic, comments, relationships, likes)
	*/
	public function set_scope($scope = array()){
		$this->_scope = $scope;
	}
	
	public function get_scope(){
		return $this->_scope;
	}
}

class InstagramConnection
{	
	private $_ch = NULL; 
	private $_properties = array();
	
	function __construct(){
		$this->_properties = array(
			'code' => CURLINFO_HTTP_CODE,
			'time' => CURLINFO_TOTAL_TIME, 
			'length' => CURLINFO_CONTENT_LENGTH_DOWNLOAD,
			'type' => CURLINFO_CONTENT_TYPE
		);
	}
	
	private function _init_connection($url){
		$this->_ch = curl_init($url);
		curl_setopt($this->_ch, CURLOPT_RETURNTRANSFER, TRUE);
	}
	
	public function get($url, $endpoint, $access_token){
		if (stristr($endpoint, '?'))
		{
			$request_url = $url . $endpoint . '&access_token='.$access_token;
		}
		else
		{
			$request_url = $url . $endpoint . '?access_token='.$access_token;
		}

		$this->_init_connection($request_url);
		curl_setopt($this->_ch, CURLOPT_RETURNTRANSFER, 1);
		
		$response = trim(curl_exec($this->_ch));
		
		return $response;
	}
	
	public function post($url, $params = array()){
		$post = '';
		
		foreach ($params as $k => $v)
		{
			$post .= "{$k}={$v}&";
		}
		
		$post = substr($post, 0, -1);
		
		$this->_init_connection($url);
		curl_setopt($this->_ch, CURLOPT_POST, 1);
		curl_setopt($this->_ch, CURLOPT_POSTFIELDS, $post);
		
		$response = curl_exec($this->_ch);
				
		return $response;
	}
}

class Instagram_Exception extends Exception {
	
	/** 
	* The Results from API Server
	*/
	protected $result;
	
	/**
	* Make a new API Exception with given result 
	*
	* @param $result 
	*	The result from the API Server
	*/
	public function __construct($result){
		$this->result = $result;
		
		$code = isset($result->code) ? $result->code : 0;
		$message = '';
		if(isset($result->error_type))
		{
			$message .= 'Error Type : '.$result->error_type.'<br/>';
		}
		
		if(isset($result->error_message))
		{
			$message .= 'Error Message : '.$result->error_message. '<br/>';
		}
		else
		{
			$message = 'Unknown Error';
		}
		
		parent::__construct($message, $code);
	}
	
	/**
	* In order to debugging easier 
	*
	* @returns 
	*	The string representation of error
	*/
	public function __toString()
	{
		$str = '';
		if ($this->code != 0)
		{
			$str .= 'Error Code : '.$this->code . '<br/>';
		}
		return $str . $this->message;
	}
}
/**
* End of instagram.php
*/