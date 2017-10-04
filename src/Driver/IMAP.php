<?php

namespace DecodeLLC\MSP\Driver;

use DecodeLLC\MSP\Driver\Driver;
use PhpMimeMailParser\Parser;

/**
 * IMAP
 */
class IMAP extends Driver
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
		$context['username'] = $username;
		$context['password'] = $password;

		if ($this->sendCommand('{tag} LOGIN {username} {password}', $context))
		{
			return true;
		}

		return false;
	}

	/**
	 * {@description}
	 *
	 * @param   string   $folder
	 *
	 * @access  public
	 * @return  bool
	 */
	public function select($folder)
	{
		$context = ['folder' => $folder];

		if ($this->sendCommand('{tag} SELECT {folder}', $context))
		{
			return true;
		}

		return false;
	}

	/**
	 * {@description}
	 *
	 * @param   \DateTime   $since
	 *
	 * @access  public
	 * @return  mixed
	 */
	public function search(\DateTime $since)
	{
		$context = ['since' => $since->format('j-M-Y')];

		if ($this->sendCommand('{tag} UID SEARCH SINCE {since}', $context))
		{
			$regularExpression = '/^\052\040SEARCH\040(?<uids>[\d\040]+)$/i';

			if (preg_match($regularExpression, $this->getStreamContentWithoutLastRow(), $match))
			{
				return explode(' ', trim($match['uids']));
			}

			return null;
		}

		return false;
	}

	/**
	 * {@description}
	 *
	 * @param   int   $uid
	 *
	 * @access  public
	 * @return  mixed
	 */
	public function fetch($uid)
	{
		$context = ['uid' => $uid];

		if ($this->sendCommand('{tag} UID FETCH {uid} (BODY[])', $context))
		{
			$rows = $this->getStreamContentByRows();

			/**
			 * Содержит примерно следующее:
			 *
			 * +----------------------------------------------+
			 * |* <number> FETCH (UID <uid> BODY[] {<length>} |
			 * +----------------------------------------------+
			 *
			 * ... обрати внимание, на открывающуюся скобку.
			 */
			array_shift($rows);

			/**
			 * Содержит закрывающую скобку (см. выше)
			 */
			array_pop($rows);

			/**
			 * Содержит статус команды
			 */
			array_pop($rows);

			$parser = new Parser();

			$parser->setText(implode(self::EOL, $rows));

			return $parser;
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
		if (strpos($this->readStream(), '* OK') === 0)
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
		$signature = sprintf('%s OK', $this->getLastTag());

		if (strpos($this->getLastRowOfStreamContent(), $signature) === 0)
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
		$this->sendCommand('{tag} LOGOUT');
	}
}
