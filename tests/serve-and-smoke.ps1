$ErrorActionPreference = 'Continue'
$proc = Start-Process -FilePath 'php' -ArgumentList '-S localhost:8003 -t public public/router-stub.php' -WorkingDirectory 'D:\project\Website\Native\my-seled' -PassThru -WindowStyle Hidden
Start-Sleep -Seconds 4
php tests/smoke.php
$code = $LASTEXITCODE
Stop-Process -Id $proc.Id -Force -ErrorAction SilentlyContinue
exit $code
