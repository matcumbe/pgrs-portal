<?php
// Include database configuration
require_once 'config.php';

// Configure error handling
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', 'php_errors.log');

// Copy FPDF from the original project if doesn't exist
if (!file_exists('fpdf/fpdf.php')) {
    error_log("FPDF library not found. Please copy from the original project.");
}

// Try to include FPDF
if (file_exists('fpdf/fpdf.php')) {
    require('fpdf/fpdf.php');
} elseif (file_exists('../webGNIS Prototype (Original)/fpdf/fpdf.php')) {
    require('../webGNIS Prototype (Original)/fpdf/fpdf.php');
} else {
    header("HTTP/1.1 500 Internal Server Error");
    echo json_encode([
        'status' => 'error',
        'message' => 'FPDF library not found'
    ]);
    exit;
}

// Set headers for API response
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json; charset=UTF-8");

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Get request parameters
$method = $_SERVER['REQUEST_METHOD'];
$endpoint = isset($_GET['action']) ? $_GET['action'] : '';
$parts = explode('/', $endpoint);
$action = $parts[0]; // First part is the action

// Verify token - this should be replaced with your actual authentication
function verifyToken($auth_header = null, $returnData = false) {
    // Pull from Authorization header if not explicitly provided
    if ($auth_header === null) {
        $headers = getallheaders();
        $auth_header = isset($headers['Authorization']) ? $headers['Authorization'] : '';
    }
    
    // Extract the token from the Authorization header
    if (preg_match('/Bearer\s(\S+)/', $auth_header, $matches)) {
        $token = $matches[1];
        
        // Here you would verify the token using your JWT library
        // For now, we'll just create a simple object for testing
        if ($returnData) {
            return (object)[
                'user_id' => 1,
                'user_type' => 'admin',
                'exp' => time() + 3600 // Expires in 1 hour
            ];
        }
        return true;
    }
    
    if ($returnData) {
        // Return a default admin user
        return (object)[
            'user_id' => 1,
            'user_type' => 'admin',
            'exp' => time() + 3600 // Expires in 1 hour
        ];
    }
    
    return false;
}

// Return standardized JSON response
function returnResponse($status_code, $message, $data = null) {
    http_response_code($status_code);
    echo json_encode([
        'status' => $status_code >= 200 && $status_code < 300 ? 'success' : 'error',
        'message' => $message,
        'data' => $data
    ]);
    exit;
}

// Custom PDF class extending FPDF
class CertificatePDF extends FPDF {
    protected $certificate_type = 'vertical'; // Default type
    protected $transaction_code = '';
    
    public function setType($type) {
        $this->certificate_type = $type;
    }
    
    public function setTransactionCode($code) {
        $this->transaction_code = $code;
    }
    
    // Function to center text (both regular and bold)
    function CenteredMixedText($regularText, $boldText, $y) {
        // Set fonts
        $this->SetFont('Arial', '', 10);
        $regularTextWidth = $this->GetStringWidth($regularText);
        
        $this->SetFont('Arial', 'B', 10);
        $boldTextWidth = $this->GetStringWidth($boldText);

        // Calculate total width
        $totalWidth = $regularTextWidth + $boldTextWidth;

        // Calculate the x position to center the text
        $x = ($this->w - $totalWidth) / 2;

        // Set position and print the regular text
        $this->SetXY($x, $y);
        $this->SetFont('Arial', '', 10);
        $this->Cell($regularTextWidth, 5, $regularText, 0, 0, 'L');

        // Print the bold text right after the regular text
        $this->SetFont('Arial', 'B', 10);
        $this->Cell($boldTextWidth, 5, $boldText, 0, 1, 'L');
    }
    
    // Create a text box with fixed width
    function TextBox($x, $y, $width, $height, $text, $border = 0, $align = 'L', $fill = false) {
        $this->SetFont('Arial', '', 8);
        $this->SetXY($x, $y);
        $this->MultiCell($width, $height, $text, $border, $align, $fill);
    }
    
