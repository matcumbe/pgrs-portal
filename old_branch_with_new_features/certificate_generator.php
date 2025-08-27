<?php
// certificate_generator.php

// FPDF and FPDI libraries
// Assuming FPDF is in 'assets/lib/fpdf/fpdf.php'
// Assuming FPDI is in 'assets/lib/FPDI-master/src/autoload.php'
// Adjust paths if necessary, relative to this file's location (WebGNIS Files directory)
require_once __DIR__ . '/assets/lib/fpdf/fpdf.php';
require_once __DIR__ . '/assets/lib/FPDI-master/src/autoload.php'; // For using existing PDFs as templates

require_once __DIR__ . '/users_config.php'; // For main DB connection and returnResponse()
// For station-specific data, we'll use config.php if it exists, similar to stations-api.php
// Autoload Composer dependencies
if (file_exists(__DIR__ . '/../vendor/autoload.php')) { // Changed path to look one level up
    require_once __DIR__ . '/../vendor/autoload.php';
} elseif (file_exists(__DIR__ . '/vendor/autoload.php')) { // Fallback to previous path
    require_once __DIR__ . '/vendor/autoload.php';
} else {
    // Fallback or error if vendor/autoload.php is not found
    error_log("Composer autoload not found. Please run 'composer install'. Searched in '" . __DIR__ . "/../vendor/autoload.php' and '" . __DIR__ . "/vendor/autoload.php'. QR Code functionality will be affected.");
}

use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\Writer\PngWriter;

if (file_exists(__DIR__ . '/config.php')) {
    require_once __DIR__ . '/config.php'; // For DB_HOST, DB_USER, DB_PASS, DB_NAME of station data
}

// Removed QR Code specific use statements as generation is temporarily disabled

// --- Helper function to fetch station details from the (potentially separate) station database ---
function getStationDetails($station_id, $station_type) {
    // These constants (DB_HOST, DB_USER, DB_PASS) will be from users_config.php,
    // as it's included first. This is acceptable because they are identical to those
    // in config.php for this specific setup.
    if (!defined('DB_HOST') || !defined('DB_USER') || !defined('DB_PASS')) {
        error_log("Station DB connection credentials (DB_HOST, DB_USER, DB_PASS) not defined. These should be set by users_config.php or config.php.");
        return null;
    }

    // Explicitly use 'webgnis_db' for station data, as intended by config.php,
    // to override the DB_NAME constant defined by users_config.php.
    $station_actual_db_name = 'webgnis_db';
    // DB_CHARSET is also defined in both config files with the same value ('utf8mb4'), so global one is fine.

    $mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, $station_actual_db_name);
    if ($mysqli->connect_error) {
        error_log("Station DB Connection Error (Attempted DB: '" . $station_actual_db_name . "'): " . $mysqli->connect_error .
                  " (Used DB_HOST: " . DB_HOST . ", DB_USER: " . DB_USER . ")");
        return null;
    }
    $mysqli->set_charset(DB_CHARSET); // Use the globally defined DB_CHARSET

    $table_name = '';
    // Mapping based on stations-api.php and user description
    switch (strtolower($station_type)) {
        case 'horizontal':
        case 'caap': // CAAP uses horizontal data table
            $table_name = 'hgcp_stations'; // Placeholder, confirm actual table name
            break;
        case 'vertical':
            $table_name = 'vgcp_stations'; // Placeholder, confirm actual table name
            break;
        case 'gravity':
            $table_name = 'grav_stations'; // Placeholder, confirm actual table name
            break;
        default:
            error_log("Unknown station type for detail fetching: $station_type");
            $mysqli->close();
            return null;
    }

    // Ensure station_id is properly escaped or use prepared statements if column name is dynamic (not typical)
    // Assuming station_id column is named 'station_id_column_name' - replace with actual
    // For security and correctness, prepared statements are best.
    // $stmt = $mysqli->prepare("SELECT * FROM `$table_name` WHERE `actual_station_id_col_name` = ?");
    // $stmt->bind_param("s", $station_id);


    // Using a direct query for now, assuming $station_id is safe and $table_name is controlled.
    // THIS SHOULD BE REPLACED WITH PREPARED STATEMENT and actual column name for station_id
    $sql = "SELECT * FROM `" . $mysqli->real_escape_string($table_name) . "` WHERE `station_id` = '" . $mysqli->real_escape_string($station_id) . "' LIMIT 1";
    $result = $mysqli->query($sql);
    
    $details = null;
    if ($result && $result->num_rows > 0) {
        $details = $result->fetch_assoc();
    } else {
        error_log("No details found for station_id: $station_id in table: $table_name. Error: " . $mysqli->error);
    }

    $result->free();
    $mysqli->close();
    return $details;
}

// --- Helper function to fetch user details for "Requesting Party" ---
function getRequestingPartyDetails($db, $request_id) {
    $sql = "SELECT r.user_id, u.name_on_certificate, u.user_type, id.full_name AS individual_name, cd.company_name 
            FROM requests r
            JOIN users u ON r.user_id = u.user_id
            LEFT JOIN individual_details id ON u.user_id = id.user_id AND u.user_type = 'individual'
            LEFT JOIN company_details cd ON u.user_id = cd.user_id AND u.user_type = 'company'
            WHERE r.request_id = :request_id";
    $stmt = $db->prepare($sql);
    $stmt->bindParam(':request_id', $request_id, PDO::PARAM_INT);
    $stmt->execute();
    $userData = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$userData) return 'N/A';

    if (!empty($userData['name_on_certificate'])) {
        return $userData['name_on_certificate'];
    }
    if ($userData['user_type'] === 'individual' && !empty($userData['individual_name'])) {
        return $userData['individual_name'];
    }
    if ($userData['user_type'] === 'company' && !empty($userData['company_name'])) {
        return $userData['company_name'];
    }
    return 'N/A'; // Fallback
}


