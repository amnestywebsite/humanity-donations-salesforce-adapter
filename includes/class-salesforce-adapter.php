<?php // phpcs:ignore WordPress.Files.FileName.InvalidClassFileName

declare( strict_types = 1 );

namespace Amnesty\Donations\Salesforce;

use Amnesty\Salesforce\Exception;
use Amnesty\Salesforce\Request;
use Amnesty\Salesforce\SObjects;
use WC_Order;
use WC_Order_Item;

/**
 * Salesforce communication handler class
 */
class Adapter {

	/**
	 * Salesforce settings
	 *
	 * @var array
	 */
	protected static $settings = [
		'donor' => [
			'sobject'            => 'Contact',
			'billing_first_name' => 'FirstName',
			'billing_last_name'  => 'LastName',
			'billing_email'      => 'Email',
			'billing_phone'      => 'HomePhone',
			'billing_address1'   => 'MailingAddress',
			'billing_address2'   => 'MailingStreet',
			'billing_city'       => 'MailingCity',
			'billing_state'      => 'MailingState',
			'billing_postcode'   => 'MailingPostalCode',
			'billing_country'    => 'MailingCountry',
			'user_locale'        => 'Languages__c',
		],
	];

	/**
	 * The current order
	 *
	 * @var WC_Order
	 */
	protected $order = null;

	/**
	 * Retrieved order data
	 *
	 * @var array
	 */
	protected $data = [];

	/**
	 * Setup and execute donation data
	 *
	 * @param integer $order_id the WooCommerce order ID
	 */
	public function __construct( int $order_id = 0 ) {
		$this->order = wc_get_order( $order_id );

		if ( ! $this->should_record_order() ) {
			return;
		}

		$this->data = Fields::data( $this->order );

		$this->setup();

		try {
			$this->store();
		} catch ( \Exception $e ) {
			// do something with this?
			$e->getMessage();
		}
	}

	/**
	 * Retrieve formatted settings
	 *
	 * @return void
	 */
	protected function setup(): void {
		// strip placeholder on empty value
		$replace = function ( string $item ): string {
			return '~' === $item ? '' : $item;
		};

		// retrieve user settings
		$donor        = array_map( $replace, Settings::get( 'donor.0', [] ) );
		$purchase     = array_map( $replace, Settings::get( 'purchase.0', [] ) );
		$relationship = array_map( $replace, Settings::get( 'relationship.0', [] ) );

		// ensure user has actually set at least some custom values; fallback to existing
		$donor        = array_filter( $donor ) ?: static::$settings['donor'];
		$purchase     = array_filter( $purchase ) ?: [];
		$relationship = array_filter( $relationship ) ?: [];

		// override defaults with user settings
		static::$settings = compact( 'donor', 'purchase', 'relationship' );
	}

	/**
	 * Create the records in Salesforce
	 *
	 * @return void
	 */
	protected function store() {
		$contact_id  = $this->create_donor();
		$purchase_id = $this->create_purchase( $contact_id );

		if ( 'object' === static::$settings['relationship']['method'] ) {
			$this->create_relationship( $contact_id, $purchase_id );
		}

		$this->order->update_meta_data( 'amnesty_salesforce_id', $purchase_id );
	}

	/**
	 * Retrieve all supported data from the order
	 *
	 * @return array
	 */
	protected function get_donor_data(): array {
		$cached = wp_cache_get( __FUNCTION__, 'adsa-order' );

		if ( false !== $cached ) {
			return $cached;
		}

		$settings = array_flip( array_diff_key( static::$settings['donor'], [ 'sobject' => true ] ) );

		$is_field = 'field' === static::$settings['relationship']['method'];
		$is_donor = static::$settings['donor']['sobject'] === static::$settings['relationship']['sobject'];

		if ( $is_field && $is_donor ) {
			$field = static::$settings['relationship']['field'];
			$value = static::$settings['relationship']['field_value'];

			$settings[ $value ] = $field;
		}

		$data = [];

		foreach ( $settings as $field => $object_field ) {
			$key = Sobjects::get( static::$settings['donor']['sobject'] )->get( $object_field )->name();
			$val = $field;

			if ( ! in_array( $field, [ 'SALESFORCE_DONOR_ID', 'SALESFORCE_DONATION_ID' ], true ) ) {
				$val = $this->data[ $field ];
			}

			$data[ $key ] = $val;
		}

		$to_cache = array_filter( $data );

		wp_cache_add( __FUNCTION__, $to_cache, 'adsa-order' );

		return $to_cache;
	}

	/**
	 * Create a donor in Salesforce
	 *
	 * @return string|null
	 */
	protected function create_donor(): ?string {
		$donor_id = $this->find_donor();

		if ( $donor_id ) {
			return $this->update_donor( $donor_id );
		}

		$sobject = static::$settings['donor']['sobject'];
		$data    = $this->get_donor_data();
		$data    = array_diff_key( $data, [ 'AccountId' => true ] );

		$resp_data = Request::post( '/sobjects/' . $sobject, $data );

		$id = null;

		if ( isset( $resp_data['id'] ) ) {
			$id = sanitize_text_field( $resp_data['id'] );
		}

		return $id;
	}

