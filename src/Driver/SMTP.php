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
	public function greeting(& $esmtp = null)
	{
		if ($this->sendCommand('EHLO {host}', ['host' => $this->getHostConnection()]))
		{
			if (strncmp($this->getStreamContent(), '250', 3) === 0)
			{
				$esmtp = true;

				return true;
			}
		}

		if ($this->sendCommand('HELO {host}', ['host' => $this->getHostConnection()]))
		{
			if (strncmp($this->getStreamContent(), '250', 3) === 0)
			{
				$esmtp = false;

				return true;
			}
		}

		return false;
	}

	/**
	 * {@description}
	 *
	 * @access  public
	 * @return  bool
	 */
	public function starttls()
	{
		if ($this->sendCommand('STARTTLS'))
		{
			if (strncmp($this->getStreamContent(), '220', 3) === 0)
			{
				if (stream_socket_enable_crypto($this->getStream(), true, STREAM_CRYPTO_METHOD_TLS_CLIENT))
				{
					return true;
				}
			}
		}

		return false;
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
		if ($this->greeting($esmtp))
		{
			if ($this->isSecureConnection() or $esmtp)
			{
				if ($this->isSecureConnection() or $this->starttls())
				{
					if ($this->isSecureConnection() or $this->greeting())
					{
						if ($this->sendCommand('AUTH LOGIN'))
						{
							if (strncmp($this->getStreamContent(), '334', 3) === 0)
							{
								if ($this->sendCommand(base64_encode($username)))
								{
									if (strncmp($this->getStreamContent(), '334', 3) === 0)
									{
										if ($this->sendCommand(base64_encode($password)))
										{
											if (strncmp($this->getStreamContent(), '235', 3) === 0)
											{
												return true;
											}
										}
									}
								}
							}
						}
					}
				}
			}
		}

		return false;
	}

	/**
	 * {@description}
	 *
	 * @access  public
	 * @return  bool
	 */
	public function isSuccessfulConnection()
	{
		if (strncmp($this->readStream(), '220', 3) === 0)
		{
			return true;
		}

		return false;
	}

	/**
	 * {@description}
	 *
	 * В отличии от IMAP и POP3 протоколов, SMTP более специфичен,
	 * так как на многие команды у него разные возвращаемые статусы.
	 *
	 * @access  public
	 * @return  bool
	 */
	public function isSuccessfulCommand()
	{
		return true;
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