// --- PDF Drawing Functions ---
function drawCertificateHeaderContent(FPDF $pdf, $transaction_code, $requestingParty, $purpose = "Reference", $orNo = null, $certificateDate = null) {
    // Date for the certificate (top right, as per samples)
    // The letterhead itself might have a date placeholder. This dynamic date will overlay it.
    // Sample shows "April 04, 2025"
    $displayDate = $certificateDate ? date("F d, Y", strtotime($certificateDate)) : date("F d, Y");
    $pdf->SetFont('Arial', '', 10); // Font for the date
    $pdf->SetXY(166, 37); // Approximate Y based on sample image's DENR logo and Bagong Pilipinas logo
    $pdf->Cell(30, 5, $displayDate, 0, 1, 'R'); // Right align in a 30mm width cell

    // "CERTIFICATE" title
    $pdf->SetFont('Arial', 'B', 14); // Slightly smaller than before, looks more like sample
    $pdf->SetXY(15, 40); // Adjust Y position to be below "National Mapping and Resource Information Authority"
    $pdf->Cell(190, 10, 'CERTIFICATE', 0, 1, 'C'); // Centered on page width (210mm - 20mm margins)
    
    // "To whom it may concern:"
    $pdf->SetFont('Arial', '', 10);
    $pdf->SetXY(15, 50); // Adjusted X and Y
    $pdf->Cell(0, 7, 'To whom it may concern:'); // Use 0 width to extend to right margin, but text is short
    $pdf->Ln(7); // Line break after this cell

    // Introductory paragraph
    $pdf->SetX(15);
    // Using a fixed width for MultiCell to control wrapping and ensure it doesn't overlap side elements
    $pdf->MultiCell(170, 5, '        This is to certify that according to the records on file in this office, the requested survey information is as follows:', 0, 'L');
    $pdf->Ln(5); // Space after the intro paragraph
}

// Y position where station data block will start. Adjusted based on new header content.
// This will be after the intro paragraph from drawCertificateHeaderContent.
define('STATION_DATA_START_Y', 68); // Approximate new starting Y for station data

// This function will now also include the "Requesting Party" block
function drawCertificateFooterContent(FPDF $pdf, $transaction_code, $requestingParty, $purpose, $orNo, $qrCodePath = null) {
    // Set Y position for the start of this block (Requesting Party, etc.)
    // This needs to be dynamic based on the height of the station data.
    // For now, let's assume an approximate Y. This will be the most complex part to get right
    // without knowing the exact height of the variable station data.
    // Let's try setting Y relative to bottom for more consistency, or after an estimated station data height.
    // For now, let's set it to a fixed position below where typical station data might end.
    // This Y will need adjustment based on actual content.
    $currentY = $pdf->GetY(); // Get Y after station data has been drawn.
    
    // If station data was short, ensure we don't overwrite. A minimum Y is needed.
    // Let's estimate station data might take up to ~80-100mm.
    // Header ends around Y=85. Station Data starts Y=90. So, this section starts after that.
    // Example: If station data section is drawn, $pdf->GetY() will be its end.
    // The accuracy text starts at a somewhat fixed position from bottom in samples.
    // The "Requesting Party" block in samples is above accuracy text.

    // --- Requesting Party, Purpose, OR No, Transaction No. block ---
    // This block is below accuracy standards and above QR/Signatory in sample
    $miscFontSize = 10;
    $pdf->SetY(200); // Approximate Y position for accuracy standards text from samples
    $pdf->SetFont('Arial', '', $miscFontSize);
    $pdf->SetX(15);
    $lineHeight = 5; // Reduced line height

    $pdf->Cell(35, $lineHeight, 'Requesting Party:', 0, 0, 'L');
    $pdf->SetFont('Arial', 'B', $miscFontSize);
    $pdf->Cell(145, $lineHeight, $requestingParty, 0, 1, 'L');
    
    $pdf->SetX(15);
    $pdf->SetFont('Arial', '', $miscFontSize);
    $pdf->Cell(35, $lineHeight, 'Purpose:', 0, 0, 'L');
    $pdf->SetFont('Arial', 'B', $miscFontSize);
    $pdf->Cell(145, $lineHeight, $purpose, 0, 1, 'L');
    
    $pdf->SetX(15);
    $pdf->SetFont('Arial', '', $miscFontSize);
    $pdf->Cell(35, $lineHeight, 'OR No.:', 0, 0, 'L');
    $pdf->SetFont('Arial', 'B', $miscFontSize);
    $pdf->Cell(145, $lineHeight, ($orNo ?: $transaction_code), 0, 1, 'L');
    
    $pdf->SetX(15);
    $pdf->SetFont('Arial', '', $miscFontSize);
    $pdf->Cell(35, $lineHeight, 'Transaction No.:', 0, 0, 'L');
    $pdf->SetFont('Arial', 'B', $miscFontSize);
    $pdf->Cell(145, $lineHeight, $transaction_code, 0, 1, 'L');
    $pdf->Ln(5); // Space after this block

    // --- Signatory and QR Code ---
    // QR Code (Left side) - Placeholder text
    $qrX = 15;
    $qrY = 222; 
    $qrSize = 15; 

    $pdf->SetXY($qrX, $qrY);
    if ($qrCodePath && file_exists($qrCodePath)) {
        $pdf->Image($qrCodePath, $qrX, $qrY, $qrSize, $qrSize, 'PNG');
    } else {
        $pdf->Cell($qrSize, $qrSize, '[QR CODE]', 1, 0, 'C'); // Placeholder if QR path is invalid
        if ($qrCodePath) { // Log if path was provided but file not found
            error_log("QR Code image not found at path: " . $qrCodePath);
        }
    }

    // Signatory details (Right side)
    $signatoryY = $qrY + ($qrSize - 15); // Attempt to vertically center signatory text block against QR image
    // Adjust signatory Y if it's too low or not aligning. Original Y was -55 from bottom (297-55 = 242)
    // Let's try to keep the signatory block roughly in its original position relative to the bottom, or slightly adjusted.
    // Original Y for signatory text block top was $pdf->SetY(-55) which is 242mm from top.
    // The QR code is at Y=222, height 30. So QR bottom is 252.
    // Let's place signatory details aligned with the QR code or slightly below its top.

    $pdf->SetY($qrY); // Align top of signatory text block with top of QR code image
    $pdf->SetX(120); 
    $pdf->SetFont('Arial', 'B', 10); // Using $miscFontSize defined earlier as 10
    $pdf->Cell(80,10,'ENGR. NICANDRO P. PARAYNO, MSc.',0,1,'C'); // Centered in its block
    $pdf->Ln(-2);
    $pdf->SetX(120);
    $pdf->SetFont('Arial','',10);
    $pdf->Cell(80,5,'OIC-Director, Mapping and Geodesy Branch',0,1,'C'); // Centered
}

