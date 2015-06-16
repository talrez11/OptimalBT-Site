<?php

/**
 * CRM Sumbmission Module
 *
 * Copyright © 2015 Way2CU. All Rights Reserved.
 * Author: Mladen Mijatov, Eyal Gershon
 */
use Core\Module;
use Core\Events;


class optimalbt extends Module {
	private static $_instance;

	private $api_domain = 'clients.frontask.co.il';
	private $api_path = '/11848/Pages/Utilities/WebToLead.aspx?type=xml';

	/**
	 * Constructor
	 */
	protected function __construct() {
		parent::__construct(__FILE__);

		Events::connect('contact_form', 'email-sent', 'handle_send', $this);
	}

	/**
	 * Public function that creates a single instance
	 */
	public static function getInstance() {
		if (!isset(self::$_instance))
			self::$_instance = new self();

		return self::$_instance;
	}

	/**
	 * Transfers control to module functions
	 *
	 * @param array $params
	 * @param array $children
	 */
	public function transferControl($params = array(), $children = array()) {
	}

	/**
	 * Event triggered upon module initialization
	 */
	public function onInit() {
	}

	/**
	 * Event triggered upon module deinitialization
	 */
	public function onDisable() {
	}

	/**
	 * Finalize message and send it to specified addresses.
	 *
	 * Note: Before sending, you *must* check if contact_form
	 * function detectBots returns false.
	 *
	 * @return boolean
	 */
	public function handle_send($mailer, $recipient, $subject, $data) {
		// prepare data
		$find = array();
		$replace = array();
		foreach ($data as $key => $value) {
			$find[] = '%'.$key.'%';
			$replace[] = $value;
		}

		// prepare content
		$content = file_get_contents($this->path.'data/api.xml');
		$content = str_ireplace($find, $replace, $content);

		// make the call
		$header = "POST {$this->api_path} HTTP/1.1\n";
		$header .= "User-Agent: Caracal\n";
		$header .= "Content-Type: application/x-www-form-urlencoded\n";
		$header .= "Content-Length: " . strlen($content) . "\n";
		$header .= "Host: ".$this->api_domain."\n";
		$header .= "Connection: close\n\n";

		$socket = fsockopen('ssl://'.$this->api_domain, 443, $error_number, $error_string, 5);
		
		// store request
		file_put_contents(__FILE__.'.request.txt', $header.$content);

		if ($socket) {
			// send and receive data
			fputs($socket, $header.$content);
			$response = stream_get_contents($socket, 1024);
			// store response
		 	file_put_contents(__FILE__.'.response.txt', $response);
			trigger_error($response, E_USER_NOTICE);
			fclose($socket);
		}
	}
}