	/**
	 * Search for existing donor in Salesforce
	 *
	 * @return string|null
	 */
	protected function find_donor(): ?string {
		$sobject  = static::$settings['donor']['sobject'];
		$settings = array_flip( array_diff_key( static::$settings['donor'], [ 'sobject' => true ] ) );
		$fields   = array_merge( [ 'Id' ], array_values( $settings ) );

		$resp_data = Request::post(
			'/parameterizedSearch',
			[
				'q'        => $this->data['billing_email'],
				'in'       => $settings['billing_email'],
				'sobjects' => [ [ 'name' => $sobject ] ],
				'fields'   => $fields,
			]
		);

		if ( empty( $resp_data['searchRecords'] ) ) {
			return null;
		}

		$found = false;

		foreach ( $resp_data['searchRecords'] as $result ) {
			if ( $this->data['billing_email'] !== $result[ $settings['billing_email'] ] ) {
				continue;
			}

			$found = $result;
		}

		if ( ! $found ) {
			return null;
		}

		return sanitize_text_field( $found['Id'] );
	}

	/**
	 * Update an existing donor in Salesforce
	 *
	 * @param string $donor_id the donor's Salesforce ID
	 *
	 * @return string
	 */
	protected function update_donor( string $donor_id = '' ): string {
		$sobject = static::$settings['donor']['sobject'];
		$data    = $this->get_donor_data();

		Request::patch( sprintf( '/sobjects/%s/%s', $sobject, $donor_id ), $data );

		return $donor_id;
	}

	/**
	 * Store the donation in Salesforce
	 *
	 * @param string $contact_id the Salesforce identifier for a contact
	 *
	 * @throws \Amnesty\Petitions\Exception thrown if something went wrong
	 *
	 * @return string
	 */
	protected function create_purchase( string $contact_id = '' ): string {
		$sobject  = static::$settings['purchase']['sobject'];
		$sobject  = Sobjects::get( $sobject );
		$settings = array_flip( array_diff_key( static::$settings['purchase'], [ 'sobject' => true ] ) );

		if ( ! $sobject ) {
			throw new Exception( 'Donation object not found', 'error' );
		}

		$is_field    = 'field' === static::$settings['relationship']['method'];
		$is_donation = static::$settings['purchase']['sobject'] === static::$settings['relationship']['sobject'];
		if ( $is_field && $is_donation ) {
			$field = static::$settings['relationship']['field'];
			$value = static::$settings['relationship']['field_value'];

			$settings[ $value ] = $field;
		}

		$data = [];

		foreach ( $settings as $field => $object_field ) {
			$object_field = $sobject->get( $object_field );

			if ( 'SALESFORCE_DONOR_ID' === $field ) {
				$object_value = $contact_id;
			} else {
				$object_value = $this->data[ $field ];
			}

			$data[ $object_field->name() ] = $object_value;
		}

		$resp_data = Request::post( '/sobjects/' . $sobject->name(), $data );

		$id = null;

		if ( isset( $resp_data['id'] ) ) {
			$id = sanitize_text_field( $resp_data['id'] );
		}

		if ( ! $id ) {
			throw new Exception( esc_html( $sobject->name() . ' ID Not Found' ), 'error' );
		}

		return $id;
	}

	/**
	 * If the relationship is a pivot object, record it
	 *
	 * @param string $contact_id  the donor Salesforce ID
	 * @param string $purchase_id the donation Salesforce ID
	 *
	 * @throws \Amnesty\Petitions\Exception thrown if something went wrong
	 *
	 * @return null|string
	 */
	protected function create_relationship( string $contact_id, string $purchase_id ): ?string {
		$salesforce_data = [
			'SALESFORCE_DONOR_ID'    => $contact_id,
			'SALESFORCE_DONATION_ID' => $purchase_id,
		];

		$sobject  = static::$settings['relationship']['object'];
		$settings = array_flip(
			array_diff_key(
				static::$settings['relationship'],
				[
					'method'      => true,
					'sobject'     => true,
					'object'      => true,
					'field'       => true,
					'field_value' => true,
				]
			)
		);

		$data = [];

		foreach ( $settings as $field => $object_field ) {
			$value = null;

			if ( ! empty( $this->data[ $field ] ) ) {
				$value = $this->data[ $field ];
			}

			if ( ! empty( $salesforce_data[ $field ] ) ) {
				$value = $salesforce_data[ $field ];
			}

			$data[ $object_field ] = $value;
		}

		$data = array_filter( $data );

		$resp_data = Request::post( '/sobjects/' . $sobject, $data );

		$id = null;

		if ( isset( $resp_data['id'] ) ) {
			$id = sanitize_text_field( $resp_data['id'] );
		}

		if ( ! $id ) {
			throw new Exception( esc_html( $sobject . ' ID Not Found' ), 'error' );
		}

		return $id;
	}

	/**
	 * Whether an order should be recorded
	 *
	 * @return bool
	 */
	protected function should_record_order(): bool {
		return apply_filters(
			'amnesty_should_record_order',
			! ! array_filter( $this->order->get_items(), [ $this, 'should_record_item' ] ),
			$this->order
		);
	}

	/**
	 * Whether an order item should be a part of the record
	 *
	 * `call_user_func` is used instead of a direct method call
	 * as to avoid intellisense complaints as the method is proxied
	 *
	 * @param WC_Order_Item $item an order item
	 *
	 * @return bool
	 */
	protected function should_record_item( WC_Order_Item $item ): bool {
		$id = call_user_func( [ $item, 'get_product' ] )->get_parent_id();

		return apply_filters(
			'amnesty_should_record_order_item',
			amnesty_product_is_donation( $id ) || amnesty_product_is_subscription( $id ),
			$item
		);
	}

}
