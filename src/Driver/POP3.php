<?php

namespace DecodeLLC\MSP\Driver;

use DecodeLLC\MSP\Driver\Driver;
use PhpMimeMailParser\Parser;

/**
 * POP3
 */
class POP3 extends Driver
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
	 * @param   string   $username
	 * @param   string   $password
	 *
	 * @access  public
	 * @return  bool
	 */
	public function login($username, $password)
	{
		if ($this->sendCommand('USER {u}', ['u' => $username]))
		{
			if ($this->sendCommand('PASS {p}', ['p' => $password]))
			{
				return true;
			}
		}

		return false;
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
		if (strpos($this->readStream(), '+') === 0)
		{
			return true;
		}

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
		if (strpos($this->getStreamContent(), '+') === 0)
		{
			return true;
		}

		return false;
	}

	/**
	 * {@description}
	 *
	 * @access  protected
	 * @return  void
	 */
	protected function beforeDisconnect()
	{
		$this->sendCommand('QUIT');
	}
}
