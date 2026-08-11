<#
    Build the theme and plugin zips.

    NEVER use Compress-Archive here. Windows PowerShell 5.1 writes Windows separators inside
    the archive ("simple-bangla\style.css"), and the ZIP specification requires "/". WordPress
    then extracts one file whose entire name is that string, at the archive root, and reports
    "The package could not be installed. The theme is missing the style.css stylesheet." —
    a true statement about a package that in fact contains everything.

    ZipFile::CreateFromDirectory has the same fault, so this walks the tree and writes each
    entry by hand with the separator replaced.
#>
param(
    [Parameter( Mandatory = $true )] [string] $Root,
    [Parameter( Mandatory = $true )] [string] $Out
)

Add-Type -AssemblyName System.IO.Compression
Add-Type -AssemblyName System.IO.Compression.FileSystem

# Editor and OS cruft that must never reach a shop.
$skip = @( '.git', '.github', 'node_modules', '.DS_Store', 'Thumbs.db', 'ehthumbs.db', 'Desktop.ini' )

function New-Package {
    param( [string] $SourceDir, [string] $ZipPath )

    $slug = Split-Path $SourceDir -Leaf
    if ( Test-Path $ZipPath ) { Remove-Item $ZipPath -Force }

    $files = Get-ChildItem -LiteralPath $SourceDir -Recurse -File | Where-Object {
        $rel = $_.FullName.Substring( $SourceDir.Length + 1 )
        $parts = $rel -split '\\'
        -not ( $parts | Where-Object { $skip -contains $_ } ) -and $_.Name -notlike '*.swp' -and $_.Name -notlike '*~'
    } | Sort-Object FullName

    $zip = [System.IO.Compression.ZipFile]::Open( $ZipPath, [System.IO.Compression.ZipArchiveMode]::Create )
    try {
        foreach ( $f in $files ) {
            $rel = $f.FullName.Substring( $SourceDir.Length + 1 ).Replace( '\', '/' )
            $entryName = "$slug/$rel"
            [void][System.IO.Compression.ZipFileExtensions]::CreateEntryFromFile(
                $zip, $f.FullName, $entryName, [System.IO.Compression.CompressionLevel]::Optimal )
        }
    } finally {
        $zip.Dispose()
    }

    $size = [math]::Round( ( Get-Item $ZipPath ).Length / 1KB, 1 )
    Write-Output ( "{0}  {1} files  {2} KB" -f ( Split-Path $ZipPath -Leaf ), $files.Count, $size )
}

if ( -not ( Test-Path $Out ) ) { New-Item -ItemType Directory -Force $Out | Out-Null }

# Versions come from the headers, so a package can never be named for a version it does not carry.
$themeDir = Join-Path $Root 'simple-bangla'
$themeVer = ( Select-String -Path ( Join-Path $themeDir 'style.css' ) -Pattern '^Version:\s*(.+)$' ).Matches[0].Groups[1].Value.Trim()

$pluginDir = Join-Path $Root 'simple-bangla-cms'
$pluginVer = ( Select-String -Path ( Join-Path $pluginDir 'simple-bangla-cms.php' ) -Pattern '^\s*\*\s*Version:\s*(.+)$' ).Matches[0].Groups[1].Value.Trim()

New-Package -SourceDir $themeDir  -ZipPath ( Join-Path $Out "simple-bangla-$themeVer.zip" )
New-Package -SourceDir $pluginDir -ZipPath ( Join-Path $Out "simple-bangla-cms-$pluginVer.zip" )
