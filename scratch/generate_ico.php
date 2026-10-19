<?php
/**
 * Generate standard 32x32 32-bit ICO file
 */
$width = 32;
$height = 32;

// ICO Header: 6 bytes
// Reserved (0), Type (1 = ICO), Count (1)
$header = pack('vvv', 0, 1, 1);

$xorSize = $width * $height * 4;
// AND mask: 1 bit per pixel, rows padded to 32-bit boundary (4 bytes for 32 pixels)
$andMaskRowSize = (int)ceil($width / 32) * 4;
$andSize = $andMaskRowSize * $height;
$imageSize = 40 + $xorSize + $andSize; // 40 byte BITMAPINFOHEADER + XOR + AND
$offset = 6 + 16; // 22 bytes

// ICO Directory Entry: 16 bytes
$entry = pack('CCCCvvVV', $width, $height, 0, 0, 1, 32, $imageSize, $offset);

// BITMAPINFOHEADER: 40 bytes
// Height is doubled in ICO BMP format (64 = 32 image + 32 mask)
$bih = pack('VVVvvVVVVVV', 40, $width, $height * 2, 1, 32, 0, $xorSize + $andSize, 0, 0, 0, 0);

// Generate 32x32 pixel data (bottom-up in Windows BMP format)
// Let's draw a stylish 32x32 car silhouette
// Colors:
// Background: #121620 -> B: 0x20, G: 0x16, R: 0x12, A: 0xFF
// Crimson:    #E10600 -> B: 0x00, G: 0x06, R: 0xE1, A: 0xFF
// Wheel Gray: #8B99AE -> B: 0xAE, G: 0x99, R: 0x8B, A: 0xFF
// Cockpit:    #222A38 -> B: 0x38, G: 0x2A, R: 0x22, A: 0xFF
// White:      #FFFFFF -> B: 0xFF, G: 0xFF, R: 0xFF, A: 0xFF

$cBg = [0x20, 0x16, 0x12, 0xFF];
$cRed = [0x00, 0x06, 0xE1, 0xFF];
$cWheel = [0xAE, 0x99, 0x8B, 0xFF];
$cCockpit = [0x38, 0x2A, 0x22, 0xFF];
$cWhite = [0xFF, 0xFF, 0xFF, 0xFF];

// Prepare a 32x32 grid (0,0 is top-left, we'll invert Y when packing for BMP bottom-up)
$grid = array_fill(0, $height, array_fill(0, $width, $cBg));

// Draw rounded badge border
for ($y = 0; $y < $height; $y++) {
    for ($x = 0; $x < $width; $x++) {
        // Corner clip for rounded badge
        $isCorner = ($x <= 1 && $y <= 1) || ($x >= 30 && $y <= 1) || ($x <= 1 && $y >= 30) || ($x >= 30 && $y >= 30);
        if ($isCorner) {
            $grid[$y][$x] = [0, 0, 0, 0]; // transparent
        } elseif ($x === 0 || $x === 31 || $y === 0 || $y === 31) {
            $grid[$y][$x] = [0x38, 0x2A, 0x22, 0xFF]; // border
        }
    }
}

// Draw sports car silhouette (Y from top to bottom: 12 to 24)
// Roof/cockpit: Y 12-16, X 11-20
for ($y = 12; $y <= 16; $y++) {
    for ($x = 11; $x <= 20; $x++) {
        $grid[$y][$x] = $cCockpit;
    }
}
// Car body: Y 16-21, X 4-27
for ($y = 16; $y <= 20; $y++) {
    for ($x = 4; $x <= 27; $x++) {
        $grid[$y][$x] = $cRed;
    }
}
// Front slope
$grid[17][3] = $cRed;
$grid[18][2] = $cWhite; // headlight
$grid[18][3] = $cRed;
$grid[19][3] = $cRed;

// Rear spoiler / back
$grid[15][26] = $cRed;
$grid[15][27] = $cRed;

// Wheels: Y 20-23, front center X=9, rear center X=23
$wheelCoords = [
    [8, 20], [9, 20], [10, 20],
    [8, 21], [9, 21], [10, 21],
    [8, 22], [9, 22], [10, 22],
    [22, 20], [23, 20], [24, 20],
    [22, 21], [23, 21], [24, 21],
    [22, 22], [23, 22], [24, 22],
];
foreach ($wheelCoords as $coord) {
    $grid[$coord[1]][$coord[0]] = $cWheel;
}
$grid[21][9] = $cRed;
$grid[21][23] = $cRed;

// Pack bottom-up
$xorData = '';
$andData = '';
for ($y = $height - 1; $y >= 0; $y--) {
    $rowMask = 0;
    for ($x = 0; $x < $width; $x++) {
        $color = $grid[$y][$x];
        $xorData .= pack('CCCC', $color[0], $color[1], $color[2], $color[3]);
        if ($color[3] === 0) {
            $rowMask |= (1 << (31 - $x));
        }
    }
    $andData .= pack('N', $rowMask);
}

$icoData = $header . $entry . $bih . $xorData . $andData;

$targetFile = dirname(__DIR__) . '/favicon.ico';
file_put_contents($targetFile, $icoData);
echo "Successfully written favicon.ico (" . strlen($icoData) . " bytes) to: " . $targetFile . "\n";