// --- New Helper Function for "Label: Value" cells with mixed font weights ---
/**
 * Draws a cell with a label in normal font and a value in bold font.
 * Manages precise positioning and borders to simulate a single cell with mixed styles.
 */
$labelLineHeight = 7;

if (!function_exists('drawStyledLabelValueCell')) {
    function drawStyledLabelValueCell(FPDF $pdf, $label, $value, $cellWidth, $labelLineHeight, $border = 1, $valueSuffix = '', $align = 'L', $fill = false, $isLastInRow = false) {
        $fontSize = 10; // Standard font size for these cells
        $cellPadding = 0.5; // Internal padding for the cell content from the border

        $fixedPageXMargin = 15; // Standard page X margin

        $currentY = $pdf->GetY();
        // For X, if cellWidth is totalWidth, we assume it starts at the margin. Otherwise, use current GetX().
        $currentX = ($cellWidth == 180) ? $fixedPageXMargin : $pdf->GetX(); // 180 is $totalWidth

        if ($cellWidth == 180 && $pdf->GetX() != $fixedPageXMargin) {
            $pdf->SetX($fixedPageXMargin);
            $currentX = $fixedPageXMargin;
        }

        // Draw outer border
        if ($border) {
            $pdf->Rect($currentX, $currentY, $cellWidth, $labelLineHeight);
        }

        // Background fill if needed
        if ($fill) {
            // Caller should SetFillColor before calling this function if fill is true
            $pdf->Rect($currentX, $currentY, $cellWidth, $labelLineHeight, 'F');
        }

        $labelStr = $label . ': ';
        $valueStr = ($value !== null && $value !== '') ? $value . $valueSuffix : 'N/A';

        $pdf->SetFont('Arial', '', $fontSize);
        $labelWidth = $pdf->GetStringWidth($labelStr);

        $pdf->SetFont('Arial', 'B', $fontSize);
        $valueWidth = $pdf->GetStringWidth($valueStr);

        $contentTotalWidth = $labelWidth + $valueWidth;
        
        // Calculate starting X position for the content (label + value)
        $contentStartX = $currentX + $cellPadding; // Default for left alignment

        if ($align === 'C') {
            $contentStartX = $currentX + ($cellWidth - $contentTotalWidth) / 2;
        } elseif ($align === 'R') {
            $contentStartX = $currentX + $cellWidth - $contentTotalWidth - $cellPadding;
        }
        
        // Ensure contentStartX is not less than the cell's actual start X (e.g. if content is wider than cell)
        if ($contentStartX < $currentX + $cellPadding) {
            $contentStartX = $currentX + $cellPadding;
        }

        // Set text Y position with a small fixed top padding (e.g., 0.5mm)
        // This will result in more bottom padding if the $labelLineHeight is sufficiently large.
        $fixedTopPaddingMm = 0; 
        $textY = $currentY + $fixedTopPaddingMm;

        // Basic check to ensure text doesn't start above the cell or get pushed down too much if cell is tiny.
        if ($textY < $currentY) {
            $textY = $currentY;
        }
        // It is assumed that $labelLineHeight is tall enough to contain the text plus this top padding.
        // If $labelLineHeight is too small, the text might appear cut off at the bottom or overflow.
        // In such a case, $labelLineHeight (i.e., $dataLineHeight in calling functions) should be increased.

        $pdf->SetXY($contentStartX, $textY);

        $pdf->SetFont('Arial', '', $fontSize);
        // Calculate actual width for label part, considering potential overflow
        $availableForLabel = $cellWidth - (2 * $cellPadding) - $valueWidth;
        $labelPrintWidth = ($labelWidth > $availableForLabel && $availableForLabel > 0) ? $availableForLabel : $labelWidth;
        if ($contentTotalWidth > $cellWidth - (2*$cellPadding) ) $labelPrintWidth = $cellWidth - (2*$cellPadding) - $valueWidth;
        if ($labelPrintWidth < 0) $labelPrintWidth = 0;


        $pdf->Cell($labelPrintWidth, $labelLineHeight, $labelStr, 0, 0, 'L');

        $pdf->SetFont('Arial', 'B', $fontSize);
        // Calculate actual width for value part
        $valuePrintWidth = $cellWidth - (2 * $cellPadding) - $labelPrintWidth;
        if ($valuePrintWidth < 0) $valuePrintWidth = 0;

        $pdf->Cell($valuePrintWidth, $labelLineHeight, $valueStr, 0, 0, 'L');


        $pdf->SetFont('Arial', '', $fontSize); 
        
        if ($isLastInRow) {
            $pdf->SetXY($fixedPageXMargin, $currentY + $labelLineHeight); // Move to start of next line
        } else {
            // Move cursor to the start of the next potential cell in the same row
            $pdf->SetXY($currentX + $cellWidth, $currentY);
        }
    }
}

