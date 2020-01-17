<?php

$username = $_GET['username'];
$machine = $_GET['machine'];

$filelines = file('template.rdp');
$filelines[] = "\nusername:s:cursosaula21\\$username";
$filelines[] = "\nfull address:s:$machine";

header('Content-disposition: attachment; filename=VirtualCoach.rdp');
header('Content-type: application/rdp');
foreach ($filelines as $line) {
	echo $line;
}
exit;
?>