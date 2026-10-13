$source = 'C:\xampp\htdocs\Gym Manager\Final Edit - Objectives Updated.docx'
$destination = 'C:\Users\lamtu\Desktop\Final Edit - Objectives Updated.docx'
$pdf = 'C:\xampp\htdocs\Gym Manager\objectives-review.pdf'

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
    $document.ExportAsFixedFormat([string]$pdf, 17, $false, 0, 0, 0, 0, $false, $true, 0, $true, $true, $true, $false, $false)
    $document.Close()
}
finally {
    $word.Quit()
}

Get-Item -LiteralPath $destination, $pdf | Select-Object FullName, Length, LastWriteTime
