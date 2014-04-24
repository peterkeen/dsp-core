// ## system.apps.create.js
// This script will run as a handler for "system.apps.create" which is triggered when a new "app" record is created
//
// ### Notes
// This example shows how to loop through the users being
// returned and augment the outbound result set.
//
// The script merely prepends the api_name of inbound data with the text "user_"
//
// In addition, the entire script is driven by the [UnderscoreJS](http://underscorejs.org/)
// "each" function. [UnderscoreJS](http://underscorejs.org/) is available for all scripts to use.
//

//
// ### Recommended Pattern
// We recommend the following pattern for your scripts.
//
// A function (you can use whatever construct you desire) is defined to process a single record.
//
// Next, check to see if we're dealing with an array, or a single record of data. This pattern will work for both inbound and outbound scripts.
//
// Should an exception be thrown, the system will stop the REST call in progress by sending the exception back to the client. The [admin application](/launchpad/index.html) will display this to the user. Handle accordingly in your apps.
//

/**
 * Performs the operations described above on the passed in record.
 *
 * @param record
 */
function updateRecord(record) {
	//	Make the api_name prefixed with "user_"
	record.api_name = 'user_' + record.api_name;
}

//	Array of records?
if (event.record) {
	_.each(event.record, function(record, index, list) {
		updateRecord(record);
	});
//	Single record?
} else if (event.last_name) {
	updateRecord(event);
//	Bogus...
} else {
	throw "Unrecognized Data Format";
}