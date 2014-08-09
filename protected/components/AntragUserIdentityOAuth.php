<?php

class AntragUserIdentityOAuth extends CBaseUserIdentity
{
	/**
	 * @var LightOpenID
	 */
	private $_loid;

	/**
	 * @param LightOpenID $loid
	 */
	public function __construct($loid)
	{
		$this->_loid = $loid;
	}


	/**
	 * @return Bool
	 */
	public function authenticate()
	{
		return $this->_loid->validate();
	}


	/**
	 * @return string
	 */
	public function getId()
	{
		return "openid:" . $this->_loid->identity;
	}

	/**
	 * @return string
	 */
	public function getName()
	{
		$atts = $this->_loid->getAttributes();
		if (isset($atts["namePerson/friendly"])) return $atts["namePerson/friendly"];
		if (isset($atts["contact/email"])) return $atts["contact/email"];
		return $this->_loid->identity;
	}

	/**
	 * @return string
	 */
	public function getEmail()
	{
		$atts = $this->_loid->getAttributes();
		if (isset($atts["contact/email"])) return $atts["contact/email"];
		return "";
	}

}