    // Simple bold text renderer
    function BoldText($x, $y, $text) {
        $this->SetXY($x, $y);
        $this->SetFont('Arial', 'B', 10);
        $this->Cell($this->GetStringWidth($text), 5, $text, 0, 1);
    }
    
    // Header - Add letterhead or logo
    function Header() {
        // Add NAMRIA logo
        if (file_exists('Assets/gnis_logo.png')) {
            $this->Image('Assets/gnis_logo.png', 10, 10, 30);
        }
        
        // Add title
        $this->SetFont('Arial', 'B', 12);
        $this->SetXY(40, 15);
        $this->Cell(130, 10, 'GEODETIC CONTROL POINT CERTIFICATE', 0, 1, 'C');
        
        $this->SetFont('Arial', '', 10);
        $this->SetXY(40, 23);
        $this->Cell(130, 5, 'National Mapping and Resource Information Authority', 0, 1, 'C');
        
        // Add a line
        $this->Line(10, 35, 200, 35);
    }
    
    // Footer - Add page numbers and transaction code
    function Footer() {
        $this->SetY(-15);
        $this->SetFont('Arial', 'I', 8);
        $this->Cell(0, 10, 'Page ' . $this->PageNo() . ' of {nb}', 0, 0, 'C');
        
        // Add transaction code
        if (!empty($this->transaction_code)) {
            $this->SetY(-10);
            $this->SetFont('Arial', '', 8);
            $this->Cell(0, 5, 'Transaction: ' . $this->transaction_code, 0, 0, 'R');
        }
    }
    
    // Function to render a vertical certificate table
    function renderVerticalTable($station) {
        $this->AddPage();
        
        // Add section title
        $this->SetFont('Arial', 'B', 12);
        $this->Cell(0, 10, 'VERTICAL CONTROL POINT DETAILS', 0, 1, 'C');
        $this->Ln(5);
        
        // Start creating table
        $this->SetFont('Arial', '', 10);
        $this->SetFillColor(240, 240, 240);
        
        // Station information
        $this->Cell(50, 8, 'Station ID:', 1, 0, 'L', true);
        $this->Cell(140, 8, $station['station_id'] ?? 'N/A', 1, 1, 'L');
        
        $this->Cell(50, 8, 'Station Name:', 1, 0, 'L', true);
        $this->Cell(140, 8, $station['station_name'] ?? 'N/A', 1, 1, 'L');
        
        $this->Cell(50, 8, 'Order/Class:', 1, 0, 'L', true);
        $this->Cell(140, 8, $station['elevation_order'] ?? 'N/A', 1, 1, 'L');
        
        // Location information
        $this->Ln(5);
        $this->SetFont('Arial', 'B', 11);
        $this->Cell(0, 8, 'Location Information', 0, 1, 'L');
        $this->SetFont('Arial', '', 10);
        
        $this->Cell(50, 8, 'Region:', 1, 0, 'L', true);
        $this->Cell(140, 8, $station['region'] ?? 'N/A', 1, 1, 'L');
        
        $this->Cell(50, 8, 'Province:', 1, 0, 'L', true);
        $this->Cell(140, 8, $station['province'] ?? 'N/A', 1, 1, 'L');
        
        $this->Cell(50, 8, 'City/Municipality:', 1, 0, 'L', true);
        $this->Cell(140, 8, $station['city'] ?? 'N/A', 1, 1, 'L');
        
        $this->Cell(50, 8, 'Barangay:', 1, 0, 'L', true);
        $this->Cell(140, 8, $station['barangay'] ?? 'N/A', 1, 1, 'L');
        
        // Coordinates
        $this->Ln(5);
        $this->SetFont('Arial', 'B', 11);
        $this->Cell(0, 8, 'Coordinates and Elevation', 0, 1, 'L');
        $this->SetFont('Arial', '', 10);
        
        $this->Cell(50, 8, 'Latitude:', 1, 0, 'L', true);
        $this->Cell(140, 8, $station['latitude'] ?? 'N/A', 1, 1, 'L');
        
        $this->Cell(50, 8, 'Longitude:', 1, 0, 'L', true);
        $this->Cell(140, 8, $station['longitude'] ?? 'N/A', 1, 1, 'L');
        
        $this->Cell(50, 8, 'Elevation:', 1, 0, 'L', true);
        $this->Cell(140, 8, ($station['elevation'] ?? 'N/A') . ' meters', 1, 1, 'L');
        
        // Description
        $this->Ln(5);
        $this->SetFont('Arial', 'B', 11);
        $this->Cell(0, 8, 'Station Description', 0, 1, 'L');
        $this->SetFont('Arial', '', 10);
        
        $this->MultiCell(190, 8, $station['description'] ?? 'No description available.', 1, 'L');
        
        // Add certification statement
        $this->Ln(10);
        $this->SetFont('Arial', '', 10);
        $this->MultiCell(190, 5, 'This is to certify that the information provided above is accurate and verified based on the records of the National Mapping and Resource Information Authority (NAMRIA).', 0, 'L');
        
        // Add signature line
        $this->Ln(15);
        $this->Cell(95, 5, '', 0, 0);
        $this->Cell(95, 5, '___________________________', 0, 1, 'C');
        $this->Cell(95, 5, '', 0, 0);
        $this->Cell(95, 5, 'Authorized Signature', 0, 1, 'C');
    }
    
