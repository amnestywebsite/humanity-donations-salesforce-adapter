<?php

declare( strict_types = 1 );

namespace Amnesty\Donations\Salesforce;

/**
 * Option managment base class
 */
class Option extends Singleton {

	/**
	 * Instance variable
	 *
	 * @var static
	 */
	protected static $instance = null;

	/**
	 * Option key
	 *
	 * @var string
	 */
	protected static $key = '';

	/**
	 * Option data
	 *
	 * @var array
	 */
	protected static $option = [];

	/**
	 * Whether option data has been changed
	 *
	 * @var boolean
	 */
	protected static $dirty = false;

	/**
	 * Retrieve stored option from DB
	 */
	protected function __construct() {
		static::$option = get_site_option( static::key() ) ?: [];
	}

	/**
	 * Update stored option in DB
	 */
	public function __destruct() {
		if ( ! static::$dirty ) {
			return;
		}

		if ( ! empty( static::$option ) ) {
			update_site_option( static::key(), static::$option );
		} else {
			delete_site_option( static::key() );
		}
	}

	/**
	 * Get Option key
	 *
	 * @return string
	 */
	protected function key(): string {
		return static::$key;
	}

	/**
	 * Check option has all requested keys
	 *
	 * @param array ...$keys keys to check
	 *
	 * @return boolean
	 */
	protected function has( ...$keys ): bool {
		if ( 1 === count( $keys ) ) {
			if ( ! is_array( $keys[0] ) ) {
				return array_has( static::$option, $keys[0] );
			}

			return count( static::pick( $keys ) ) === count( $keys );
		}

		$has = false;

		foreach ( $keys as $key ) {
			$has = $has || array_has( static::$option, $key );
		}

		return $has;
	}

	/**
	 * Get an option value
	 *
	 * @param string $key     the option key
	 * @param mixed  $default_value default return value
	 *
	 * @return mixed
	 */
	protected function get( string $key = '', $default_value = null ) {
		return array_get( static::$option, $key, $default_value );
	}

	/**
	 * Get some option values
	 *
	 * @param array $keys the option key(s)
	 *
	 * @return array
	 */
	protected function pick( array $keys = [] ): array {
		return array_filter( array_map( [ $this, 'get' ], $keys ) );
	}

	/**
	 * Get all option values
	 *
	 * @return array
	 */
	protected function all(): array {
		return static::$option;
	}

	/**
	 * Get all option keys
	 *
	 * @return array
	 */
	protected function keys(): array {
		return array_keys( static::$option );
	}

	/**
	 * Set option value(s)
	 *
	 * @param array ...$args option key(s) and value(s)
	 *
	 * @return void
	 */
	protected function set( ...$args ): void {
		static::$dirty = true;

		if ( ! is_array( $args[0] ) ) {
			array_set( static::$option, $args[0], $args[1] ?? null );
			return;
		}

		foreach ( $args[0] as $key => $value ) {
			array_set( static::$option, $key, $value );
		}
	}

	/**
	 * Unset one or more option keys
	 *
	 * @param array ...$keys the option key(s) to delete
	 *
	 * @return void
	 */
	protected function unset( ...$keys ): void {
		static::$dirty = true;

		if ( is_array( $keys[0] ) ) {
			$keys = $keys[0];
		}

		foreach ( $keys as $key ) {
			unset( static::$option[ $key ] );
		}
	}

	/**
	 * Remove all option values
	 *
	 * @return void
	 */
	protected function clear(): void {
		static::$dirty  = true;
		static::$option = [];
	}

}
