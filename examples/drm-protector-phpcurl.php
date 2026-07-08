<?php
/**
 * DRM Protector Script using PHP cURL
 * 
 * This script uploads a PDF file to VeryPDF DRM service,
 * applies DRM protection and watermarks, and saves the response.
 * 
 * Usage: php drm-protector-phpcurl.php <email> <PDF file path>
 */

main();

/**
 * Main entry point of the script
 * Handles command line arguments, email confirmation, and DRM upload
 */
function main() {
    // Get confirmed email address from command line
    $email = getConfirmedEmailFromCLI();

    // Get and validate PDF file path from command line
    $pdfFilePath = getPdfFilePathFromCLI();

    // Ensure PHP curl extension is available
    ensureCurlExtension();

    // Prepare POST fields for cURL request
    $postFields = preparePostFields($pdfFilePath, $email);

    // Upload PDF via cURL and save response
    uploadPdfWithCurl($postFields);
}

/**
 * Get email from command line and require user confirmation
 * @return string Confirmed email address
 */
function getConfirmedEmailFromCLI() {
    global $argc, $argv;

    if ($argc < 3) {
        die("Usage: php drm-protector-phpcurl.php <email> <PDF file path>\n");
    }

    $email = $argv[1];

    // Display warning and instructions to the user
    echo "=====================================================\n";
    echo "WARNING: All uploaded and protected PDF files will be placed\n";
    echo "under the account associated with this email: $email\n\n";
    echo "Please double-check that this email address is correct.\n";
    echo "If you enter the wrong email, the protected PDFs will be placed\n";
    echo "into the wrong account and you will NOT be able to access them.\n";
    echo "=====================================================\n";

    // Prompt for confirmation
    echo "Type 'YES' to confirm and continue: ";
    $handle = fopen("php://stdin", "r");
    $line = fgets($handle);
    fclose($handle);

    if (trim(strtoupper($line)) !== 'YES') {
        die("Email not confirmed. Exiting.\n");
    }

    return $email;
}

/**
 * Get the PDF file path from command line and validate it exists
 * @return string Absolute path of the PDF file
 */
function getPdfFilePathFromCLI() {
    global $argv;

    $pdfFilePath = $argv[2];

    if (!file_exists($pdfFilePath)) {
        die("Error: The file '$pdfFilePath' does not exist. Please provide a valid file path.\n");
    }

    $realPath = realpath($pdfFilePath);
    if ($realPath === false) {
        die("Error: Cannot resolve real path of the file.\n");
    }

    return $realPath;
}

/**
 * Ensure the PHP curl extension is loaded
 */
function ensureCurlExtension() {
    if (!extension_loaded('curl')) {
        die("Error: PHP curl extension is not loaded. Please enable php-curl.\n");
    }
}

/**
 * Prepare POST fields array for the cURL request
 * @param string $pdfFilePath Absolute path to the PDF file
 * @param string $email Email address to associate with the DRM account
 * @return array POST fields for cURL
 */