function drawHorizontalCaapData(FPDF $pdf, $stationData, $isCaap) {
    $pdf->SetY(STATION_DATA_START_Y);
    $pdf->SetX(15); // Ensure starting X for the whole block

    $fillColor = [220, 220, 220]; 
    $headerLineHeight = 5; 
    $dataLineHeight = 6; 
    $headerFontSize = 10; // Changed from 9 to 8 for tighter fit
    $dataFontSize = 10; 
    $border = 1;
    $totalWidth = 180; 
    
    // --- Province (Centered) ---
    // Ensure X is at margin before calling for a full-width cell
    $pdf->SetX(15);
    drawStyledLabelValueCell($pdf, 'Province', $stationData['province'] ?? null, $totalWidth, $dataLineHeight, $border, '', 'C', false, true);

    // --- Station Name (Centered) ---
    $pdf->SetX(15);
    $stationNameForCert = $stationData['station_name'] ?? null;
    drawStyledLabelValueCell($pdf, 'Station Name', $stationNameForCert, $totalWidth, $dataLineHeight, $border, '', 'C', false, true);

    // --- Grid: Order | Accuracy Class | Elevation ---
    $colWidthThird = $totalWidth / 3; 
    $pdf->SetX(15);
    drawStyledLabelValueCell($pdf, 'Order', $stationData['horizontal_order'] ?? null, $colWidthThird, $dataLineHeight, $border, '', 'L', false, false);
    drawStyledLabelValueCell($pdf, 'Accuracy Class', $stationData['accuracy_class'] ?? null, $colWidthThird, $dataLineHeight, $border, '', 'C', false, false);
    if ($isCaap) {
        $elevation = utf8_decode($stationData['ApprovedBy']) ?? 'N/A';    
        drawStyledLabelValueCell($pdf, 'Elevation', $elevation, $colWidthThird, $dataLineHeight, $border, '', 'L', false, true);
    } else {
        drawStyledLabelValueCell($pdf, 'Elevation', ' ', $colWidthThird, $dataLineHeight, $border, '', 'L', false, true);
    }

    // --- Grid: Island | Barangay | Municipality ---
    $pdf->SetX(15);
    drawStyledLabelValueCell($pdf, 'Island', $stationData['island_group'] ?? null, $colWidthThird, $dataLineHeight, $border, '', 'L', false, false);
    drawStyledLabelValueCell($pdf, 'Barangay', $stationData['barangay'] ?? null, $colWidthThird, $dataLineHeight, $border, '', 'C', false, false);
    drawStyledLabelValueCell($pdf, 'Municipality', $stationData['city'] ?? null, $colWidthThird, $dataLineHeight, $border, '', 'L', false, true);

    // --- PRS92 Coordinates (Grayed out, Centered) ---
    $pdf->SetX(15);
    $pdf->SetFont('Arial', 'B', $headerFontSize);
    $pdf->SetFillColor($fillColor[0], $fillColor[1], $fillColor[2]);
    $pdf->Cell($totalWidth, $headerLineHeight, 'PRS92 Coordinates', $border, 1, 'C', true);
    $pdf->SetX(15);
    
    $latitudeDMS = '';
    if ($stationData['latitude_degrees'] !== null && $stationData['latitude_minutes'] !== null && $stationData['latitude_seconds'] !== null) {
        $latitudeDMS = $stationData['latitude_degrees'] . utf8_decode('°') . ' ' . $stationData['latitude_minutes'] . '\'' . ' ' . $stationData['latitude_seconds'] . '" N';
    }
    
    $longitudeDMS = '';
    if ($stationData['longitude_degrees'] !== null && $stationData['longitude_minutes'] !== null && $stationData['longitude_seconds'] !== null) {
        $longitudeDMS = $stationData['longitude_degrees'] . utf8_decode('°') . ' ' . $stationData['longitude_minutes'] . '\'' . ' ' . $stationData['longitude_seconds'] . '" E';
    }
    
    drawStyledLabelValueCell($pdf, 'Latitude', $latitudeDMS, $colWidthThird, $dataLineHeight, $border, '', 'L', false, false);
    drawStyledLabelValueCell($pdf, 'Longitude', $longitudeDMS, $colWidthThird, $dataLineHeight, $border, '', 'C', false, false);
    drawStyledLabelValueCell($pdf, 'Ellipsoidal Height', $stationData['ellipsoidal_height'] ?? null, $colWidthThird, $dataLineHeight, $border, ' m', 'L', false, true);

    // --- PTM / PRS92 (NOT Grayed out, Centered) ---
    $pdf->SetX(15);
    $pdf->SetFont('Arial', 'B', $headerFontSize);
    $pdf->Cell($totalWidth, $headerLineHeight, 'PTM / PRS92', $border, 1, 'C', false); 
    $pdf->SetX(15);
    drawStyledLabelValueCell($pdf, 'Northing', $stationData['utm_northing'] ?? null, $colWidthThird, $dataLineHeight, $border, '', 'L', false, false);
    drawStyledLabelValueCell($pdf, 'Easting', $stationData['utm_easting'] ?? null, $colWidthThird, $dataLineHeight, $border, '', 'C', false, false);
    drawStyledLabelValueCell($pdf, 'Zone', $stationData['utm_zone'] ?? null, $colWidthThird, $dataLineHeight, $border, '', 'L', false, true);

    // --- UTM / PRS92 (NOT Grayed out, Centered) ---
    $pdf->SetX(15);
    $pdf->SetFont('Arial', 'B', $headerFontSize);
    $pdf->Cell($totalWidth, $headerLineHeight, 'UTM / PRS92', $border, 1, 'C', false); 
    $pdf->SetX(15);
    drawStyledLabelValueCell($pdf, 'Northing', $stationData['utm_y'] ?? null, $colWidthThird, $dataLineHeight, $border, '', 'L', false, false);
    drawStyledLabelValueCell($pdf, 'Easting', $stationData['utm_x'] ?? null, $colWidthThird, $dataLineHeight, $border, '', 'C', false, false);
    drawStyledLabelValueCell($pdf, 'Zone', $stationData['utm_zone_alt'] ?? null, $colWidthThird, $dataLineHeight, $border, '', 'L', false, true);
    
    // --- WGS84 Coordinates (Grayed out, Centered) ---
    $pdf->SetX(15);
    $pdf->SetFont('Arial', 'B', $headerFontSize);
    $pdf->SetFillColor($fillColor[0], $fillColor[1], $fillColor[2]);
    $pdf->Cell($totalWidth, $headerLineHeight, 'WGS84 Coordinates', $border, 1, 'C', true); 
    $pdf->SetX(15);
    
    $wgs84LatDMS = '';
    if ($stationData['wgs84_north_degrees'] !== null && $stationData['wgs84_north_minutes'] !== null && $stationData['wgs84_north_seconds'] !== null) {
        $wgs84LatDMS = $stationData['wgs84_north_degrees'] . utf8_decode('°') . ' ' . $stationData['wgs84_north_minutes'] . '\'' . ' ' . $stationData['wgs84_north_seconds'] . '" N';
    }
    
    $wgs84LonDMS = '';
    if ($stationData['wgs84_east_degrees'] !== null && $stationData['wgs84_east_minutes'] !== null && $stationData['wgs84_east_seconds'] !== null) {
        $wgs84LonDMS = $stationData['wgs84_east_degrees'] . utf8_decode('°') . ' ' . $stationData['wgs84_east_minutes'] . '\'' . ' ' . $stationData['wgs84_east_seconds'] . '" E';
    }
    
    drawStyledLabelValueCell($pdf, 'Latitude', $wgs84LatDMS, $colWidthThird, $dataLineHeight, $border, '', 'L', false, false);
    drawStyledLabelValueCell($pdf, 'Longitude', $wgs84LonDMS, $colWidthThird, $dataLineHeight, $border, '', 'C', false, false);
    drawStyledLabelValueCell($pdf, 'Ellip. Height', $stationData['itrf_ell_hgt'] ?? null, $colWidthThird, $dataLineHeight, $border, ' m', 'L', false, true);

    // --- Error Ellipse (NOT Bold, Centered) ---
    $pdf->SetX(15);
    $pdf->SetFont('Arial', '', $headerFontSize); 
    $pdf->Cell($totalWidth, $headerLineHeight, 'Error Ellipse', $border, 1, 'C', false); 

    // --- UTM / WGS84 (NOT Grayed out, Centered) ---
    $pdf->SetX(15);
    $pdf->SetFont('Arial', 'B', $headerFontSize); 
    $pdf->Cell($totalWidth, $headerLineHeight, 'UTM / WGS84', $border, 1, 'C', false); 
    $pdf->SetX(15);
    drawStyledLabelValueCell($pdf, 'Northing', $stationData['utm_y_wgs84'] ?? null, $colWidthThird, $dataLineHeight, $border, '', 'L', false, false);
    drawStyledLabelValueCell($pdf, 'Easting', $stationData['utm_x_wgs84'] ?? null, $colWidthThird, $dataLineHeight, $border, '', 'C', false, false);
    drawStyledLabelValueCell($pdf, 'Zone', $stationData['utm_zone_wgs84'] ?? null, $colWidthThird, $dataLineHeight, $border, '', 'L', false, true);

    $pdf->Ln(2);

    // --- Accuracy Standards Text ---
    $pdf->SetFont('Arial', '', 8);
    $pdf->SetX(15);
    $pdf->MultiCell(180, 3.5, "The accuracy standards reported herein (FGDC-STD-007-1998) supercedes and replace the previous accuracy standards found in FDCC 1984. Classified control points are certified as being consistent with all other points in the network, not merely those within that particular survey.", 0, 'L');
    $pdf->Ln(2);
    
    // --- Station Mark Description ---
    $pdf->SetX(15);
    $pdf->SetFont('Arial', '', 9); 
    $descriptionText = $stationData['description'] ?? 'Station mark description not available.';
    $pdf->MultiCell($totalWidth, $dataLineHeight-2, $descriptionText, 0, 'L');
}

