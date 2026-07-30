$ftpServer = "ftp://ftpupload.net/htdocs/"
$ftpUser = "if0_42164663"
$ftpPass = "d9mAzS8Q7XWF"

function Upload-File ($filePath) {
    $fileName = Split-Path $filePath -Leaf
    $uri = "$ftpServer$fileName"
    
    Write-Output "Uploading $fileName to $uri..."
    
    $webclient = New-Object System.Net.WebClient
    $webclient.Credentials = New-Object System.Net.NetworkCredential($ftpUser, $ftpPass)
    
    try {
        $webclient.UploadFile($uri, $filePath)
        Write-Output "Successfully uploaded $fileName"
    } catch {
        Write-Output "Failed to upload $fileName. Error: $($_.Exception.Message)"
    }
}

Upload-File "c:\xampp\htdocs\Gym Manager\setup.php"
Upload-File "c:\xampp\htdocs\GymManager_SourceCode.zip"
Upload-File "c:\xampp\htdocs\Gym Manager\gym_manager_export.sql"
