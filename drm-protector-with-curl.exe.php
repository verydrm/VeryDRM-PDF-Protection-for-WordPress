<?php
/**
 * Main entry point of the script
 * Handles command line arguments, user confirmation, and DRM PDF upload
 */
function main() {
    // Get and confirm email address from command line
    $email = getConfirmedEmailFromCLI();

    // Get PDF file path from command line
    $pdfFilePath = getPdfFilePathFromCLI();

    // Ensure curl.exe exists
    $curlPath = getCurlPath();

    // Build the curl command with DRM and watermark options
    $curlCommand = buildCurlCommand($curlPath, $pdfFilePath, $email);

    // Execute the curl command and save the output
    executeCurlCommand($curlCommand);
}

/**
 * Get email address from command line and ask user to confirm
 * @return string Confirmed email address
 */
function getConfirmedEmailFromCLI() {
    global $argc, $argv;

    if ($argc < 3) {
        echo "Usage: php drm-protector.php <email> <PDF file path>\n";
        exit(1);
    }

    $email = $argv[1]; // Email is now the first argument

    // Display a warning to the user
    echo "=====================================================\n";
    echo "WARNING: All uploaded and protected PDF files will be placed\n";
    echo "under the account associated with this email: $email\n\n";
    echo "Please double-check that this email address is correct.\n";
    echo "If you enter the wrong email, the protected PDFs will be placed\n";
    echo "into the wrong account and you will NOT be able to access them.\n";
    echo "=====================================================\n";

    // Prompt the user for confirmation
    echo "Type 'YES' to confirm and continue: ";
    $handle = fopen("php://stdin", "r");
    $line = fgets($handle);
    $confirmation = trim($line);
    fclose($handle);

    if (strtoupper($confirmation) !== 'YES') {
        echo "Email not confirmed. Exiting.\n";
        exit(1);
    }

    return $email;
}

/**
 * Get the PDF file path from command line and validate
 * @return string Absolute path to the PDF file
 */
function getPdfFilePathFromCLI() {
    global $argv;
    $pdfFilePath = $argv[2]; // PDF file path is the second argument

    if (!file_exists($pdfFilePath)) {
        die("Error: The file '$pdfFilePath' does not exist. Please provide a valid file path.\n");
    }

    return realpath($pdfFilePath);
}

/**
 * Get curl executable path and check if it exists
 * @return string Absolute path to curl.exe
 */
function getCurlPath() {
    $curlPath = dirname(__FILE__) . '/curl/curl.exe';
    echo "Checking curl path: $curlPath\n";
    if (!file_exists($curlPath)) {
        die("Error: curl.exe not found. Please ensure curl is installed and the path is correct.\n");
    }
    return $curlPath;
}

/**
 * Build the curl command string for uploading PDF with DRM and watermarks
 * @param string $curlPath Path to curl.exe
 * @param string $pdfFilePath Path to PDF file
 * @param string $email Email address for DRM account
 * @return string Complete curl command
 */
function buildCurlCommand($curlPath, $pdfFilePath, $email) {
    $expiryDate = (new DateTime('+10 days'))->format('Y/m/d H:i');

    // Base curl command with POST and file upload
    $cmd = "\"$curlPath\" -X POST \"https://online.verypdf.com/app/pdfdrm/web/upload.php\" ";
    $cmd .= '-F "InputFileType=LocalFile" ';
    $cmd .= '-F "Email=' . $email . '" ';
    $cmd .= '-F "EnablePDFEncryption=on" ';
    $cmd .= '-F "UserPassword=d58mG8bX0r8AsgiH" ';
    $cmd .= '-F "OwnerPassword=Z88hgBQ5esfP5eC7" ';
    $cmd .= '-F "PDFCompatibility=6" ';
    $cmd .= '-F "EnableDRMProtection=on" ';
    $cmd .= '-F "VeryPDFDRM_IsNeedInternet=ON" ';
    $cmd .= '-F "VeryPDFDRM_ClientTimeZone=UTC" ';
    $cmd .= '-F "VeryPDFDRM_ExpireAfterDate=' . $expiryDate . '" ';
    $cmd .= '-F "VeryPDFDRM_DenyPrint=ON" ';
    $cmd .= '-F "VeryPDFDRM_DenyClipCopy=ON" ';
    $cmd .= '-F "VeryPDFDRM_DenySave=ON" ';
    $cmd .= '-F "VeryPDFDRM_DenySaveAs=ON" ';
    $cmd .= '-F "TextWatermark_Text=VeryPDF" ';
    $cmd .= '-F "TextWatermark_Color=C0C0C0" ';
    $cmd .= '-F "TextWatermark_FontName=Arial" ';
    $cmd .= '-F "TextWatermark_Opacity=30" ';
    $cmd .= '-F "LocalFile[]=@' . $pdfFilePath . '"';

    return $cmd;
}

/**
 * Execute the curl command and save the output to output.html
 * @param string $curlCommand The command to execute
 */
function executeCurlCommand($curlCommand) {
    echo "Executing the following command:\n$curlCommand\n";
    $output = shell_exec($curlCommand);

    // Save the output to output.html
    file_put_contents('output.html', $output);
    //echo $output;
    echo "\nOutput has been saved to 'output.html'.\n";
	
    // Extract all URLs starting with https://online.verypdf.com/u/
    preg_match_all('/https:\/\/online\.verypdf\.com\/u\/[^\s"\'<>]+/i', $output, $matches);

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

// Run the main program
main();