function drawVerticalData(FPDF $pdf, $stationData) {
    $pdf->SetY(STATION_DATA_START_Y);
    $pdf->SetX(15); // Ensure starting X for the whole block

    $dataLineHeight = 6; // From drawStyledLabelValueCell's default $labelLineHeight
    $border = 1;
    $totalWidth = 180; 
    $halfWidth = $totalWidth / 2;

    // --- Station Name (Centered) ---
    $pdf->SetX(15);
    // Assuming 'station_name' is the correct field from $stationData
    drawStyledLabelValueCell($pdf, 'Station Name', $stationData['station_name'] ?? null, $totalWidth, $dataLineHeight, $border, '', 'C', false, true);

    // --- Island | Province ---
    $pdf->SetX(15);
    drawStyledLabelValueCell($pdf, 'Island', $stationData['island_group'] ?? null, $halfWidth, $dataLineHeight, $border, '', 'L', false, false);
    drawStyledLabelValueCell($pdf, 'Province', $stationData['province'] ?? null, $halfWidth, $dataLineHeight, $border, '', 'L', false, true);

    // --- Barangay | Municipality ---
    $pdf->SetX(15);
    drawStyledLabelValueCell($pdf, 'Barangay', $stationData['barangay'] ?? null, $halfWidth, $dataLineHeight, $border, '', 'L', false, false);
    // Assuming 'city' maps to Municipality as in horizontal
    drawStyledLabelValueCell($pdf, 'Municipality', $stationData['city'] ?? null, $halfWidth, $dataLineHeight, $border, '', 'L', false, true);

    // --- Elevation | Accuracy Class ---
    $pdf->SetX(15);
    drawStyledLabelValueCell($pdf, 'Elevation', $stationData['elevation'] ?? null, $halfWidth, $dataLineHeight, $border, ' m', 'L', false, false);
    drawStyledLabelValueCell($pdf, 'Accuracy Class', $stationData['accuracy_class'] ?? null, $halfWidth, $dataLineHeight, $border, '', 'L', false, true);

    if (isset($stationData['lon']) && is_string($stationData['lon'])) {
        $lonParts = explode(' ', trim($stationData['lon']));
        $lonDeg = $lonParts[0] ?? 'N/A';
        $lonMin = $lonParts[1] ?? '';
        $lonSec = isset($lonParts[2]) ? sprintf('%.2F', $lonParts[2]) : '';
    }
    $lonDMS = $lonDeg . utf8_decode('°') . ' ' . $lonMin . '\'' . ' ' . $lonSec . '" E';

    if (isset($stationData['lat']) && is_string($stationData['lat'])) {
        $latParts = explode(' ', trim($stationData['lat']));
        $latDeg = $latParts[0] ?? 'N/A';
        $latMin = $latParts[1] ?? '';
        $lonSec = isset($lonParts[2]) ? sprintf('%.2F', $lonParts[2]) : '';
    }
    $latDMS = $latDeg . utf8_decode('°') . ' ' . $latMin . '\'' . ' ' . $latSec . '" N';
        
    $pdf->SetX(15);
    drawStyledLabelValueCell($pdf, 'Longitude', $lonDMS, $halfWidth, $dataLineHeight, $border, '', 'L', false, false);
    drawStyledLabelValueCell($pdf, 'Latitude', $latDMS, $halfWidth, $dataLineHeight, $border, '', 'L', false, true);

    // --- Station Mark Description ---
    $pdf->SetX(15);
    $descriptionText = $stationData['description'] ?? 'Station mark description not available.';
    $descLineHeight = 5; // Match dataLineHeight for consistency within MultiCell
    $pdf->SetFont('Arial', '', 10); // Standard font for description text

    // Draw border first
    $currentX = $pdf->GetX();
    $currentY = $pdf->GetY();
    $textHeight = $pdf->GetStringWidth($descriptionText) / $totalWidth * $descLineHeight;
    $pdf->Rect($currentX, $currentY, $totalWidth, $textHeight + 7); // Border stays in same place
    $pdf->Ln(2);
    // Draw text with padding
    $pdf->SetX($currentX + 2); // Add left padding
    $pdf->MultiCell($totalWidth - 4, $descLineHeight, $descriptionText, 0, 'L'); // No border, text padded
    $pdf->Ln(3); // Space after description block    
    // $pdf->Ln(5); // Space after description block - MultiCell adds some space, user's Ln(5) might be too much or just right. Let's keep user's explicit spacing for now.
    // Actually, MultiCell updates current Y. The next Ln(5) will add space from *there*.

    $pdf->Ln(2); // Space after description block (from user's diff)

    // --- Accuracy Standards Text --- (Preserve this from user's change)
    $pdf->SetFont('Arial', '', 8);
    $pdf->SetX(15);
    $pdf->MultiCell(180, 3.5, "The accuracy standards reported herein (FGDC-STD-007-1998) supercedes and replace the previous accuracy standards found in FDCC 1984. Classified control points are certified as being consistent with all other points in the network, not merely those within that particular survey.", 0, 'L');
    $pdf->Ln(2);
    
}

