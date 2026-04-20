param(
    [string]$BaseUrl = 'http://127.0.0.1:8000',
    [Parameter(Mandatory = $true)]
    [string]$Email,
    [Parameter(Mandatory = $true)]
    [string]$Password,
    [string]$TargetPath = '/guru',
    [int]$LoginRedirect = 10,
    [int]$TargetRedirect = 0
)

$ErrorActionPreference = 'Stop'
$ProgressPreference = 'SilentlyContinue'

$session = New-Object Microsoft.PowerShell.Commands.WebRequestSession

$loginPage = Invoke-WebRequest -Uri "$BaseUrl/login" -WebSession $session -UseBasicParsing
$token = [regex]::Match($loginPage.Content, 'name="_token"\s+value="([^"]+)"').Groups[1].Value

if ([string]::IsNullOrWhiteSpace($token)) {
    Write-Output 'NO_CSRF_TOKEN'
    exit 1
}

$body = @{
    _token = $token
    email = $Email
    password = $Password
}

try {
    $loginResp = Invoke-WebRequest -Uri "$BaseUrl/login" -Method Post -WebSession $session -Body $body -MaximumRedirection $LoginRedirect -UseBasicParsing
    Write-Output ('LOGIN_STATUS=' + [int]$loginResp.StatusCode)
} catch {
    if ($_.Exception.Response) {
        Write-Output ('LOGIN_STATUS=' + [int]$_.Exception.Response.StatusCode.value__)
    } else {
        Write-Output 'LOGIN_REQUEST_FAILED'
    }
}

try {
    $targetResp = Invoke-WebRequest -Uri "$BaseUrl$TargetPath" -WebSession $session -MaximumRedirection $TargetRedirect -UseBasicParsing
    Write-Output ('TARGET_STATUS=' + [int]$targetResp.StatusCode)
} catch {
    if ($_.Exception.Response) {
        $statusCode = [int]$_.Exception.Response.StatusCode.value__
        Write-Output ('TARGET_STATUS=' + $statusCode)
        if ($_.Exception.Response.Headers.Location) {
            Write-Output ('TARGET_LOCATION=' + ($_.Exception.Response.Headers.Location -join ','))
        }
    } else {
        Write-Output 'TARGET_REQUEST_FAILED'
    }
}