function preparePostFields($pdfFilePath, $email) {
    $expiryDate = (new DateTime('+10 days'))->format('Y/m/d H:i');

    return [
        'InputFileType' => 'LocalFile',
        'Email' => $email,
        'EnablePDFEncryption' => 'on',
        'PasswordForInputPDFFile' => '',
        'UserPassword' => 'd58mG8bX0r8AsgiH',
        'OwnerPassword' => 'Z88hgBQ5esfP5eC7',
        'PDFCompatibility' => '6',
        'EnableDRMProtection' => 'on',
        'VeryPDFDRM_IsNeedInternet' => 'ON',
        'VeryPDFDRM_ClientTimeZone' => 'UTC',
        'Check_VeryPDFDRM_LogonID_01' => 'ON',
        'VeryPDFDRM_LogonID_01' => 'Demo',
        'Check_VeryPDFDRM_Password_01' => 'ON',
        'VeryPDFDRM_Password_01' => 'Demo',
        'Check_VeryPDFDRM_ExpireAfterDate' => 'ON',
        'VeryPDFDRM_ExpireAfterDate' => $expiryDate,
        'VeryPDFDRM_DenyPrint' => 'ON',
        'VeryPDFDRM_DenyClipCopy' => 'ON',
        'VeryPDFDRM_DenySave' => 'ON',
        'VeryPDFDRM_DenySaveAs' => 'ON',
        'Check_VeryPDFDRM_SetIdleTime' => 'ON',
        'VeryPDFDRM_SetIdleTime' => '300',
        'Check_VeryPDFDRM_CloseAfterSeconds' => 'ON',
        'VeryPDFDRM_CloseAfterSeconds' => '300',
        'Check_VeryPDFDRM_TitleOfMessage' => 'ON',
        'VeryPDFDRM_TitleOfMessage' => 'VeryPDF DRM Reader',
        'Check_VeryPDFDRM_DescriptionOfMessage' => 'ON',
        'VeryPDFDRM_DescriptionOfMessage' => 'Welcome to use VeryPDF DRM Reader...',
        'Check_VeryPDFDRM_ExpireAfterViews' => 'ON',
        'VeryPDFDRM_ExpireAfterViews' => '10',
        'Check_VeryPDFDRM_ExpirePrintCount' => 'ON',
        'VeryPDFDRM_ExpirePrintCount' => '10',
        'Check_VeryPDFDRM_SetInvalidPWCount' => 'ON',
        'VeryPDFDRM_SetInvalidPWCount' => '10',
        'Check_VeryPDFDRM_PDFExpiryDelete' => 'ON',
        'VeryPDFDRM_LimitIP' => '78.137.214.118',
        'TextWatermark_Text' => 'VeryPDF',
        'TextWatermark_Color' => 'C0C0C0',
        'TextWatermark_X' => '1',
        'TextWatermark_Y' => '1',
        'TextWatermark_OffsetX' => '0',
        'TextWatermark_OffsetY' => '0',
        'TextWatermark_IsTiledText' => 'ON',
        'TextWatermark_FontName' => 'Arial',
        'TextWatermark_FontSize' => '0',
        'TextWatermark_Opacity' => '30',
        'TextWatermark_Rotate' => '-45',
        'ImageWatermark_File' => "https://www.verypdf.com/images/coffee.jpg",
        'ImageWatermark_X' => '1',
        'ImageWatermark_Y' => '1',
        'ImageWatermark_OffsetX' => '0',
        'ImageWatermark_OffsetY' => '0',
        'ImageWatermark_Width' => '0',
        'ImageWatermark_Height' => '0',
        'ImageWatermark_Scale' => '100',
        'ImageWatermark_Opacity' => '10',
        'ImageWatermark_Rotate' => '0',
        'PDFWatermark_File' => "https://www.verypdf.com/images/pdf/StandardBusiness.pdf",
        'PDFWatermark_PDFPage' => '1',
        'PDFWatermark_X' => '1',
        'PDFWatermark_Y' => '1',
        'PDFWatermark_OffsetX' => '0',
        'PDFWatermark_OffsetY' => '0',
        'PDFWatermark_Width' => '100',
        'PDFWatermark_Height' => '100',
        'PDFWatermark_Scale' => '100',
        'PDFWatermark_Opacity' => '50',
        'PDFWatermark_Rotate' => '0',
        'LineWatermark_X1' => '0',
        'LineWatermark_Y1' => '100',
        'LineWatermark_X2' => '1000',
        'LineWatermark_Y2' => '100',
        'LineWatermark_Opacity' => '50',
        'LineWatermark_Rotate' => '0',
        'LineWatermark_Width' => '1',
        'LineWatermark_Color' => 'FF0000',
        'LocalFile[]' => new CURLFile($pdfFilePath)
    ];
}

/**
 * Upload the PDF file using cURL and save the response
 * @param array $postFields POST fields including PDF file and DRM settings
 */
function uploadPdfWithCurl($postFields) {
    $url = 'https://online.verypdf.com/app/pdfdrm/web/upload.php';

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
    curl_setopt($ch, CURLOPT_TIMEOUT, 300);

    echo "Uploading file: " . $postFields['LocalFile[]']->name . "\n";

    $response = curl_exec($ch);
    if ($response === false) {
        $err = curl_error($ch);
        curl_close($ch);
        die("cURL error: $err\n");
    }

    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    file_put_contents('output.html', $response);
    echo "HTTP status: $httpCode\n";
    echo "Response saved to 'output.html'\n";
	
	
    // Extract all URLs starting with https://online.verypdf.com/u/
    preg_match_all('/https:\/\/online\.verypdf\.com\/u\/[^\s"\'<>]+/i', $response, $matches);

    if (!empty($matches[0])) {
        // Remove duplicate URLs
        $uniqueUrls = array_unique($matches[0]);

        echo "Found URLs:\n";
        foreach ($uniqueUrls as $url) {
            echo $url . "\n"; // Output one URL per line
        }
    } else {
        echo "No matching URLs found in the response.\n";
    }
}

