$source = 'C:\xampp\htdocs\Gym Manager\Project Gym Manager - Chapter 10 Updated.docx'
$destination = 'D:\Project final computing\Project Gym Manager - Chapter 10 Updated.docx'
$pdf = 'C:\xampp\htdocs\Gym Manager\chapter10-review.pdf'

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
    $document.ExportAsFixedFormat($pdf, 17)
    $document.Close()
}
finally {
    $word.Quit()
}
Get-Item -LiteralPath $destination | Select-Object FullName, Length, LastWriteTime
if (Test-Path -LiteralPath $pdf) { Get-Item -LiteralPath $pdf | Select-Object FullName, Length, LastWriteTime }
