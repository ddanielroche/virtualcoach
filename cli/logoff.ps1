param(
	[Parameter(Mandatory=$true)][string]$server = "a",
	[Parameter(Mandatory=$true)][string]$username = "b"
)

$result = qwinsta /server:$server $username
Write-Output $result
ForEach($line in $result[1..$result.Count]) {
	# Close Active User Session
	if ($line[48] -eq "A") {
		$sessionID = $( $line -replace '\s+', ' ' ).split()[3]
		rwinsta $sessionID /server:$server
	}
	# Close Disconnected User Session
	elseif ($line[48] -eq "D") {
		$sessionID = $( $line -replace '\s+', ' ' ).split()[2]
		rwinsta $sessionID /server:$server
	}
}