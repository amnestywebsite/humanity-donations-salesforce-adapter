<?php

declare( strict_types = 1 );

namespace Amnesty\Donations\Salesforce;

use WC_Order;

/**
 * Field mappings class
 */
class Fields {

	/**
	 * Default Salesforce fields for donation information
	 *
	 * @var array
	 */
	protected static $defaults = [
		'Contact' => [
			'billing_first_name' => 'FirstName',
			'billing_last_name'  => 'LastName',
			'billing_email'      => 'Email',
			'billing_phone'      => 'HomePhone',
			'billing_address1'   => 'MailingStreet',
			'billing_city'       => 'MailingCity',
			'billing_state'      => 'MailingState',
			'billing_postcode'   => 'MailingPostalCode',
			'billing_country'    => 'MailingCountry',
			'user_locale'        => 'Languages__c',
		],
	];

	/**
	 * Field name to label mapping
	 *
	 * @return array
	 */
	public static function labels(): array {
		return [
			'SALESFORCE_DONOR_ID'    => __( 'The Donor\'s Salesforce ID', 'adsa' ),
			'SALESFORCE_DONATION_ID' => __( 'The Donation\'s Salesforce ID', 'adsa' ),
			'billing_first_name'     => __( 'Billing First Name', 'adsa' ),
			'billing_last_name'      => __( 'Billing Last Name', 'adsa' ),
			'billing_email'          => __( 'Billing Email Address', 'adsa' ),
			'billing_phone'          => __( 'Billing Phone Number', 'adsa' ),
			'billing_address1'       => __( 'Billing Address Line 1', 'adsa' ),
			'billing_address2'       => __( 'Billing Address Line 2', 'adsa' ),
			'billing_city'           => __( 'Billing Address City', 'adsa' ),
			'billing_state'          => __( 'Billing Address State', 'adsa' ),
			'billing_postcode'       => __( 'Billing Address Postcode', 'adsa' ),
			'billing_country'        => __( 'Billing Address Country', 'adsa' ),
			'transaction_number'     => __( 'Transaction ID', 'adsa' ),
			'payment_method'         => __( 'Payment Method', 'adsa' ),
			'purchase_date'          => __( 'Purchase Date', 'adsa' ),
			'order_number'           => __( 'Order Number', 'adsa' ),
			'order_summary'          => __( 'Order Summary', 'adsa' ),
			'order_status'           => __( 'Order Status', 'adsa' ),
			'order_total'            => __( 'Order Total', 'adsa' ),
			'additional'             => __( 'Chosen Campaign', 'adsa' ),
			'user_locale'            => __( 'User Locale', 'adsa' ),
		];
	}

	/**
	 * Getter for Salesforce object defaults
	 *
	 * @param string $sobject the Salesforce object
	 *
	 * @return array
	 */
	public static function defaults( string $sobject ): array {
		return static::$defaults[ $sobject ] ?? [];
	}

	/**
	 * Getter for Salesforce object field defaults
	 *
	 * @param string $sobject the Salesforce object
	 * @param string $field   the donation Field
	 *
	 * @return string
	 */
	public static function default( string $sobject, string $field ): string {
		return static::$defaults[ $sobject ][ $field ] ?? '~';
	}

	/**
	 * Getter for donation data
	 *
	 * @param \WC_Order $order the donation
	 *
	 * @return array
	 */
	public static function data( WC_Order $order ): array {
		return [
			'billing_first_name' => $order->get_billing_first_name(),
			'billing_last_name'  => $order->get_billing_last_name(),
			'billing_email'      => $order->get_billing_email(),
			'billing_phone'      => $order->get_billing_phone(),
			'billing_address1'   => $order->get_billing_address_1(),
			'billing_address2'   => $order->get_billing_address_2(),
			'billing_city'       => $order->get_billing_city(),
			'billing_state'      => $order->get_billing_state(),
			'billing_postcode'   => $order->get_billing_postcode(),
			'billing_country'    => $order->get_billing_country(),
			'transaction_number' => $order->get_transaction_id(),
			'payment_method'     => $order->get_payment_method(),
			'purchase_date'      => $order->get_date_created()->format( 'Y-m-d' ),
			'order_number'       => $order->get_id(),
			'order_status'       => $order->get_status(),
			'order_total'        => $order->get_total(),
			'additional'         => amnesty_wc_get_wooccm_field_value( $order->get_id() ),
			'user_locale'        => get_user_locale( $order->get_user_id() ),
			'order_summary'      => sprintf(
				// translators: %1$s: order ID, %2$s transaction ID
				__( 'Order: %1$s; Transaction: %2$s', 'adsa' ),
				$order->get_order_number(),
				$order->get_transaction_id()
			),
		];
	}

}
