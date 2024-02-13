<h3><?php esc_html_e( 'Default Configuration', 'adsa' ); ?></h3>
<dl>
	<dt><h4><?php esc_html_e( 'Donors (Users)', 'adsa' ); ?></h4></dt>
	<dd>
		<strong><?php esc_html_e( 'Users who make donations are stored as "Contact" Objects.', 'adsa' ); ?></strong><br>
		<strong><?php esc_html_e( 'This is in place to make the plugin easier to configure, but all settings are manageable below.', 'adsa' ); ?></strong><br>
		<strong><?php esc_html_e( 'Form fields stored are:', 'adsa' ); ?></strong>
		<ul>
			<li><?php esc_html_e( 'Billing First Name', 'adsa' ); ?> => <em>FirstName</em></li>
			<li><?php esc_html_e( 'Billing Last Name', 'adsa' ); ?> => <em>LastName</em></li>
			<li><?php esc_html_e( 'Billing Email Address', 'adsa' ); ?> => <em>Email</em></li>
			<li><?php esc_html_e( 'Billing Telephone (optionally)', 'adsa' ); ?> => <em>HomePhone</em></li>
			<li><?php esc_html_e( 'Billing Address Line 1', 'adsa' ); ?> => <em>MailingAddress</em></li>
			<li><?php esc_html_e( 'Billing Address Line 2', 'adsa' ); ?> => <em>MailingStreet</em></li>
			<li><?php esc_html_e( 'Billing Address City', 'adsa' ); ?> => <em>MailingCity</em></li>
			<li><?php esc_html_e( 'Billing Address State', 'adsa' ); ?> => <em>MailingState</em></li>
			<li><?php esc_html_e( 'Billing Address Postcode', 'adsa' ); ?> => <em>MailingPostalCode</em></li>
			<li><?php esc_html_e( 'Billing Address Country', 'adsa' ); ?> => <em>MailingCountry</em></li>
			<li><?php esc_html_e( 'Donor Locale', 'adsa' ); ?> => <em>Languages__c</em></li>
		</ul>
	</dd>
	<dt><h4><?php esc_html_e( 'Donations (Purchases)', 'adsa' ); ?></h4></dt>
	<dd><strong><?php esc_html_e( 'The donation object must be configured manually.', 'adsa' ); ?></strong></dd>
	<dt><h4><?php esc_html_e( 'Donation <=> Donor Relationships', 'adsa' ); ?></h4></dt>
	<dd><strong><?php esc_html_e( 'The relationship between a Donation and a Donor must be declared manually.', 'adsa' ); ?></strong></dd>
</dl>
