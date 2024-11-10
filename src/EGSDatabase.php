<?php

namespace Malik12tree\ZATCA;

use Malik12tree\ZATCA\EGS;

abstract class EGSDatabase
{
	/**
	 * @param string $uuid
	 * @return EGS|null
	 */
	protected abstract function _load($uuid);
	/**
	 * @param EGS $egs
	 */
	public abstract function save($egs);

	/**
	 * @param string $uuid
	 * @param bool $assignDatabase If true, the database will be assigned to the EGS object.
	 * @return EGS|null
	 */
	public  function load($uuid, $assignDatabase = true)
	{
		$egs = $this->_load($uuid);
		if ($egs && $assignDatabase) {
			$egs->setDatabase($this);
		}
		return $egs;
	}
}