    // Function to render a horizontal certificate table
    function renderHorizontalTable($station) {
        $this->AddPage();
        
        // Add section title
        $this->SetFont('Arial', 'B', 12);
        $this->Cell(0, 10, 'HORIZONTAL CONTROL POINT DETAILS', 0, 1, 'C');
        $this->Ln(5);
        
        // Start creating table
        $this->SetFont('Arial', '', 10);
        $this->SetFillColor(240, 240, 240);
        
        // Station information
        $this->Cell(50, 8, 'Station ID:', 1, 0, 'L', true);
        $this->Cell(140, 8, $station['station_id'] ?? 'N/A', 1, 1, 'L');
        
        $this->Cell(50, 8, 'Station Name:', 1, 0, 'L', true);
        $this->Cell(140, 8, $station['station_name'] ?? 'N/A', 1, 1, 'L');
        
        $this->Cell(50, 8, 'Order:', 1, 0, 'L', true);
        $this->Cell(140, 8, $station['horizontal_order'] ?? 'N/A', 1, 1, 'L');
        
        // Location information
        $this->Ln(5);
        $this->SetFont('Arial', 'B', 11);
        $this->Cell(0, 8, 'Location Information', 0, 1, 'L');
        $this->SetFont('Arial', '', 10);
        
        $this->Cell(50, 8, 'Region:', 1, 0, 'L', true);
        $this->Cell(140, 8, $station['region'] ?? 'N/A', 1, 1, 'L');
        
        $this->Cell(50, 8, 'Province:', 1, 0, 'L', true);
        $this->Cell(140, 8, $station['province'] ?? 'N/A', 1, 1, 'L');
        
        $this->Cell(50, 8, 'City/Municipality:', 1, 0, 'L', true);
        $this->Cell(140, 8, $station['city'] ?? 'N/A', 1, 1, 'L');
        
        $this->Cell(50, 8, 'Barangay:', 1, 0, 'L', true);
        $this->Cell(140, 8, $station['barangay'] ?? 'N/A', 1, 1, 'L');
        
        // Coordinates
        $this->Ln(5);
        $this->SetFont('Arial', 'B', 11);
        $this->Cell(0, 8, 'Geographic Coordinates', 0, 1, 'L');
        $this->SetFont('Arial', '', 10);
        
        $this->Cell(50, 8, 'Latitude:', 1, 0, 'L', true);
        $this->Cell(140, 8, $station['latitude'] ?? 'N/A', 1, 1, 'L');
        
        $this->Cell(50, 8, 'Longitude:', 1, 0, 'L', true);
        $this->Cell(140, 8, $station['longitude'] ?? 'N/A', 1, 1, 'L');
        
        $this->Cell(50, 8, 'Ellipsoidal Height:', 1, 0, 'L', true);
        $this->Cell(140, 8, ($station['ellipsoidal_height'] ?? 'N/A') . ' meters', 1, 1, 'L');
        
        // Description
        $this->Ln(5);
        $this->SetFont('Arial', 'B', 11);
        $this->Cell(0, 8, 'Station Description', 0, 1, 'L');
        $this->SetFont('Arial', '', 10);
        
        $this->MultiCell(190, 8, $station['description'] ?? 'No description available.', 1, 'L');
        
        // Add certification statement
        $this->Ln(10);
        $this->SetFont('Arial', '', 10);
        $this->MultiCell(190, 5, 'This is to certify that the information provided above is accurate and verified based on the records of the National Mapping and Resource Information Authority (NAMRIA).', 0, 'L');
        
        // Add signature line
        $this->Ln(15);
        $this->Cell(95, 5, '', 0, 0);
        $this->Cell(95, 5, '___________________________', 0, 1, 'C');
        $this->Cell(95, 5, '', 0, 0);
        $this->Cell(95, 5, 'Authorized Signature', 0, 1, 'C');
    }
    
