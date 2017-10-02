<?php

namespace DecodeLLC\MSP\Driver;

/**
 * Driver
 */
abstract class Driver
{

	/**
	 * {@description}
	 */
	const EOL = "\r\n";

	/**
	 * {@description}
	 *
	 * @var     mixed
	 * @access  private
	 */
	private $stream;

	/**
	 * {@description}
	 *
	 * @var     string
	 * @access  private
	 */
	private $streamContent;

	/**
	 * {@description}
	 *
	 * @var     array
	 * @access  private
	 */
	private $console = [];

	/**
	 * {@description}
	 *
	 * @var     int
	 * @access  private
	 */
	private $seqTag = 0;

	/**
	 * {@description}
	 *
	 * @var     string
	 * @access  private
	 */
	private $lastTag;

	/**
	 * {@description}
	 *
	 * @access  public
	 * @return  string
	 */
	abstract public function getSocket();

	/**
	 * {@description}
	 *
	 * @access  public
	 * @return  bool
	 */
	abstract public function isSuccessfulConnection();

	/**
	 * {@description}
	 *
	 * @access  public
	 * @return  bool
	 */
	abstract public function isSuccessfulCommand();

	/**
	 * {@description}
	 *
	 * @access  protected
	 * @return  void
	 */
	protected function beforeConnect()
	{}

	/**
	 * {@description}
	 *
	 * @access  protected
	 * @return  void
	 */
	protected function afterConnect()
	{}

	/**
	 * {@description}
	 *
	 * @access  protected
	 * @return  void
	 */
	protected function beforeDisconnect()
	{}

	/**
	 * {@description}
	 *
	 * @access  protected
	 * @return  void
	 */
	protected function afterDisconnect()
	{}

	/**
	 * {@description}
	 *
	 * @access  public
	 * @return  void
	 */
	final public function __destruct()
	{
		$this->disconnect();
	}

	/**
	 * {@description}
	 *
	 * @param   int   $timeout
	 *
	 * @access  public
	 * @return  bool
	 *
	 * @throws  \RuntimeException
	 */
	final public function connect($timeout = 30)
	{
		$this->beforeConnect();

		$this->stream = stream_socket_client(
			$this->getSocket(), $errno, $errmsg, $timeout
		);

		if ($this->isConnected())
		{
			if ($this->isSuccessfulConnection())
			{
				$this->afterConnect();

				return true;
			}

			$this->disconnect();

			return false;
		}

		throw new \RuntimeException($errmsg, $errno);
	}

	/**
	 * {@description}
	 *
	 * @access  public
	 * @return  void
	 */
	final public function disconnect()
	{
		if ($this->isConnected())
		{
			$this->beforeDisconnect();

			fclose($this->getStream());

			$this->afterDisconnect();
		}
	}

	/**
	 * {@description}
	 *
	 * @access  public
	 * @return  bool
	 */
	final public function reconnect()
	{
		$this->disconnect();

		return $this->connect();
	}

	/**
	 * {@description}
	 *
	 * @access  public
	 * @return  bool
	 */
	final public function isConnected()
	{
		return $this->existsStream();
	}

	/**
	 * {@description}
	 *
	 * @access  public
	 * @return  void
	 *
	 * @throws  \LogicException
	 */
	final public function setStream()
	{
		throw new \LogicException('The stream is read-only.');
	}

	/**
	 * {@description}
	 *
	 * @access  public
	 * @return  mixed
	 */
	final public function getStream()
	{
		return $this->stream;
	}

	/**
	 * {@description}
	 *
	 * @access  public
	 * @return  bool
	 */
	final public function existsStream()
	{
		return is_resource($this->getStream());
	}

