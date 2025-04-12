# Create directories if they don't exist
New-Item -ItemType Directory -Force -Path "public\images"
New-Item -ItemType Directory -Force -Path "public\img\templates"
New-Item -ItemType Directory -Force -Path "public\img\documents"

# Hero SVG
$heroSvg = @'
<svg xmlns="http://www.w3.org/2000/svg" width="800" height="600" viewBox="0 0 800 600">
  <rect width="100%" height="100%" fill="#4F46E5"/>
  <text x="50%" y="50%" font-family="Arial" font-size="48" fill="white" text-anchor="middle">CVHub Hero</text>
</svg>
'@
$heroSvg | Out-File "public\images\hero-cv.svg"

# Template images
$templateNames = @("basic", "modern", "professional", "creative")
foreach ($name in $templateNames) {
    $displayName = $name -replace "-", " "
    $displayName = (Get-Culture).TextInfo.ToTitleCase($displayName)
    Invoke-WebRequest -Uri "https://dummyimage.com/800x600/4F46E5/FFFFFF.png&text=$displayName+CV+Template" -OutFile "public\img\templates\$name.jpg"
}

# Document images
$documentNames = @(
    "birth-certificate",
    "math-study",
    "id-form",
    "research-guide",
    "science-notes",
    "official-letter",
    "cv-template",
    "arabic-notes"
)
foreach ($name in $documentNames) {
    $displayName = $name -replace "-", " "
    $displayName = (Get-Culture).TextInfo.ToTitleCase($displayName)
    Invoke-WebRequest -Uri "https://dummyimage.com/400x300/4F46E5/FFFFFF.png&text=$displayName" -OutFile "public\img\documents\$name.jpg"
}

# Baridi Mob logo
Invoke-WebRequest -Uri "https://dummyimage.com/200x100/4F46E5/FFFFFF.png&text=Baridi+Mob" -OutFile "public\images\baridi-mob.png" 