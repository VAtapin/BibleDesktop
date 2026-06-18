param(
    [Parameter(ValueFromRemainingArguments = $true)]
    [string[]] $Arguments
)

$env:COMPOSER_HOME = Join-Path (Resolve-Path "$PSScriptRoot\..") '.composer'

& "$PSScriptRoot\php.ps1" composer.phar @Arguments
