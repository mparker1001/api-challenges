<?php
// (c)2012 Rackspace Hosting. See COPYING for license.
// This script will create a clone of the server as specified in the $server_id variable

//Include necessary libraries
require('rackspace.inc');

// Get authentication credentials in auth.inc.php file. Credentials are set through the variables $username, $tenant, and $apiKey
include('auth.inc.php');

//Set server ID (uuid) that you want to use for creating a clone
$server_id = '80128595-d6be-4c5c-ab60-1addbd218534';

//Set image creation timeout in seconds. If the image creation takes longer than this value then the script will abort
$image_timeout = 1800;

//Set suffix of server name clone. Server clone name will be format of <origservername>-$suffix.
$clone_suffix = "clone";

print "Authenticating...\n";

$conn = new OpenCloud\Rackspace(
     'https://identity.api.rackspacecloud.com/v2.0',
     array(
         'username' => $username,
         'tenantName' => $tenant,
         'apiKey' => $apiKey
     ));

$compute = $conn->Compute('cloudServersOpenStack', 'DFW');
$server = $compute->Server($server_id);
$server_name = $server->name;
$flavor_id = $server->flavor;

//Set image name & create the image
$timestamp = date("Ymdhi");
$image_name = $server_name . "-" . $timestamp;
print "Creating the image...\n";
$server->CreateImage($image_name);

$ilist = $compute->ImageList(TRUE, array('name'=>$image_name));
while($image = $ilist->Next()) {
    $image_id = $image->id;
}

//Loop through image creation process until it is complete
$image_time = 0;
do {
	$image = $compute->Image($image_id);
	echo "Image creation process: " . $image->progress . "% complete\r";
	if ($image->progress == "100") {
		echo "\nImage creation complete!\n";
		break;
	}
	sleep(30);
	$image_time = $image_time + 30;
	if ( $image_time >= $image_timeout ) {
		die("Image Timeout Exceeded");
	}
} while ($image->progress != "100");

//Create the server from the image
$newservername = $server_name . "-" . $clone_suffix;
$server = $compute->Server();
print "Creating the server...\n";
$server->Create(array(
	'name' => $newservername,
	'flavor' => $compute->Flavor($flavor_id ),
	'image' => $compute->ImageList(TRUE,array('name'=>$image_name))->Next()
));
$server->WaitFor('ACTIVE', 300, 'progress');
print "\nDone\n";
print $newservername . " Info\n";
print "IP Address: " . $server->accessIPv4 . "\n";
print "Root Password: " . $server->adminPass . "\n";

// callback function for WaitFor
function progress($server) {
    printf("%s:%-8s %3d%% complete\r",
        $server->name, $server->status, $server->progress);
}
?>
