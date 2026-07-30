$ftpServer = "ftp://ftpupload.net/htdocs/assets/css/style.css"
$ftpUser = "if0_42164663"
$ftpPass = "d9mAzS8Q7XWF"

$filePath = "c:\xampp\htdocs\Gym Manager\assets\css\style.css"

$webclient = New-Object System.Net.WebClient
$webclient.Credentials = New-Object System.Net.NetworkCredential($ftpUser, $ftpPass)

try {
    $webclient.UploadFile($ftpServer, $filePath)
    Write-Output "Successfully updated style.css"
} catch {
    Write-Output "Failed to upload style.css. Error: $($_.Exception.Message)"
}
