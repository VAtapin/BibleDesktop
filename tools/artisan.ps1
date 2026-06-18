param(
    [Parameter(ValueFromRemainingArguments = $true)]
    [string[]] $Arguments
)

& "$PSScriptRoot\php.ps1" artisan @Arguments
