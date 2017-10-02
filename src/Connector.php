<?php

namespace DecodeLLC\MSP;

/**
 * Import classes
 */
use DecodeLLC\MSP\Driver\Driver;

/**
 * Connector
 */
class Connector
{

	/**
	 * {@description}
	 */
	protected $driver;

	/**
	 * {@description}
	 *
	 * @param   \DecodeLLC\MSP\Driver\Driver   $driver
	 *
	 * @access  public
	 * @return  void
	 */
	public function __construct(Driver $driver)
	{
		$this->driver = $driver;
	}

	/**
	 * {@description}
	 *
	 * @param   int   $timeout
	 *
	 * @access  public
	 * @return  bool
	 */
	public function connect($timeout = null)
	{
		return $this->getDriver()->connect($timeout);
	}

	/**
	 * {@description}
	 *
	 * @access  public
	 * @return  void
	 */
	public function disconnect()
	{
		$this->getDriver()->disconnect();
	}

	/**
	 * {@description}
	 *
	 * @access  public
	 * @return  \DecodeLLC\MSP\Driver\Driver
	 */
	public function getDriver()
	{
		return $this->driver;
	}
}