    // Function to render a gravity certificate table
    function renderGravityTable($station) {
        $this->AddPage();
        
        // Add section title
        $this->SetFont('Arial', 'B', 12);
        $this->Cell(0, 10, 'GRAVITY CONTROL POINT DETAILS', 0, 1, 'C');
        $this->Ln(5);
        
        // Start creating table
        $this->SetFont('Arial', '', 10);
        $this->SetFillColor(240, 240, 240);
        
        // Station information
        $this->Cell(50, 8, 'Station ID:', 1, 0, 'L', true);
        $this->Cell(140, 8, $station['station_id'] ?? 'N/A', 1, 1, 'L');
        
        $this->Cell(50, 8, 'Station Name:', 1, 0, 'L', true);
        $this->Cell(140, 8, $station['station_name'] ?? 'N/A', 1, 1, 'L');
        
        $this->Cell(50, 8, 'Order:', 1, 0, 'L', true);
        $this->Cell(140, 8, $station['order'] ?? 'N/A', 1, 1, 'L');
        
        // Location information
        $this->Ln(5);
        $this->SetFont('Arial', 'B', 11);
        $this->Cell(0, 8, 'Location Information', 0, 1, 'L');
        $this->SetFont('Arial', '', 10);
        
        $this->Cell(50, 8, 'Region:', 1, 0, 'L', true);
        $this->Cell(140, 8, $station['region'] ?? 'N/A', 1, 1, 'L');
        
        $this->Cell(50, 8, 'Province:', 1, 0, 'L', true);
        $this->Cell(140, 8, $station['province'] ?? 'N/A', 1, 1, 'L');
        
        $this->Cell(50, 8, 'City/Municipality:', 1, 0, 'L', true);
        $this->Cell(140, 8, $station['city'] ?? 'N/A', 1, 1, 'L');
        
        $this->Cell(50, 8, 'Barangay:', 1, 0, 'L', true);
        $this->Cell(140, 8, $station['barangay'] ?? 'N/A', 1, 1, 'L');
        
        // Coordinates and gravity
        $this->Ln(5);
        $this->SetFont('Arial', 'B', 11);
        $this->Cell(0, 8, 'Coordinates and Gravity Value', 0, 1, 'L');
        $this->SetFont('Arial', '', 10);
        
        $this->Cell(50, 8, 'Latitude:', 1, 0, 'L', true);
        $this->Cell(140, 8, $station['latitude'] ?? 'N/A', 1, 1, 'L');
        
        $this->Cell(50, 8, 'Longitude:', 1, 0, 'L', true);
        $this->Cell(140, 8, $station['longitude'] ?? 'N/A', 1, 1, 'L');
        
        $this->Cell(50, 8, 'Gravity Value:', 1, 0, 'L', true);
        $this->Cell(140, 8, $station['gravity_value'] ?? 'N/A', 1, 1, 'L');
        
        // Description
        $this->Ln(5);
        $this->SetFont('Arial', 'B', 11);
        $this->Cell(0, 8, 'Station Description', 0, 1, 'L');
        $this->SetFont('Arial', '', 10);
        
        $this->MultiCell(190, 8, $station['description'] ?? 'No description available.', 1, 'L');
        
        // Add certification statement
        $this->Ln(10);
        $this->SetFont('Arial', '', 10);
        $this->MultiCell(190, 5, 'This is to certify that the information provided above is accurate and verified based on the records of the National Mapping and Resource Information Authority (NAMRIA).', 0, 'L');
        
        // Add signature line
        $this->Ln(15);
        $this->Cell(95, 5, '', 0, 0);
        $this->Cell(95, 5, '___________________________', 0, 1, 'C');
        $this->Cell(95, 5, '', 0, 0);
        $this->Cell(95, 5, 'Authorized Signature', 0, 1, 'C');
    }
}

