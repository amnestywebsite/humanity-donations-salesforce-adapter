<?php

declare( strict_types = 1 );

namespace Amnesty\Donations\Salesforce;

use Amnesty\Salesforce\SObjects;
use Amnesty\Salesforce\Tokens;
use CMB2;

/**
 * Settings page management class
 */
class Page_Settings {

	/**
	 * Setting meta key
	 *
	 * @var string
	 */
	protected $id = 'amnesty_donations_settings_salesforce';

	/**
	 * Settings page slug
	 *
	 * @var string
	 */
	protected $slug = 'salesforce_settings';

	/**
	 * Spin up settings page
	 *
	 * @param \CMB2 $cmb2 CMB2 object instance
	 */
	public function __construct( CMB2 $cmb2 ) {
		$this->register_settings( $cmb2 );
	}

	/**
	 * Register required settings
	 *
	 * @param CMB2 $settings the CMB2 instance
	 *
	 * @return void
	 */
	public function register_settings( CMB2 $settings ): void {
		if ( ! Tokens::has( 'refresh_token' ) ) {
			$settings->add_field(
				[
					'id'      => 'message',
					'type'    => 'message',
					'message' => $this->get_message( 'not-authorised' ),
				]
			);
			return;
		}

		$settings->add_field(
			[
				'id'      => 'info',
				'type'    => 'message',
				'message' => $this->get_message( 'default-info' ),
			]
		);

		$this->register_donor_settings( $settings );
		$this->register_donation_settings( $settings );
		$this->register_relation_settings( $settings );
	}

	/**
	 * Register settings for the donor
	 *
	 * @param \CMB2 $settings CMB2 settings object
	 *
	 * @return void
	 */
	protected function register_donor_settings( CMB2 $settings ): void {
		$group = $settings->add_field(
			[
				'id'         => 'donor',
				'name'       => __( 'Donor', 'adsa' ),
				'desc'       => __( 'Settings for synchronising donor information to Salesforce', 'adsa' ),
				'type'       => 'group',
				'repeatable' => false,
				'options'    => [
					'closed' => true,
				],
			]
		);

		$settings->add_group_field(
			$group,
			[
				'id'      => 'sobject',
				'name'    => __( 'Salesforce Object type', 'adsa' ),
				'desc'    => __( 'Select which Salesforce Object the donor should be stored as', 'adsa' ),
				'type'    => 'select',
				'default' => 'Contact',
				'options' => SObjects::list(),
			]
		);

		$sobject = Settings::get( 'donor.0.sobject', 'Contact' );
		$object  = SObjects::get( $sobject );
		$fields  = $object ? $object->list() : [];

		foreach ( $fields as $id => $name ) {
			if ( in_array( $id, [ '~', 'Id' ], true ) ) {
				continue;
			}

			$settings->add_group_field(
				$group,
				[
					'id'      => $id,
					'name'    => $name,
					'desc'    => $id,
					'type'    => 'select',
					'default' => Fields::default( $sobject, $id ),
					'options' => [ '~' => __( 'None', 'cmb2' ) ] + Fields::labels(),
				]
			);
		}
	}

	/**
	 * Register settings for the donation
	 *
	 * @param \CMB2 $settings CMB2 settings object
	 *
	 * @return void
	 */
	protected function register_donation_settings( CMB2 $settings ): void {
		$group = $settings->add_field(
			[
				'id'         => 'purchase',
				'name'       => __( 'Donation', 'adsa' ),
				'desc'       => __( 'Settings for synchronising order information to Salesforce', 'adsa' ),
				'type'       => 'group',
				'repeatable' => false,
			]
		);

		$settings->add_group_field(
			$group,
			[
				'id'         => 'sobject',
				'name'       => __( 'Salesforce Object type', 'adsa' ),
				'desc'       => __( 'Select which Salesforce Object the donation should be stored as', 'adsa' ),
				'type'       => 'select',
				'default'    => 'Order',
				'options'    => SObjects::list(),
				'attributes' => [
					'data-replace-fields' => 'true',
				],
			]
		);

		$sobject = Settings::get( 'purchase.0.sobject', '' );
		$object  = SObjects::get( $sobject );
		$fields  = $object ? $object->list() : [];

		foreach ( $fields as $id => $name ) {
			if ( in_array( $id, [ '~', 'Id' ], true ) ) {
				continue;
			}

			$settings->add_group_field(
				$group,
				[
					'id'         => $id,
					'name'       => $name,
					'desc'       => $id,
					'type'       => 'select',
					'options'    => [ '~' => __( 'None', 'cmb2' ) ] + Fields::labels(),
					'attributes' => [
						'data-replace-from' => 'sobject',
					],
				]
			);
		}
	}