	/**
	 * {@description}
	 *
	 * @param   string   $command
	 * @param   array    $context
	 *
	 * @access  public
	 * @return  bool
	 */
	final public function sendCommand($command, array $context = [])
	{
		if ($this->existsStream())
		{
			$context['tag'] = $this->regenerateTag();

			$this->toConsole('-> ', $command);

			$command = $this->interpolate($command, $context);

			if (fwrite($this->getStream(), $command . self::EOL))
			{
				$this->streamContent = $this->readStream();

				if ($this->isSuccessfulCommand())
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
	 * @param   int   $timeout
	 *
	 * @access  public
	 * @return  string
	 *
	 * @todo    Optimized this method.
	 */
	final public function readStream($timeout = 3)
	{
		if ($this->existsStream())
		{
			if (stream_set_timeout($this->getStream(), $timeout))
			{
				if ($content = stream_get_contents($this->getStream(), -1, -1))
				{
					$this->toConsole('<- ', $content);

					return trim($content);
				}
			}
		}

		return '';
	}

	/**
	 * {@description}
	 *
	 * @access  public
	 * @return  void
	 *
	 * @throws  \LogicException
	 */
	final public function setStreamContent()
	{
		throw new \LogicException('The stream content is read-only.');
	}

	/**
	 * {@description}
	 *
	 * @access  public
	 * @return  string
	 */
	final public function getStreamContent()
	{
		return $this->streamContent;
	}

	/**
	 * {@description}
	 *
	 * @access  public
	 * @return  array
	 */
	final public function getStreamContentByRows()
	{
		return explode(self::EOL, $this->getStreamContent());
	}

	/**
	 * {@description}
	 *
	 * @access  public
	 * @return  string
	 */
	final public function getStreamContentWithoutFirstRow()
	{
		$rows = $this->getStreamContentByRows();

		array_shift($rows);

		return implode(self::EOL, $rows);
	}

	/**
	 * {@description}
	 *
	 * @access  public
	 * @return  string
	 */
	final public function getStreamContentWithoutLastRow()
	{
		$rows = $this->getStreamContentByRows();

		array_pop($rows);

		return implode(self::EOL, $rows);
	}

	/**
	 * {@description}
	 *
	 * @access  public
	 * @return  string
	 */
	final public function getFirstRowOfStreamContent()
	{
		$rows = $this->getStreamContentByRows();

		return reset($rows);
	}

	/**
	 * {@description}
	 *
	 * @access  public
	 * @return  string
	 */
	final public function getLastRowOfStreamContent()
	{
		$rows = $this->getStreamContentByRows();

		return end($rows);
	}

	/**
	 * {@description}
	 *
	 * @param   string   $prefix
	 * @param   string   $message
	 * @param   array    $context
	 *
	 * @access  public
	 * @return  void
	 */
	final public function toConsole($prefix, $message, array $context = [])
	{
		$rows = explode(self::EOL, $message);

		foreach ($rows as $row)
		{
			if ('' === trim($row)) {
				continue;
			}

			$this->console[] = $this->interpolate($prefix . $row, $context);
		}
	}

	/**
	 * {@description}
	 *
	 * @access  public
	 * @return  array
	 */
	final public function getConsole()
	{
		return $this->console;
	}

	/**
	 * {@description}
	 *
	 * @access  public
	 * @return  string
	 *
	 * @see     https://ru.wikipedia.org/wiki/IMAP
	 */
	final public function regenerateTag()
	{
		$this->seqTag++;

		$this->lastTag = 'A';

		if (3 >= $s = strlen($this->seqTag))
		{
			$this->lastTag .= str_repeat('0', 4 - $s);
		}

		$this->lastTag .= $this->seqTag;

		return $this->lastTag;
	}

	/**
	 * {@description}
	 *
	 * @access  public
	 * @return  string
	 */
	final public function getLastTag()
	{
		return $this->lastTag;
	}

	/**
	 * {@description}
	 *
	 * @param   string   $message
	 * @param   array    $context
	 *
	 * @access  public
	 * @return  string
	 */
	final public function interpolate($message, array $context = [])
	{
		$substitutable = [];

		foreach ($context as $key => $value)
		{
			$substitutable['{' . $key . '}'] = $value;
		}

		return strtr($message, $substitutable);
	}
}