function drawGravityData(FPDF $pdf, $stationData) {
    $pdf->SetY(STATION_DATA_START_Y);
    $pdf->SetX(15); // Ensure starting X for the whole block

    $dataLineHeight = 6; 
    $border = 1;
    $totalWidth = 180; 
    $halfWidth = $totalWidth / 2;
    $fillColor = [220, 220, 220]; // Gray fill for header
    $headerFontSize = 10; // Font size for section headers
    $headerLineHeight = 7; // Line height for section headers, slightly more than data for visual separation

    // --- Province (Full Width) ---
    $pdf->SetX(15);
    drawStyledLabelValueCell($pdf, 'Province', $stationData['province'] ?? null, $totalWidth, $dataLineHeight, $border, '', 'C', false, true);

    // --- Station Name (Full Width) ---
    $pdf->SetX(15);
    drawStyledLabelValueCell($pdf, 'Station Name', $stationData['station_name'] ?? null, $totalWidth, $dataLineHeight, $border, '', 'C', false, true);

    // --- Municipality | Barangay ---
    $pdf->SetX(15);
    drawStyledLabelValueCell($pdf, 'Municipality', $stationData['city'] ?? null, $halfWidth, $dataLineHeight, $border, '', 'L', false, false); // city field for Municipality
    drawStyledLabelValueCell($pdf, 'Barangay', $stationData['barangay'] ?? null, $halfWidth, $dataLineHeight, $border, '', 'L', false, true);

    // --- WGS84 Coordinates (Header) ---
    $pdf->SetX(15);
    $pdf->SetFont('Arial', 'B', $headerFontSize);
    $pdf->SetFillColor($fillColor[0], $fillColor[1], $fillColor[2]);
    $pdf->Cell($totalWidth, $headerLineHeight, 'WGS84 Coordinates', $border, 1, 'C', true); // Centered, with fill
    $pdf->SetFont('Arial', '', 9); // Reset font for subsequent data cells if not handled by drawStyledLabelValueCell

    // --- Longitude | Latitude ---
    // Assuming $stationData contains pre-formatted DMS strings for WGS84 lat/lon for gravity stations
    // e.g., $stationData['wgs84_longitude_dms'] and $stationData['wgs84_latitude_dms']
    // If these fields are not populated by getStationDetails for gravity, this will show N/A or empty.
    $pdf->SetX(15);
    drawStyledLabelValueCell($pdf, 'Longitude', $stationData['longitude'] ?? 'N/A', $halfWidth, $dataLineHeight, $border, '', 'L', false, false);
    drawStyledLabelValueCell($pdf, 'Latitude', $stationData['latitude'] ?? 'N/A', $halfWidth, $dataLineHeight, $border, '', 'L', false, true);

    // --- <EMPTY> | Observed Value ---
    $pdf->SetX(15);
    // Draw an empty, bordered cell for the left side
    $pdf->Cell($halfWidth, $dataLineHeight, '', $border, 0);
    // Observed Value for the right side
    // Using 'gravity_value' from the grav_stations SQL schema
    drawStyledLabelValueCell($pdf, 'Observed Value', $stationData['gravity_value'] ?? null, $halfWidth, $dataLineHeight, $border, '', 'L', false, true);

    // --- Station Mark Description ---
    $pdf->SetX(15);
    $descriptionText = $stationData['description'] ?? 'Station mark description not available.';
    $descLineHeight = 5; // Match dataLineHeight for consistency within MultiCell
    $pdf->SetFont('Arial', '', 10); // Standard font for description text

    // Draw border first
    $currentX = $pdf->GetX();
    $currentY = $pdf->GetY();
    $textHeight = $pdf->GetStringWidth($descriptionText) / $totalWidth * $descLineHeight;
    $pdf->Rect($currentX, $currentY, $totalWidth, $textHeight + 7); // Border stays in same place
    
    // Draw text with padding
    $pdf->SetX($currentX + 2); // Add left padding
    $pdf->MultiCell($totalWidth - 4, $descLineHeight + 2, $descriptionText, 0, 'L'); // No border, text padded
    $pdf->Ln(2); // Space after description block

    $pdf->SetFont('Arial', '', 8);
    $pdf->SetX(15);
    $pdf->MultiCell(180, 3.5, "The accuracy standards reported herein (FGDC-STD-007-1998) supercedes and replace the previous accuracy standards found in FDCC 1984. Classified control points are certified as being consistent with all other points in the network, not merely those within that particular survey.", 0, 'L');
    $pdf->Ln(2);
}


