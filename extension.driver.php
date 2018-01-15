<?php

Class extension_Stripe_Payments extends Extension {

	public function getSubscribedDelegates() {
		return array(
			array(
				'page' => '/system/preferences/',
				'delegate' => 'Save',
				'callback' => 'save_preferences'
			),
			array(
				'page' => '/system/preferences/success/',
				'delegate' => 'Save',
				'callback' => 'save_preferences'
			),
			array(
				'page' => '/system/preferences/',
				'delegate' => 'AddCustomPreferenceFieldsets',
				'callback' => 'append_preferences'
			),
			array(
				'page' => '/blueprints/events/new/',
				'delegate' => 'AppendEventFilter',
				'callback' => 'addFilterToEventEditor'
			),
			array(
				'page' => '/blueprints/events/edit/',
				'delegate' => 'AppendEventFilter',
				'callback' => 'addFilterToEventEditor'
			),
			array(
				'page' => '/frontend/',
				'delegate' => 'EventPreSaveFilter',
				'callback' => 'check_stripe_preferences'
			),
			array(
				'page' => '/frontend/',
				'delegate' => 'EventFinalSaveFilter',
				'callback' => 'process_event_data'
			),
			array(
				'page' => '/frontend/',
				'delegate' => 'FrontendParamsResolve',
				'callback' => 'addStripeParams'
			),
		);
	}

	public function addStripeParams(array $context = null) {
		$context['params']['stripe-publishable-key'] = $this->_get_publishable_key();
	}

	public function process_event_data($context) {
//		var_dump(in_array('stripe-payments', $context['event']->eParamFILTERS));
//		die();
		if (!in_array('stripe-payments', $context['event']->eParamFILTERS)) {
			return;
		}
//		var_dump($_POST);
//		die("OK");
	}

	public function check_stripe_preferences(&$context) {
//		var_dump(in_array('stripe-payments', $context['event']->eParamFILTERS));
//		die();
//		var_dump($_POST);
//		die("OK");
		if (!in_array('stripe-payments', $context['event']->eParamFILTERS)) {
			return;
		}
		$context['messages'][] = array(
			'stripe-payments',
			FALSE,
			'You need to set the your business ID/email in the preferences.'
		);
		return;
	}

	public function install() {
		try {
			Symphony::Database()->query("CREATE TABLE IF NOT EXISTS `tbl_stripepayments_logs` (
                                    `id` int(11) NOT NULL auto_increment,
                                    `customer` varchar(255) NOT NULL,
									`payment_date` date NOT NULL,
                                    `amount` varchar(255) NOT NULL,
                                    `paid` tinyint(1) NOT NULL,
                                    `status` varchar(255) NOT NULL,
                                    PRIMARY KEY (`id`)
                                  ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;");

			return TRUE;
		} catch (Exception $ex) {
			return FALSE;
		}
	}

	public function uninstall() {
//        # Remove tables
//        Symphony::Database()->query("DROP TABLE `tbl_stripepayments_logs`");
//
//        # Remove preferences
//        Symphony::Configuration()->remove('stripe-payments');
//        Administration::instance()->saveConfig();
	}

	public function save_preferences() {
		
	}

	public function addFilterToEventEditor($context) {
		$context['options'][] = array('stripe-payments', @in_array('stripe-payments', $context['selected']), 'Stripe Payments Test Event Filter');
	}

	public function append_preferences($context) {
		# Add new fieldset
		$group = new XMLElement('fieldset');
		$group->setAttribute('class', 'settings');
		$group->appendChild(new XMLElement('legend', 'Stripe Payments'));

		# Add Publishable key field
		$label = Widget::Label('Publishable key');
		$label->appendChild(Widget::Input('settings[stripe-payments][publishable-key]', General::Sanitize($this->_get_publishable_key())));
		$group->appendChild($label);
		$group->appendChild(new XMLElement('p', "Publishable API keys are meant solely to identify your account with Stripe, they aren't secret. In other words, they can safely be published in places like your Stripe.js JavaScript code, or in an Android or iPhone app. Publishable keys only have the power to create tokens.", array('class' => 'help')));

		# Add Secret key field
		$label = Widget::Label('Secret key');
		$label->appendChild(Widget::Input('settings[stripe-payments][secret-key]', General::Sanitize($this->_get_secret_key())));
		$group->appendChild($label);
		$group->appendChild(new XMLElement('p', "Secret API keys should be kept confidential and only stored on your own servers. Your account's secret API key can perform any API request to Stripe without restriction.", array('class' => 'help')));

		# Add Secret key field
		$label = Widget::Label('Logs Per Page');
		$label->appendChild(Widget::Input('settings[stripe-payments][logs-per-page]', General::Sanitize($this->get_logs_per_page()), "number"));
		$group->appendChild($label);
		$group->appendChild(new XMLElement('p', "Set log records per page at the backend.", array('class' => 'help')));

		$context['wrapper']->appendChild($group);
	}

	private function _get_publishable_key() {
		return Symphony::Configuration()->get('publishable-key', 'stripe-payments');
	}

	private function _get_secret_key() {
		return Symphony::Configuration()->get('secret-key', 'stripe-payments');
	}

	public function fetchNavigation() {
		$nav = array();
		$nav[] = array(
			'location' => 262,
			'name' => 'Stripe Payments',
			'type' => 'content',
			'children' => array(
				array(
					'name' => 'Transactions',
					'link' => '/logs/',
					'limit' => 'developer',
				)
			)
		);
		return $nav;
	}

	public function _count_logs() {
		return (integer) Symphony::Database()->fetchVar('total', 0, "
				SELECT
					COUNT(l.id) AS `total`
				FROM
					`tbl_stripepayments_logs` AS l
			");
	}

	public function _get_logs_by_page($page, $per_page) {
		$start = ($page - 1) * $per_page;

		return Symphony::Database()->fetch("
				SELECT
					l.*
				FROM
					`tbl_stripepayments_logs` AS l
				ORDER BY
					l.id DESC
				LIMIT {$start}, {$per_page}
			");
	}

	public function _get_logs() {
		return Symphony::Database()->fetch("
				SELECT
					l.*
				FROM
					`tbl_stripepayments_logs` AS l
				ORDER BY
					l.id DESC
			");
	}

	public function _get_log($log_id) {
		return Symphony::Database()->fetchRow(0, "
				SELECT
					l.*
				FROM
					`tbl_stripepayments_logs` AS l
				WHERE
					l.id = '{$log_id}'
				LIMIT 1
			");
	}

	public function get_logs_per_page() {
		return Symphony::Configuration()->get('logs-per-page', 'stripe-payments');
	}

}