	/**
	 * Register settings for the relationship between a donation and a donor
	 *
	 * @param \CMB2 $settings CMB2 settings object
	 *
	 * @return void
	 */
	protected function register_relation_settings( CMB2 $settings ): void {
		$group = $settings->add_field(
			[
				'id'         => 'relationship',
				'name'       => __( 'Relationship', 'adsa' ),
				'desc'       => __( 'Settings for synchronising donation to donor relationship to Salesforce', 'adsa' ),
				'type'       => 'group',
				'repeatable' => false,
			]
		);

		$settings->add_group_field(
			$group,
			[
				'id'      => 'method',
				'name'    => __( 'Configure Relationship', 'adsa' ),
				'desc'    => __( 'How is the relationship between a donation and a donor defined?', 'adsa' ),
				'type'    => 'select',
				'options' => [
					'object' => __( 'Object', 'adsa' ),
					'field'  => __( 'Field', 'adsa' ),
				],
			]
		);

		$method = Settings::get( 'relationship.0.method', 'field' );

		// if it's on a primary object

		$settings->add_group_field(
			$group,
			[
				'id'         => 'sobject',
				'name'       => __( 'Salesforce Object type', 'adsa' ),
				'desc'       => __( 'Should the field be set on the donation object or the donor object?', 'adsa' ),
				'type'       => 'select',
				'classes'    => 'object' === $method ? 'is-hidden' : '',
				'options'    => [
					Settings::get( 'purchase.0.sobject', '' ) => __( 'Donation', 'adsa' ),
					Settings::get( 'donor.0.sobject', 'Contact' ) => __( 'Donor', 'adsa' ),
				],
				'attributes' => [
					'data-show-on' => 'field',
				],
			]
		);

		$sobject = Settings::get( 'relationship.0.sobject', '' );
		$object  = Sobjects::get( $sobject );
		$fields  = $object ? $object->list() : [];

		$settings->add_group_field(
			$group,
			[
				'id'         => 'field',
				'name'       => __( 'Salesforce Object field', 'adsa' ),
				'desc'       => __( 'Select the field to be used for the relationship', 'adsa' ),
				'type'       => 'select',
				'classes'    => 'object' === $method ? 'is-hidden' : '',
				'options'    => $fields,
				'attributes' => [
					'data-show-on'  => 'field',
					'data-populate' => 'sobject',
				],
			]
		);

		$settings->add_group_field(
			$group,
			[
				'id'         => 'field_value',
				'name'       => __( 'Field value', 'adsa' ),
				'desc'       => __( 'Select the field to be used as the value of the Object field', 'adsa' ),
				'type'       => 'select',
				'classes'    => 'object' === $method ? 'is-hidden' : '',
				'options'    => Fields::labels(),
				'attributes' => [
					'data-show-on' => 'field',
				],
			]
		);

		// if it's a separate object

		$settings->add_group_field(
			$group,
			[
				'id'         => 'object',
				'name'       => __( 'Salesforce Object type', 'adsa' ),
				'desc'       => __( 'Select which Salesforce Object the relationship should be stored as', 'adsa' ),
				'type'       => 'select',
				'classes'    => 'field' === $method ? 'is-hidden' : '',
				'options'    => SObjects::list(),
				'attributes' => [
					'data-show-on'        => 'object',
					'data-replace-fields' => 'true',
				],
			]
		);

		$sobject = Settings::get( 'relationship.0.object', '' );
		$object  = SObjects::get( $sobject );
		$fields  = $object ? $object->list() : [];

		foreach ( $fields as $id => $name ) {
			if ( in_array( $id, [ '~', 'Id' ], true ) ) {
				continue;
			}

			$settings->add_group_field(
				$group,
				[
					'id'         => $id,
					'name'       => $name,
					'desc'       => $id,
					'type'       => 'select',
					'default'    => Fields::default( $sobject, $id ),
					'classes'    => 'field' === $method ? 'is-hidden' : '',
					'options'    => [ '~' => __( 'None', 'cmb2' ) ] + Fields::labels(),
					'attributes' => [
						'data-show-on'      => 'object',
						'data-replace-from' => 'sobject',
					],
				]
			);
		}
	}

	/**
	 * Retrieve a message from the messages directory
	 *
	 * @param string $name the message name
	 *
	 * @return string
	 */
	protected function get_message( string $name = '' ): string {
		$dir  = dirname( Init::$file );
		$file = sprintf( '%s/messages/%s.php', untrailingslashit( $dir ), $name );

		ob_start();
		include $file;
		return ob_get_clean();
	}

}
