# Symphony CMS - Stripe Payments Extension

## Installation

- Upload the 'stripe_payments' folder to your Symphony 'extensions' folder.
- Enable it by selecting the "Stripe Payments", choose Enable from the with-selected menu, then click Apply.

## Apply the event to the page

- Apply the event "Stripe Payments: Save data" to the page where you want to use Stripe payments

## Usage

###Overview
This event is used to deal with data returned by Stripe’s Checkout via API and Reconciling/Adding of data to the sections. It does the following:

Saves the transaction details to the log.
Reconciles the data return by Stripe Checkout with matching fields with the given allowed parameters.
Add/Update multiple entries across sections.
Outputs data/status of payment as XML.
For the event to work you’ll need to assign this event to the page where your stripe form action is set to and also you need to set stripe_payment hidden field in your form

###Transaction Logs
The transaction logs store the following data:

Customer Email
Payment Date
Amount Paid
Payment Status
Inserting Data into Sections as New Records
In order to add records as a new entry you need to follow the format given below

<input type="hidden" name="sections[SECTION_ID_THAT_YOU_WISH_TO_ADD_NEW_RECORD][FIELD_HANDLE_OF_THE_SECTION]" value="VALUE_TO_ADD"/>
In the above example, if you want to insert a value that returns from Stripe checkout instead of your custom value, you must add the correct parameter from the list of allowed parameters(Refer allowed parameters section).

###Reconciling Data
To save any of the Stripe checkout data to a corresponding entry, you need to include the field in the following format.

<input type="hidden" name="fields[SECTION_ID_OF_THE_ENTRY_TO_BE_UPDATED][ENTRY_ID_TO_BE_UPDATED][ENTRY_HANDLE]" value="VALUE_TO_BE_UPDATED"/>
In the above example, if you want to update a value that returns from Stripe checkout instead of your custom value, you must add the correct parameter from the list of allowed parameters(Refer allowed parameters section).

###XML Output
Data returned from Stripe checkout and corresponding messages are included as the <stripe-payments-response> node in the XML output for use in frontend pages.

List of Allowed Parameters
transaction_id
amount
currency
description
status
paid
Essentials
Please note that the following parameters must be sent as hidden fields.

<input type="hidden" name="stripe_payment" value="1"/> 
<input type="hidden" name="actual_amount" value="1000"/> 
<input type="hidden" name="currency" value="usd"/> 
<input type="hidden" name="charge_description" value="Charge for Member Registration"/> 
Example Form
<form action="{$current-url}/?debug" method="POST"> 
<script src="https://checkout.stripe.com/checkout.js" class="stripe-button" data-key="{params/stripe-publishable-key}" data-amount="1000" data-name="bliss.org" data-description="Donation" data-image="https://stripe.com/img/documentation/checkout/marketplace.png" data-locale="auto" data-panel-label="{{amount}}" data-currency="GBP" data-zip-code="true"> </script> 

<input type="hidden" name="stripe_payment" value="1"/> <!-- Required --> 
<input type="hidden" name="actual_amount" value="1000"/> <!-- Required --> 
<input type="hidden" name="currency" value="usd"/> <!-- Required --> 
<input type="hidden" name="charge_description" value="Charge for Member Registration"/> <!-- Required --> 
<input type="hidden" name="fields[7][8][paid-status]" value="status"/> <!-- Update Entry --> 
<input type="hidden" name="sections[8][amount]" value="amount"/> <!-- New Entry --> 
</form>