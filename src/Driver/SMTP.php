<?php

namespace DecodeLLC\MSP\Driver;

use DecodeLLC\MSP\Driver\Driver;

/**
 * SMTP
 */
class SMTP extends Driver
{

	/**
	 * {@description}
	 *
	 * @var     string
	 * @access  protected
	 */
	protected $socket;

	/**
	 * {@description}
	 *
	 * @param   string   $socket
	 *
	 * @access  public
	 * @return  void
	 */
	public function __construct($socket)
	{
		$this->socket = $socket;
	}

	/**
	 * {@description}
	 *
	 * @access  public
	 * @return  string
	 */
	public function getSocket()
	{
		return $this->socket;
	}

	/**
	 * {@description}
	 *
	 * @access  public
	 * @return  bool
	 */
	public function isSuccessfulConnection()
	{
		return false;
	}

	/**
	 * {@description}
	 *
	 * @access  public
	 * @return  bool
	 */
	public function isSuccessfulCommand()
	{
		return false;
	}
}
