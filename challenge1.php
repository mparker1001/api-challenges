<?php
// (c)2012 Rackspace Hosting. See COPYING for license.
// This script will create a user specified number of CentOS 6.3 servers in the DFW datacenter

require('rackspace.inc');

// Get authentication credentials in auth.inc.php file. Credentials are set through the variables $username, $tenant, and $apiKey
include('auth.inc.php');

// Set number of servers to build
$num_servers = "3";

// Set server name prefix
$name_prefix = "web";

print "Authenticating...\n";

$conn = new OpenCloud\Rackspace(
     'https://identity.api.rackspacecloud.com/v2.0',
     array(
         'username' => $username,
         'tenantName' => $tenant,
         'apiKey' => $apiKey
     ));

$compute = $conn->Compute('cloudServersOpenStack', 'DFW');
$server = $compute->Server();

print "Creating the servers...\n";
for ($i=1; $i<=$num_servers; $i++) {
	$servername = $name_prefix . $i;
	$server->Create(array(
    	'name' => $servername,
    	'flavor' => $compute->Flavor(2),
    	'image' => $compute->ImageList(TRUE,array('name'=>'CentOS 6.3'))->Next()
    	));
	$server->WaitFor('ACTIVE', 300, 'progress');
	print "Done\n";
	print $servername . " Info\n";
	print "IP Address: " . $server->accessIPv4 . "\n";
	print "Root Password: " . $server->adminPass . "\n";
}

// callback function for WaitFor
function progress($server) {
    printf("%s:%-8s %3d%% complete\n",
        $server->name, $server->status, $server->progress);
}
