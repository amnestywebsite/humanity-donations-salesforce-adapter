<?php

declare( strict_types = 1 );

namespace Amnesty\Donations\Salesforce;

use ReflectionProperty;

/**
 * Singleton base class
 */
abstract class Singleton {

	/**
	 * Instance variable
	 *
	 * @var static
	 */
	protected static $instance = null;

	/**
	 * Retrieve class instance
	 *
	 * @return static
	 */
	final public static function instance() {
		if ( ! static::$instance ) {
			static::$instance = new static();
		}

		return static::$instance;
	}

	/**
	 * Getter for inaccessible object properties
	 *
	 * @param string $property the property name
	 *
	 * @return mixed
	 */
	final public function __get( $property ) {
		if ( ! property_exists( static::class, $property ) ) {
			return null;
		}

		$prop = new ReflectionProperty( static::class, $property );

		if ( $prop->isStatic() ) {
			return $prop->getValue();
		}

		return $prop->getValue( static::instance() );
	}

	/**
	 * Getter for inaccessible object methods
	 *
	 * @param string $method    the method name
	 * @param array  $arguments the parameters passed
	 *
	 * @throws \RuntimeException if method missing
	 *
	 * @return mixed
	 */
	final public function __call( $method, $arguments ) {
		$self = static::instance();

		if ( ! method_exists( $self, $method ) ) {
			throw new \RuntimeException( esc_html( sprintf( 'Method %s does not exist on %s', $method, static::class ) ) );
		}

		return call_user_func_array( [ $self, $method ], $arguments );
	}

	/**
	 * Getter for inaccessible static methods
	 *
	 * @param string $method    the method name
	 * @param array  $arguments the parameters passed
	 *
	 * @throws \RuntimeException if method missing
	 *
	 * @return mixed
	 */
	final public static function __callStatic( $method, $arguments ) {
		$self = static::instance();

		if ( ! method_exists( $self, $method ) ) {
			throw new \RuntimeException( esc_html( sprintf( 'Method %s does not exist on %s', $method, static::class ) ) );
		}

		return call_user_func_array( [ $self, $method ], $arguments );
	}

	/**
	 * Prevent object cloning
	 *
	 * @return void
	 */
	final protected function __clone() {
	}

}
