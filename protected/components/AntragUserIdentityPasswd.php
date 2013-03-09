<?php

class AntragUserIdentityPasswd extends CBaseUserIdentity
{
	/** @var string */
	private $username;

	/**
	 * @param string $username
	 */
	public function __construct($username)
	{
		$this->username = $username;
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
		return "openid:https://" . $this->username . ".netzbegruener.in/";
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