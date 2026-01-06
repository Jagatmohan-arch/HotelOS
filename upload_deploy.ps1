$user = 'uplfveim_deploy'
$pass = 'jm@HS10$$'
$ftpUrl = "ftp://hotelos.needkit.in/public_html/deploy.zip"
$localFile = "c:\Users\HP\Documents\HotelOS\deploy.zip"

Write-Host "Starting Upload to $ftpUrl..."

$webclient = New-Object System.Net.WebClient
$webclient.Credentials = New-Object System.Net.NetworkCredential($user, $pass)

try {
    $webclient.UploadFile($ftpUrl, $localFile)
    Write-Host "SUCCESS: deploy.zip uploaded."
} catch {
    Write-Host "ERROR: $($_.Exception.Message)"
    # Try alternative path if public_html is root
    try {
        $ftpUrl = "ftp://hotelos.needkit.in/deploy.zip"
        Write-Host "Retrying with path $ftpUrl..."
        $webclient.UploadFile($ftpUrl, $localFile)
        Write-Host "SUCCESS: deploy.zip uploaded to root."
    } catch {
        Write-Host "ERROR RETRY: $($_.Exception.Message)"
        exit 1
    }
}
