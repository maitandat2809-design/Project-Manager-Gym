$source = 'C:\xampp\htdocs\Gym Manager\Final Edit - Objectives 24-Week Schedule Updated.docx'
$destination = 'C:\Users\lamtu\Desktop\Final Edit - Objectives 24-Week Schedule Updated.docx'
Copy-Item -LiteralPath $source -Destination $destination -Force
$word = New-Object -ComObject Word.Application
$word.Visible = $false
$word.DisplayAlerts = 0
try {
    $document = $word.Documents.Open($destination)
    $document.Repaginate()
    $null = $document.Fields.Update()
    $document.Repaginate()
    $document.Save()
    $document.Close()
}
finally {
    $word.Quit()
}
Get-Item -LiteralPath $destination | Select-Object FullName, Length, LastWriteTime
