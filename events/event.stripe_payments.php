<?php

require_once EXTENSIONS . '/stripe_payments/lib/vendor/autoload.php';

Final Class eventStripe_payments extends Event {

	public static $allowed_params;

	public static function about() {
		return array(
			'name' => 'Stripe Payments: Save data',
			'author' => array('name' => 'Nimantha Perera',
				'website' => 'http://www.eyes-down.net',
				'email' => 'nimantha@eyes-down.net'),
			'version' => '1.0.0',
			'release-date' => '2017-09-26',
		);
	}

	public function __construct() {
		parent::__construct();
		$this->_driver = Symphony::ExtensionManager()->create('stripe_payments');
		self::$allowed_params = array(
			"transaction_id" => "",
			"amount" => "",
			"currency" => "",
			"description" => "",
			"status" => "",
			"paid" => ""
		);
	}

	public function load() {
		ob_start();



//		echo '<pre>';
//		var_dump($status);
//		echo '</pre>';
//		die();

		if ($_POST["stripe_payment"]) {
			$sk = Symphony::Configuration()->get('secret-key', 'stripe-payments');

			$output = new XMLElement('stripe-payments-response');

			$amount = $_POST["actual_amount"];
			$currency = $_POST['currency'];
			$description = $_POST["charge_description"];
			$email = $_POST["stripeEmail"];
//			$email = "failed";

			$fields = $_POST['fields'];
			$hidden_fields = $_POST['hfields'];
			$sections = $_POST['sections'];

//			echo '<pre>';
//			var_dump($fields);
//			echo '</pre>';
//			die();

			$today = date("Y-m-d");

			try {

				if ($amount > 0) {
					// Set the token
					$token = $_POST['stripeToken'];











					// Set your secret key
					\Stripe\Stripe::setApiKey($sk);






					// Charge the user's card:
					$charge = \Stripe\Charge::create(array(
								"amount" => $amount,
								"currency" => $currency,
								"description" => $description,
								"source" => $token
					));

//				echo "<pre>";
//				var_dump($charge->source);
//				echo("</pre>");
//				die();
//				self::$allowed_params["amount"] = number_format(floatval($charge->amount / 100), 2);
//				self::$allowed_params["currency"] = $currency;
//				self::$allowed_params["description"] = $description;
//				self::$allowed_params["status"] = $charge->status;
//				self::$allowed_params["paid"] = $charge->paid;
//				$allowed_params = array(
//					"amount" => $charge->amount,
//					"currency" => $currency,
//					"description" => $description,
//					"status" => $charge->status,
//					"paid" => $charge->paid
//				);

					$post_hidden_fields = new XMLElement('hidden_fields');
					if ($hidden_fields) {
						foreach ($hidden_fields as $key => $val) {
							$post_hidden_fields->appendChild(new XMLElement("$key", $val));
						}
					}
					$output->appendChild($post_hidden_fields);

					if ($charge->paid) {

						$output->setAttribute('result', 'success');
						$output->appendChild(new XMLElement("amount", $charge->amount));
						$output->appendChild(new XMLElement("message", $charge->status));
						self::$allowed_params["transaction_id"] = $charge->id;
						self::$allowed_params["customer"] = $charge->source["name"];
						self::$allowed_params["amount"] = number_format(floatval($charge->amount / 100), 2);
						self::$allowed_params["currency"] = $currency;
						self::$allowed_params["description"] = $description;
						self::$allowed_params["status"] = $charge->status;
						self::$allowed_params["paid"] = $charge->paid;
					} else {
						$output->setAttribute('result', 'error');
						$output->appendChild(new XMLElement("message", $charge->status));

						self::$allowed_params["transaction_id"] = 0;
						self::$allowed_params["customer"] = $email;
						self::$allowed_params["amount"] = $amount;
						self::$allowed_params["currency"] = $currency;
						self::$allowed_params["description"] = $description;
						self::$allowed_params["status"] = "failed";
						self::$allowed_params["paid"] = 0;
					}

					$log = array(
						"transaction_id" => self::$allowed_params["transaction_id"],
						"description" => self::$allowed_params["description"],
						"customer" => $charge->source["name"],
						"payment_date" => $today,
						"amount" => self::$allowed_params["amount"],
						"paid" => self::$allowed_params["paid"],
						"status" => self::$allowed_params["status"]
					);
				}

				$entryManager = new EntryManager(Symphony::Engine());

//				$new_item = new Entry();

				if ($fields) {

					$post_fields = new XMLElement('post-fields');

					foreach ($fields as $section_id => $section_vals) {
						foreach ($section_vals as $entry_id => $entry_vals) {
							foreach ($entry_vals as $entry_handle => $entry_val) {

								$post_fields->appendChild(new XMLElement("$entry_handle", $entry_val));

								$fields = Symphony::Database()->fetch("
						SELECT `id`, `label` FROM `tbl_fields` WHERE `element_name` = '$entry_handle' AND `parent_section` = '$section_id'
					");
								$entries = $entryManager->fetch($entry_id, null, null, null, null, null, false, true);

								$entry = $entries[0];
								$field = $fields[0];

								$field_type = FieldManager::fetchFieldTypeFromID($field['id']);

//							echo '<pre>';
//							var_dump($field);
//							var_dump($entry_val);
//							echo '</pre>';
//
//							die();
//								echo '<pre>';
//								var_dump($entry);
//								var_dump($field);
//								echo '</pre>';

								$value = array_key_exists($entry_val, self::$allowed_params) ? self::$allowed_params[$entry_val] : $entry_val;








								$cols = $this->_get_cols($field_type, $value);

//								echo '<pre>';
//								var_dump($cols);
//								echo '</pre>';

								$entry->setData($field['id'], $cols);

//								$entry->setData($field['id'], array(
//									'handle' => Lang::createHandle($value),
//									'value' => $value
//										)
//								);

								$status = $entry->commit();
//								echo '<pre>';
//								var_dump($entry);
//								var_dump($field);
//								echo '</pre>';
							}
						}
					}

					$output->appendChild($post_fields);
				}

				if ($sections) {

					$entries = new XMLElement("entries");
					$entries->setAttribute('group', 'new');

					foreach ($sections as $source => $data) {

						foreach ($data as $field_handle => $value) {
//							if ($value == "amount") {
//								self::$allowed_params[$value] = self::$allowed_params[$value] / 100;
//							}
							$data[$field_handle] = array_key_exists($value, self::$allowed_params) ? self::$allowed_params[$value] : $value;
						}

						$entry = EntryManager::create();
//						$new_entry_id = $entry->assignEntryId();
//						$data["id"] = $new_entry_id;
						$entry->set('section_id', $source);
						$entry->setDataFromPost($data);

						$status = $entry->commit();

//						echo '<pre>';
//						var_dump($entry->get("id"));
//						echo '</pre>';
//						die();

						$new_entry = new XMLElement("entry");
						$new_entry->setAttribute('id', $entry->get("id"));
						$new_entry->setAttribute('section', $source);

						$entries->appendChild($new_entry);
					}
					$output->appendChild($entries);
				}

				Symphony::Database()->insert($log, 'tbl_stripepayments_logs');
			} catch (Exception $ex) {
				$log = array(
					"transaction_id" => 0,
					"description" => $description,
					"customer" => $email,
					"payment_date" => $today,
					"amount" => number_format(floatval($amount / 100), 2),
					"paid" => 0,
					"status" => "failed"
				);
				$output->setAttribute('result', 'system-error');
				$output->appendChild(new XMLElement("message", $ex->getMessage()));
				Symphony::Database()->insert($log, 'tbl_stripepayments_logs');
			}

//			$entry_id = 1;
//            echo '<pre>';
//            var_dump($entries);
//            var_dump($charge->source["name"]);
//            var_dump($charge->paid);
//            var_dump($charge->status);
//            var_dump($charge->amount);
//            echo '</pre>';
//            die();





			return $output;
		}
	}

	private function _get_cols($type, $value) {

		$cols = array();

		switch ($type) {
			case "input":
				$cols["handle"] = Lang::createHandle($value);
				$cols["value"] = $value;
				break;
			case "date":
				$cols["date"] = date("Y-m-d h:i:s", strtotime($value));
				$cols["value"] = date(DATE_ISO8601, strtotime($value));
				break;
		}
		return $cols;
	}

	public static function documentation() {
		$docs = array();
		$params = "<ul>";
		foreach (self::$allowed_params as $key => $value) {
			$params .= "<li>" . $key . "</li>";
		}
		$params .= "</ul>";
		$docs[] = '
<h3>Overview</h3>
<p>
This event is used to deal with data returned by Stripe&#8217;s Checkout via API and Reconciling/Adding of data to the sections. It does the following:
</p>

<ol>
	<li>Saves the transaction details to <a target="_blank" href="' . SYMPHONY_URL . '/extension/stripe_payments/logs/">the log</a>.</li>
	<li>Reconciles the data return by Stripe Checkout with matching fields with the given allowed parameters.</li>
	<li>Add/Update multiple entries across sections.</li>
	<li>Outputs data/status of payment as XML.</li>
</ol>
<p>For the event to work you&#8217;ll need to assign this event to the page where your stripe form action is set to and also you need to set <code><b>stripe_payment</b></code> hidden field in your form</p>
<h3>Transaction Logs</h3>
<p>The transaction logs store the following data:</p>
<ul>
	<li><code>Customer Email</code></li>
	<li><code>Payment Date</code></li>
	<li><code>Amount Paid</code></li>
	<li><code>Payment Status</code></li>
</ul>

<h3>Inserting Data into Sections as New Records</h3>
<p>In order to add records as a new entry you need to follow the format given below</p>
<samp><b>&lt;input type="hidden" name="sections[SECTION_ID_THAT_YOU_WISH_TO_ADD_NEW_RECORD][FIELD_HANDLE_OF_THE_SECTION]" value="VALUE_TO_ADD"/&gt;</b></samp>
<p>In the above example, if you want to insert a value that returns from Stripe checkout instead of your custom value, you must add the correct parameter from the list of allowed parameters(Refer allowed parameters section).</p>

<h3>Reconciling Data</h3>
<p>To save any of the Stripe checkout data to a corresponding entry, you need to include the field in the following format.</p>
<samp><b>&lt;input type="hidden" name="fields[SECTION_ID_OF_THE_ENTRY_TO_BE_UPDATED][ENTRY_ID_TO_BE_UPDATED][ENTRY_HANDLE]" value="VALUE_TO_BE_UPDATED"/&gt;</b></samp>
<p>In the above example, if you want to update a value that returns from Stripe checkout instead of your custom value, you must add the correct parameter from the list of allowed parameters(Refer allowed parameters section).</p>

<h3>XML Output</h3>
<p>Data returned from Stripe checkout and corresponding messages are included as the <code>&lt;stripe-payments-response&gt;</code> node in the XML output for use in frontend pages.</p>

<h3>List of Allowed Parameters</h3>
' . $params . '

<h3>Essentials</h3>
<p>
Please note that the following parameters must be sent as hidden fields.<br/><br/>

&ltinput type="hidden" name="stripe_payment" value="1"/&gt <br/>
&ltinput type="hidden" name="actual_amount" value="1000"/&gt <br/>
&ltinput type="hidden" name="currency" value="usd"/&gt <br/>
&ltinput type="hidden" name="charge_description" value="Charge for Member Registration"/&gt <br/>
</p>

<h3>Example Form</h3>
<p>
<code>
&ltform action="{$current-url}/?debug" method="POST"&gt <br/>
			&ltscript
				src="https://checkout.stripe.com/checkout.js"
				class="stripe-button"
				data-key="{params/stripe-publishable-key}"
				data-amount="1000"
				data-name="bliss.org"
				data-description="Donation"
				data-image="https://stripe.com/img/documentation/checkout/marketplace.png"
				data-locale="auto"
				data-panel-label="{{amount}}"
				data-currency="GBP"
				data-zip-code="true"&gt
			&lt/script&gt <br/><br/>
			&ltinput type="hidden" name="stripe_payment" value="1"/&gt &lt!-- Required --&gt <br/>
			&ltinput type="hidden" name="actual_amount" value="1000"/&gt &lt!-- Required --&gt <br/>
			&ltinput type="hidden" name="currency" value="usd"/&gt &lt!-- Required --&gt <br/>
			&ltinput type="hidden" name="charge_description" value="Charge for Member Registration"/&gt &lt!-- Required --&gt <br/>

			&ltinput type="hidden" name="fields[7][8][paid-status]" value="status"/&gt &lt!-- Update Entry --&gt <br/>
			
			&ltinput type="hidden" name="sections[8][amount]" value="amount"/&gt &lt!-- New Entry --&gt <br/>
		&lt/form&gt
		</code>
</p>
 
';



		return implode("\n", $docs);
	}

}
