<?php

class AntragUserIdentityPasswd extends CBaseUserIdentity
{
	/** @var string */
	private $username;
	private $auth;

	/**
	 * @param string $username
	 * @param string $auth
	 */
	public function __construct($username, $auth)
	{
		$this->username = $username;
		$this->auth = $auth;
	}


	/**
	 * @return Bool
	 */
	public function authenticate()
	{
		return false;
	}


	/**
	 * @return string
	 */
	public function getId()
	{
		return $this->auth;
	}

	/**
	 * @return string
	 */
	public function getName()
	{
		return $this->username;
	}

	/**
	 * @return string
	 */
	public function getEmail()
	{
		return "";
	}


}