// Function to generate a PDF certificate for a transaction
function generateTransactionCertificate($db, $transactionCode) {
    try {
        error_log("Generating certificate for transaction: " . $transactionCode);
        
        // Check if transaction exists
        $transactionSql = "SELECT t.*, r.*, rs.status_name 
                           FROM transactions t
                           JOIN requests r ON t.request_id = r.request_id
                           JOIN request_statuses rs ON r.status_id = rs.status_id
                           WHERE t.transaction_code = :transaction_code
                           LIMIT 1";
        
        $stmt = $db->prepare($transactionSql);
        $stmt->bindParam(':transaction_code', $transactionCode);
        $stmt->execute();
        
        $transaction = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$transaction) {
            error_log("Transaction not found: " . $transactionCode);
            return [
                'status' => 'error',
                'message' => 'Transaction not found'
            ];
        }
        
        // Get request items (stations) for this request
        $itemsSql = "SELECT ri.*, s.station_type
                    FROM request_items ri
                    LEFT JOIN stations s ON ri.station_id = s.station_id
                    WHERE ri.request_id = :request_id";
        
        $stmtItems = $db->prepare($itemsSql);
        $stmtItems->bindParam(':request_id', $transaction['request_id']);
        $stmtItems->execute();
        
        $requestItems = $stmtItems->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($requestItems)) {
            // If no items found, try to get them from the stations API
            $requestItems = getStationsFromAPI();
        }
        
        if (empty($requestItems)) {
            error_log("No items found for request: " . $transaction['request_id']);
            return [
                'status' => 'error',
                'message' => 'No control points found for this transaction'
            ];
        }
        
        // Group items by station type
        $stationsByType = [];
        foreach ($requestItems as $item) {
            $type = $item['station_type'] ?? 'vertical'; // Default to vertical if not specified
            
            if (!isset($stationsByType[$type])) {
                $stationsByType[$type] = [];
            }
            
            $stationsByType[$type][] = $item;
        }
        
        // Create PDF
        $pdf = new CertificatePDF();
        $pdf->AliasNbPages();
        $pdf->setTransactionCode($transactionCode);
        
        // Add stations to PDF by type
        foreach ($stationsByType as $type => $stations) {
            $pdf->setType($type);
            
            foreach ($stations as $station) {
                switch ($type) {
                    case 'horizontal':
                        $pdf->renderHorizontalTable($station);
                        break;
                    case 'gravity':
                        $pdf->renderGravityTable($station);
                        break;
                    case 'vertical':
                    default:
                        $pdf->renderVerticalTable($station);
                        break;
                }
            }
        }
        
        // Create certificates directory if it doesn't exist
        $certDir = 'Assets/certificates';
        if (!is_dir($certDir)) {
            mkdir($certDir, 0755, true);
        }
        
        // Save PDF
        $filename = $certDir . '/' . $transactionCode . '.pdf';
        $pdf->Output('F', $filename);
        
        return [
            'status' => 'success',
            'message' => 'Certificate generated successfully',
            'filename' => $filename,
            'transaction_code' => $transactionCode
        ];
    } catch (Exception $e) {
        error_log("Error generating certificate: " . $e->getMessage());
        return [
            'status' => 'error',
            'message' => 'Failed to generate certificate: ' . $e->getMessage()
        ];
    }
}