/**
 * Generates a preprocessed certificate PDF and saves it.
 *
 * @param PDO $db The database connection object (for webgnis_users).
 * @param string $transaction_code The unique transaction code, used as filename.
 * @param int $request_id The ID of the request associated with this transaction.
 * @return array ['status' => 'success'|'error', 'message' => string, 'filepath' => string|null]
 */
function generateAndSaveCertificate($db, $transaction_code, $request_id) {
    $outputDir = __DIR__ . '/assets/preprocessed_certs/'; // Ensure this is correct relative to this script.
    if (!is_dir($outputDir)) {
        if (!mkdir($outputDir, 0775, true)) {
            error_log("Failed to create certificate directory: $outputDir");
            return ['status' => 'error', 'message' => 'Failed to create certificate directory.', 'filepath' => null];
        }
    }

    $qrOutputDir = __DIR__ . '/assets/qrcodes/'; // Directory for QR codes
    error_log("[DEBUG] Initial qrOutputDir path defined as: " . $qrOutputDir); // DEBUG

    if (!is_dir($qrOutputDir)) {
        error_log("[DEBUG] qrOutputDir ('" . $qrOutputDir . "') is not a directory. Attempting to create."); // DEBUG
        if (!mkdir($qrOutputDir, 0775, true)) {
            error_log("Failed to create QR code directory: $qrOutputDir");
            // Non-fatal, QR codes might fail but PDF generation can continue with placeholder
        } else {
            error_log("[DEBUG] Successfully created directory: " . $qrOutputDir);
        }
    } else {
        error_log("[DEBUG] qrOutputDir ('" . $qrOutputDir . "') already exists."); // DEBUG
    }

    // Explicitly check writability after attempted creation or if it exists
    if (is_dir($qrOutputDir)) {
        if (is_writable($qrOutputDir)) {
            error_log("[DEBUG] qrOutputDir ('" . $qrOutputDir . "') is writable."); // DEBUG
        } else {
            error_log("[DEBUG] qrOutputDir ('" . $qrOutputDir . "') IS NOT WRITABLE."); // DEBUG
        }
    } else {
        error_log("[DEBUG] qrOutputDir ('" . $qrOutputDir . "') still not a directory after checks."); // DEBUG
    }

    $filename = $transaction_code . '.pdf';
    $filepath = $outputDir . $filename;
    // Corrected path to the letterhead image as per user's file structure.
    $letterheadImage = __DIR__ . '/assets/sample_certs/NAMRIA Letterhead (A4).docx.png';

    if (!file_exists($letterheadImage)) {
        error_log("Letterhead image not found: $letterheadImage");
        // Provide a more specific error if the image is the issue.
        return ['status' => 'error', 'message' => "Letterhead image file not found at expected path: $letterheadImage", 'filepath' => null];
    }

    $stmt = $db->prepare("SELECT ri.station_id, ri.station_name, ri.station_type, r.user_id, r.request_date 
                          FROM request_items ri
                          JOIN requests r ON ri.request_id = r.request_id
                          WHERE ri.request_id = :request_id");
    $stmt->bindParam(':request_id', $request_id, PDO::PARAM_INT);
    $stmt->execute();
    $request_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($request_items)) {
        error_log("No request items found for request_id: $request_id, transaction_code: $transaction_code");
        return ['status' => 'error', 'message' => 'No station items found for this request.', 'filepath' => null];
    }
    
    $requestingParty = getRequestingPartyDetails($db, $request_id);
    // These were originally passed to drawCertificateHeaderContent, but now some are needed for footer
    $purposeForCert = "Reference"; // Default or fetch if available
    $orNumberForCert = $transaction_code; // Default or fetch if available
    $certificateDate = $request_items[0]['request_date']; // Use request_date for certificate date

    // $tempQrFiles = []; // QR cleanup list removed
    $tempQrFiles = []; // List to keep track of generated QR image files for cleanup

    try {
        // Use FPDF directly, FPDI is not needed for placing a simple image background here.
        $pdf = new FPDF('P', 'mm', 'A4');

        foreach ($request_items as $item) {
            $pdf->AddPage();
            
            $pdf->Image($letterheadImage, 0, 0, 210, 297, 'PNG');

            // Draw header: Only needs pdf object and certificate date from this scope now.
            // Transaction code, requesting party, etc. are passed but won't be used by the new header.
            drawCertificateHeaderContent($pdf, $transaction_code, $requestingParty, $purposeForCert, $orNumberForCert, $certificateDate);

            $stationData = getStationDetails($item['station_id'], $item['station_type']);
            
            // QR Code generation logic removed
            // $qrCodePath = null; // No QR code path
            $qrCodePath = null;
            if (class_exists(Builder::class) && is_dir($qrOutputDir) && is_writable($qrOutputDir)) {
                try {
                    $request_date_for_qr = date('mdYHis', strtotime($certificateDate));
                    $qrData = "99" . $request_date_for_qr . str_replace(' ', '', $item['station_name']); // Remove spaces from station name for QR data
                    
                    // Sanitize station_id and station_name for filename
                    $safe_station_id = preg_replace('/[^a-zA-Z0-9_-]/', '_', $item['station_id']);
                    $safe_station_name = preg_replace('/[^a-zA-Z0-9_-]/', '_', $item['station_name']);
                    $qrFilename = $transaction_code . '_' . $safe_station_id . '_' . $safe_station_name . '_qr.png';
                    $currentQrPath = $qrOutputDir . $qrFilename;

                    $qr_result = (new Builder(
                        data: $qrData,
                        writer: new PngWriter(),
                        encoding: new Encoding('UTF-8'),
                        errorCorrectionLevel: ErrorCorrectionLevel::Medium,
                        size: 150, // Generate a 150x150px QR code, FPDF will resize it
                        margin: 5, // Small margin
                        validateResult: false // Improves performance by not re-reading the QR
                    ))->build();
                    
                    $qr_result->saveToFile($currentQrPath);
                    
                    if (file_exists($currentQrPath)) {
                        $qrCodePath = $currentQrPath;
                        $tempQrFiles[] = $qrCodePath; // Add to cleanup list
                    } else {
                        error_log("Failed to save QR Code to: " . $currentQrPath);
                    }
                } catch (Exception $qrE) {
                    error_log("QR Code generation failed for station {$item['station_id']}: " . $qrE->getMessage());
                }
            } else {
                if (!class_exists(Builder::class)) {
                    error_log("QR Code Builder class not found. Is endroid/qr-code installed and autoloaded?");
                }
                if (!is_dir($qrOutputDir) || !is_writable($qrOutputDir)) {
                    error_log("QR code output directory '{$qrOutputDir}' is not writable or does not exist.");
                }
            }


            if (!$stationData) {
                $pdf->SetY(STATION_DATA_START_Y); 
                $pdf->SetX(15);
                $pdf->SetFont('Arial','B',10);
                $pdf->SetTextColor(255,0,0);
                $pdf->Cell(180,10, "Error: Detailed data for {$item['station_name']} ({$item['station_id']}) not retrieved.", 0, 1, 'C');
                $pdf->SetTextColor(0,0,0);
                // Call footer even if station data fails, passing necessary details
                drawCertificateFooterContent($pdf, $transaction_code, $requestingParty, $purposeForCert, $orNumberForCert, $qrCodePath); // $qrCodePath will be null
                continue; 
            }
            
            $station_type_lower = strtolower($item['station_type']);
            if ($station_type_lower == 'horizontal' || $station_type_lower == 'caap') {
                drawHorizontalCaapData($pdf, $stationData, $station_type_lower == 'caap');
            } elseif ($station_type_lower == 'vertical') {
                drawVerticalData($pdf, $stationData);
            } elseif ($station_type_lower == 'gravity') {
                drawGravityData($pdf, $stationData);
            } else {
                 $pdf->SetY(STATION_DATA_START_Y);
                 $pdf->SetX(15);
                 $pdf->SetFont('Arial','I',10);
                 $pdf->Cell(180,10, "Format for station type '{$item['station_type']}' not implemented.", 0, 1, 'C');
            }
            // Call footer after station data is drawn, passing necessary details
            drawCertificateFooterContent($pdf, $transaction_code, $requestingParty, $purposeForCert, $orNumberForCert, $qrCodePath); // $qrCodePath will be null
        }

        $pdf->Output('F', $filepath); 

        // Cleanup temporary QR files removed
        // foreach ($tempQrFiles as $tempFile) { ... }
        foreach ($tempQrFiles as $tempFile) {
            if (file_exists($tempFile)) {
                unlink($tempFile);
            }
        }

        if (!file_exists($filepath)) {
            error_log("Failed to save PDF to: $filepath for transaction: $transaction_code");
            return ['status' => 'error', 'message' => 'Failed to save PDF.', 'filepath' => null];
        }
        return ['status' => 'success', 'message' => 'Certificate generated successfully.', 'filepath' => $filepath];

    } catch (Exception $e) {
        // Catch FPDF exceptions specifically if they occur, or general exceptions
        $errorMessage = 'PDF generation failed: ' . $e->getMessage();
        if (method_exists($e, 'getFile')) {
             $errorMessage .= ' in ' . $e->getFile() . ' on line ' . $e->getLine();
        }
        error_log("Error generating PDF for transaction $transaction_code: " . $errorMessage . " Trace: " . $e->getTraceAsString());
        return ['status' => 'error', 'message' => $errorMessage, 'filepath' => null];
    }
}

?> 