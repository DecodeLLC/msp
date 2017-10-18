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
	 * @var     bool
	 * @access  protected
	 */
	protected $useTLS = false;

	/**
	 * {@description}
	 *
	 * @var     bool
	 * @access  protected
	 */
	protected $supportESMTP = false;

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
	 * @return  void
	 */
	public function onTLS()
	{
		$this->useTLS = true;
	}

	/**
	 * {@description}
	 *
	 * @access  public
	 * @return  void
	 */
	public function offTLS()
	{
		$this->useTLS = false;
	}

	/**
	 * {@description}
	 *
	 * @access  public
	 * @return  bool
	 */
	public function usedTLS()
	{
		return !! $this->useTLS;
	}

	/**
	 * {@description}
	 *
	 * @access  public
	 * @return  bool
	 */
	public function supportedESMTP()
	{
		return !! $this->supportESMTP;
	}

	/**
	 * {@description}
	 *
	 * @param   mixed   $code
	 *
	 * @access  public
	 * @return  bool
	 */
	public function verifyResponseStatusCode($code)
	{
		return strncmp($this->getStreamContent(), $code, 3) === 0;
	}

	/**
	 * {@description}
	 *
	 * @access  public
	 * @return  bool
	 */
	public function greeting()
	{
		if ($this->sendCommand('EHLO {host}', ['host' => $this->getHostConnection()]))
		{
			if ($this->verifyResponseStatusCode(250))
			{
				$this->supportESMTP = true;

				return true;
			}
		}

		if ($this->sendCommand('HELO {host}', ['host' => $this->getHostConnection()]))
		{
			if ($this->verifyResponseStatusCode(250))
			{
				$this->supportESMTP = false;

				return true;
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
	 *
	 * @throws  \RuntimeException
	 */
	public function login($username, $password)
	{
		if (! $this->greeting())
		{
			// Сервер не принял приветствие
			throw new \RuntimeException('SMTP.LOGIN.ERROR.001');
		}

		if ($this->usedTLS())
		{
			if (! $this->supportedESMTP())
			{
				// Сервер не поддерживает расширение ESMTP включающий в себя поддержку TLS протокола.
				throw new \RuntimeException('SMTP.LOGIN.ERROR.002');
			}

			if (! $this->sendCommand('STARTTLS'))
			{
				// Не удалось передать команду [STARTTLS] на сервер.
				throw new \RuntimeException('SMTP.LOGIN.ERROR.003');
			}

			if (! $this->verifyResponseStatusCode(220))
			{
				// Сервер не поддерживает TLS протокол.
				throw new \RuntimeException('SMTP.LOGIN.ERROR.004');
			}

			if (! stream_socket_enable_crypto($this->getStream(), true, STREAM_CRYPTO_METHOD_TLS_CLIENT))
			{
				// Не удалось включить шифрование сокета для TLS протокола.
				throw new \RuntimeException('SMTP.LOGIN.ERROR.005');
			}

			if (! $this->greeting())
			{
				// Сервер не принял повторное приветствие.
				throw new \RuntimeException('SMTP.LOGIN.ERROR.006');
			}
		}

		if (! $this->sendCommand('AUTH LOGIN'))
		{
			// Не удалось передать команду [AUTH LOGIN] на сервер.
			throw new \RuntimeException('SMTP.LOGIN.ERROR.007');
		}

		if (! $this->verifyResponseStatusCode(334))
		{
			// Сервер не поддерживает тип авторизации [LOGIN].
			throw new \RuntimeException('SMTP.LOGIN.ERROR.008');
		}

		if (! ($this->sendCommand(base64_encode($username))))
		{
			// Не удалось передать имя учетной записи на сервер.
			throw new \RuntimeException('SMTP.LOGIN.ERROR.009');
		}

		if (! $this->verifyResponseStatusCode(334))
		{
			// Сервер не принял имя учетной записи.
			throw new \RuntimeException('SMTP.LOGIN.ERROR.010');
		}

		if (! $this->sendCommand(base64_encode($password)))
		{
			// Не удалось передать пароль от учетной записи на сервер.
			throw new \RuntimeException('SMTP.LOGIN.ERROR.011');
		}

		if (! $this->verifyResponseStatusCode(235))
		{
			// Сервер не принял пароль от учетной записи.
			throw new \RuntimeException('SMTP.LOGIN.ERROR.012');
		}

		return true;
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
