$ftpServer = "ftp://ftpupload.net/htdocs/reset_pass.php"
$ftpUser = "if0_42164663"
$ftpPass = "d9mAzS8Q7XWF"

$filePath = "c:\xampp\htdocs\Gym Manager\reset_pass.php"

$webclient = New-Object System.Net.WebClient
$webclient.Credentials = New-Object System.Net.NetworkCredential($ftpUser, $ftpPass)

try {
    $webclient.UploadFile($ftpServer, $filePath)
    Write-Output "Successfully uploaded reset_pass.php"
} catch {
    Write-Output "Failed to upload reset_pass.php. Error: $($_.Exception.Message)"
}
