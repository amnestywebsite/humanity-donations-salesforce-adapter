<?php

declare( strict_types = 1 );

namespace Amnesty\Donations\Salesforce;

/**
 * Settings handler object
 */
class Settings extends Option {

	/**
	 * Settings key
	 *
	 * @var string
	 */
	protected static $key = 'amnesty_salesforce_donations';

	/**
	 * Instance variable
	 *
	 * @var self
	 */
	protected static $instance = null;

	/**
	 * Option data
	 *
	 * @var array
	 */
	protected static $option = [];

}
