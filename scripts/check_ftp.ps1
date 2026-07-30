$ftpServer = "ftp://ftpupload.net/htdocs/"
$ftpUser = "if0_42164663"
$ftpPass = "d9mAzS8Q7XWF"

$request = [System.Net.FtpWebRequest]::Create($ftpServer)
$request.Credentials = New-Object System.Net.NetworkCredential($ftpUser, $ftpPass)
$request.Method = [System.Net.WebRequestMethods+Ftp]::ListDirectory

try {
    $response = $request.GetResponse()
    $reader = New-Object System.IO.StreamReader($response.GetResponseStream())
    $directories = $reader.ReadToEnd()
    $reader.Close()
    $response.Close()
    Write-Output "Root contents:"
    Write-Output $directories
} catch {
    Write-Output "Error: $($_.Exception.Message)"
}