// Function to get stations from the API if they are not in the database
function getStationsFromAPI() {
    $stations = [];
    
    // Get stations from the API for each type
    $types = ['vertical', 'horizontal', 'gravity'];
    
    foreach ($types as $type) {
        $url = "stations-api.php?type=" . $type;
        $response = @file_get_contents($url);
        
        if ($response) {
            $data = json_decode($response, true);
            
            if (isset($data['success']) && $data['success'] && isset($data['data'])) {
                // Add type to each station
                foreach ($data['data'] as &$station) {
                    $station['station_type'] = $type;
                }
                
                $stations = array_merge($stations, $data['data']);
            }
        }
    }
    
    // Take only the first 5 stations of each type for simplicity
    $result = [];
    $countByType = [];
    
    foreach ($stations as $station) {
        $type = $station['station_type'];
        
        if (!isset($countByType[$type])) {
            $countByType[$type] = 0;
        }
        
        if ($countByType[$type] < 5) {
            $result[] = $station;
            $countByType[$type]++;
        }
    }
    
    return $result;
}

// Connect to the database
try {
    $dbConfig = getDBConfig();
    $db = new PDO("mysql:host={$dbConfig['DB_HOST']};dbname={$dbConfig['DB_NAME']}", $dbConfig['DB_USER'], $dbConfig['DB_PASS']);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    error_log("Database connection failed: " . $e->getMessage());
    returnResponse(500, "Database connection failed", null);
}

// Handle API requests
switch ($action) {
    case 'generate':
        // Check authorization
        $token = verifyToken(null, true);
        if (!$token || $token->user_type !== 'admin') {
            returnResponse(401, "Unauthorized access", null);
        }
        
        // Get transaction code
        $transactionCode = isset($_GET['transaction_code']) ? $_GET['transaction_code'] : null;
        
        if (!$transactionCode) {
            // Check if it's in the URL path
            if (count($parts) > 1) {
                $transactionCode = $parts[1];
            }
            
            // If still no transaction code, check request body
            if (!$transactionCode) {
                $json = file_get_contents('php://input');
                $data = json_decode($json, true);
                
                if ($data && isset($data['transaction_code'])) {
                    $transactionCode = $data['transaction_code'];
                }
            }
        }
        
        if (!$transactionCode) {
            returnResponse(400, "Transaction code is required", null);
        }
        
        // Generate certificate
        $result = generateTransactionCertificate($db, $transactionCode);
        
        if ($result['status'] === 'success') {
            returnResponse(200, $result['message'], [
                'filename' => $result['filename'],
                'transaction_code' => $result['transaction_code'],
                'download_url' => $result['filename']
            ]);
        } else {
            returnResponse(500, $result['message'], null);
        }
        break;
        
    case 'download':
        // Get filename
        $transactionCode = isset($_GET['transaction_code']) ? $_GET['transaction_code'] : null;
        
        if (!$transactionCode) {
            // Check if it's in the URL path
            if (count($parts) > 1) {
                $transactionCode = $parts[1];
            }
        }
        
        if (!$transactionCode) {
            returnResponse(400, "Transaction code is required", null);
        }
        
        $filename = 'Assets/certificates/' . $transactionCode . '.pdf';
        
        // Check if file exists
        if (!file_exists($filename)) {
            returnResponse(404, "Certificate not found", null);
        }
        
        // Output file for download
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . basename($filename) . '"');
        header('Content-Length: ' . filesize($filename));
        readfile($filename);
        exit;
        
    default:
        returnResponse(400, "Invalid action", null);
} 