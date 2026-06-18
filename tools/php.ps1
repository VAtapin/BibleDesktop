param(
    [Parameter(ValueFromRemainingArguments = $true)]
    [string[]] $Arguments
)

& php `
    -d extension=curl `
    -d extension=openssl `
    -d extension=fileinfo `
    -d extension=intl `
    -d extension=mbstring `
    -d extension=pdo_pgsql `
    -d extension=pgsql `
    -d extension=pdo_sqlite `
    -d extension=sqlite3 `
    -d extension=zip `
    @Arguments
