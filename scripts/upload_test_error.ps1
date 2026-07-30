$ftpServer = "ftp://ftpupload.net/htdocs/test_error.php"
$ftpUser = "if0_42164663"
$ftpPass = "d9mAzS8Q7XWF"

$filePath = "c:\xampp\htdocs\Gym Manager\test_error.php"

$webclient = New-Object System.Net.WebClient
$webclient.Credentials = New-Object System.Net.NetworkCredential($ftpUser, $ftpPass)

try {
    $webclient.UploadFile($ftpServer, $filePath)
    Write-Output "Successfully uploaded test_error.php"
} catch {
    Write-Output "Failed to upload test_error.php. Error: $($_.Exception.Message)"
}
