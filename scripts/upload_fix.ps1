$ftpServer = "ftp://ftpupload.net/htdocs/"
$ftpUser = "if0_42164663"
$ftpPass = "d9mAzS8Q7XWF"

$filePath = "c:\xampp\htdocs\Gym Manager\fix_paths.php"
$fileName = Split-Path $filePath -Leaf
$uri = "$ftpServer$fileName"

$webclient = New-Object System.Net.WebClient
$webclient.Credentials = New-Object System.Net.NetworkCredential($ftpUser, $ftpPass)

try {
    $webclient.UploadFile($uri, $filePath)
    Write-Output "Successfully uploaded $fileName"
} catch {
    Write-Output "Failed to upload $fileName. Error: $($_.Exception.Message)"
}
