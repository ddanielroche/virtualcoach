param(
[Parameter(Mandatory=$true)][string]$server = "a",
[Parameter(Mandatory=$true)][string]$username = "b"
)

$rdp = $(qwinsta /server:$server $username | Select-String -Pattern 'rdp')
if ($rdp) {
	$rdp = $($rdp -replace '\s+', ' ').split()[1]
	rwinsta $rdp /server:$server
}
