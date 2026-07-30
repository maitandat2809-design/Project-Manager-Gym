$ftpServer = "ftp://ftpupload.net/htdocs/config/db.php"
$ftpUser = "if0_42164663"
$ftpPass = "d9mAzS8Q7XWF"

$filePath = "c:\xampp\htdocs\Gym Manager\db_infinity.php"

$webclient = New-Object System.Net.WebClient
$webclient.Credentials = New-Object System.Net.NetworkCredential($ftpUser, $ftpPass)

try {
    $webclient.UploadFile($ftpServer, $filePath)
    Write-Output "Successfully updated db.php"
} catch {
    Write-Output "Failed to upload db.php. Error: $($_.Exception.Message)"
}
