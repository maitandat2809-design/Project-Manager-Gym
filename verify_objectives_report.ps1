$path = 'C:\Users\lamtu\Desktop\Final Edit - Objectives Updated.docx'
$word = New-Object -ComObject Word.Application
$word.Visible = $false
$word.DisplayAlerts = 0
try {
    $document = $word.Documents.Open($path, $false, $true)
    $document.Repaginate()
    Write-Output ("Pages: " + $document.ComputeStatistics(2))
    foreach ($paragraph in $document.Paragraphs) {
        $text = $paragraph.Range.Text.Trim([char]13, [char]7).Trim()
        if ($text -match '^1\.3(\.1)?(\s|$)' -or $text -match '^1\.4\s') {
            $page = $paragraph.Range.get_Information(3)
            Write-Output ("Page {0}: {1}" -f $page, $text)
        }
    }
    $document.Close($false)
}
finally {
    $word.Quit()
}
