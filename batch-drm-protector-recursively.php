<?php
/*

drm-protector-batch.php
This script is designed to batch process all PDF files in a specified directory 
by uploading them to the VeryPDF DRM service, applying DRM protection, and saving 
the protected PDFs (.vpdf files) to an output directory.


✅ Batch Upload and Protect PDF files (PHP Application)
-- PDF Batch DRM Protection Script

This PHP script (`drm-protector-batch.php`) is designed to batch-protect PDF files with VeryPDF DRM.  

It recursively scans an input directory, uploads each PDF file to the VeryPDF DRM online service, applies DRM protection, and downloads the protected file into an output directory while preserving the original directory structure.

The script is intended to be executed from the command line (CLI) and is suitable for processing a large number of PDF files.

-- Command-Line Usage
Run the script using the following command:
php drm-protector-batch.php <input_directory> <output_directory>

Parameters
• <input_directory>
Directory containing the original PDF files to be protected.
* All .pdf files inside this directory and its subdirectories will be processed.
* Non-PDF files are automatically ignored.

• <output_directory>
Directory where DRM-protected PDF files will be saved.
* The script will automatically create subdirectories as needed.
* The directory structure from the input directory is preserved.

Example

php drm-protector-batch.php /data/original_pdfs /data/drm_protected_pdfs

This command will:
1. Scan /data/original_pdfs recursively.
2. Upload each PDF file to the VeryPDF DRM service.
3. Apply encryption and DRM restrictions.
4. Download the protected file (.vpdf) to /data/drm_protected_pdfs.
5. Keep the same folder hierarchy as the original files.


-- Important Notes About Email Account

All uploaded files will be associated with the account specified in the 'Email' parameter of the script:

'Email' => 'Your email address', // Administrator's email address

* Use the correct email account.
* If an incorrect account is used, the protected PDF files will be placed under the wrong account.
* Make sure the email corresponds to the intended DRM account to avoid confusion or misplacement of protected files.


-- Testing Before Full Run

Before running the script on a large batch, it is highly recommended to:

1. Test with 1-2 PDF files first.
2. Verify that the DRM protection, download, and output directory placement work correctly.
3. Once confirmed, proceed to process all PDF files in the input directory.

This ensures that:
* The correct email account is used.
* Output directories and file naming are correct.
* Any potential issues are caught before processing many files.


-- Handling Server Upload Rate Limits
Due to server-side upload frequency limits imposed by the DRM service, it is normal that:
• Some PDF files may fail or be skipped during a single execution.
• Temporary upload failures do not indicate a script error.

This behavior is expected and handled intentionally by the script.


-- Safe to Run Multiple Times (Idempotent Design)

The script is designed to be safe to run multiple times:
• Before processing a PDF, it checks whether the corresponding protected file already exists in the output directory.
• If the output file exists, the script automatically skips that PDF.
• Only PDFs that have not yet been protected will be processed.

-- You can simply run the same command multiple times:
php drm-protector-batch.php <input_directory> <output_directory>

Each run will:
• Skip PDFs that were already protected in previous runs.
• Retry PDFs that were skipped earlier due to upload limits or temporary errors.
• Gradually complete DRM protection for all PDF files.
👉 Repeat the command until all PDF files are successfully protected.


-- Recommended Workflow for Large Batches
For large collections of PDFs:
1. Run the script once.
2. Wait for it to finish.
3. Run the same command again.
4. Repeat until no new files are processed.

This incremental approach:
• Avoids overwhelming the DRM server.
• Works reliably with upload rate limits.
• Requires no manual tracking of completed files.

-- System Requirements
• PHP CLI (recommended PHP 7.4+)
• PHP curl extension enabled
• Internet access to VeryPDF DRM service

To verify cURL is enabled:

php -m | grep curl

-- Summary
• ✔ Batch-process PDFs with DRM protection
• ✔ Recursive directory support
• ✔ Automatically skips already protected files
• ✔ Safe to re-run multiple times
• ✔ Designed for real-world server rate limits

This makes the script reliable and practical for large-scale PDF DRM deployment in production environments.


*/

/**
 * logMessage
 * Prints a log message to the console with a timestamp and a log level.
 *
 * @param string $msg The message to log.
 * @param string $level Optional. The log level (INFO, ERROR, SUCCESS). Default is "INFO".
 *
 * Usage:
 * logMessage("Starting process..."); // INFO by default
 * logMessage("File not found", "ERROR"); // Error log
 */
function logMessage($msg, $level = "INFO") {
    $time = date("Y-m-d H:i:s"); // Current timestamp in Y-m-d H:i:s format
    echo "[$time][$level] $msg\n"; // Output log message with timestamp and level
}

/**
 * generateRandomPassword
 * Generates a random alphanumeric password of specified length.
 *
 * @param int $length Optional. Length of the password. Default is 16 characters.
 * @return string Randomly generated password.
 *
 * Usage:
 * $pwd = generateRandomPassword(); // returns 16-char password
 * $pwd = generateRandomPassword(8); // returns 8-char password
 */
function generateRandomPassword($length = 16) {
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $password = '';
    $maxIndex = strlen($chars) - 1;

    // Loop to generate each character randomly
    for ($i = 0; $i < $length; $i++) {
        // random_int is cryptographically secure
        $password .= $chars[random_int(0, $maxIndex)];
    }

    return $password;
}

/**
 * uploadPdfWithDRM
 * Uploads a PDF file to VeryPDF's online DRM service to apply encryption and DRM protection.
 *
 * @param string $filePath Full path to the local PDF file.
 * @return string|false The server response on success (usually contains a download link),
 *                      or false on failure.
 *
 * How it works:
 * 1. Validates that the PDF file exists.
 * 2. Prepares DRM and PDF encryption options (user password, owner password, printing permissions, etc.).
 * 3. Uses cURL to POST the file along with DRM settings to VeryPDF DRM service.
 * 4. Returns the raw response from the server for further processing.
 */
function uploadPdfWithDRM($strEmail, $filePath) {
    $url = 'https://online.verypdf.com/app/pdfdrm/web/upload.php';

    // Verify file exists
    if (!file_exists($filePath)) {
        logMessage("File not found: $filePath", "ERROR");
        return false;
    }

	// Get absolute path
    $realPath = realpath($filePath);
    if ($realPath === false) {
        logMessage("Cannot resolve real path: $filePath", "ERROR");
        return false;
    }


    // Prepare POST fields
    $postFields = [
        'InputFileType' => 'LocalFile', 						// Indicate that we are uploading a local file
        'Email' => $strEmail, 									// Administrator's email address
        'EnablePDFEncryption' => 'on',							// Enable PDF encryption
        'UserPassword' => generateRandomPassword(),				// Password required to open PDF
        'OwnerPassword' => generateRandomPassword(),			// Owner password for advanced permissions
		
		//PDFCompatibility		
		//3: Acrobat 3.0 and later (40-bit RC4)		
		//5: Acrobat 5.0 and later (128-bit RC4)		
		//6: Acrobat 6.0 and later (128-bit RC4)		
		//7: Acrobat 7.0 and later (128-bit AES)		
		//9: Acrobat 9.0 and later (256-bit AES)		
        'PDFCompatibility' => '6', 								// Acrobat 6.0 and later (128-bit RC4)
		
		'perm_allowprinting' => 'on',							// Allow printing
		'perm_high_quality_printing' => 'on',					// Allow high-quality printing
        'EnableDRMProtection' => 'on',							// Enable DRM protection
        'VeryPDFDRM_IsNeedInternet' => 'ON',					// DRM requires internet connection to validate
        'VeryPDFDRM_ClientTimeZone' => 'UTC',					// Set client timezone
		'Check_VeryPDFDRM_LogonID_01' => 'ON',					// Enable user login ID check
		'Check_VeryPDFDRM_Password_01' => 'ON',					// Enable user password check
        'VeryPDFDRM_LogonID_01' => generateRandomPassword(),	// Random login ID
        'VeryPDFDRM_Password_01' => generateRandomPassword(),	// Random password

        'OtherOptions_DisableAllMessageBoxes' => 'true',		// Disable popup messages during processing
		
        //'VeryPDFDRM_ExpireAfterDate' => (new DateTime('+10 days'))->format('Y/m/d H:i'),
        //'VeryPDFDRM_DenyPrint' => 'ON',
        //'VeryPDFDRM_DenyClipCopy' => 'ON',
        //'VeryPDFDRM_DenySave' => 'ON',
        //'VeryPDFDRM_DenySaveAs' => 'ON',
        //'Check_VeryPDFDRM_SetIdleTime' => 'ON',
        //'VeryPDFDRM_SetIdleTime' => '300',
        //'Check_VeryPDFDRM_CloseAfterSeconds' => 'ON',
        //'VeryPDFDRM_CloseAfterSeconds' => '300',
        //'Check_VeryPDFDRM_TitleOfMessage' => 'ON',
        //'VeryPDFDRM_TitleOfMessage' => 'VeryPDF DRM Reader',
        //'Check_VeryPDFDRM_DescriptionOfMessage' => 'ON',
        //'VeryPDFDRM_DescriptionOfMessage' => 'Welcome to use VeryPDF DRM Reader...',
        //'Check_VeryPDFDRM_ExpireAfterViews' => 'ON',
        //'VeryPDFDRM_ExpireAfterViews' => '10',
        //'Check_VeryPDFDRM_ExpirePrintCount' => 'ON',
        //'VeryPDFDRM_ExpirePrintCount' => '10',
        //'Check_VeryPDFDRM_SetInvalidPWCount' => 'ON',
        //'VeryPDFDRM_SetInvalidPWCount' => '10',
        //'Check_VeryPDFDRM_PDFExpiryDelete' => 'ON',
        //'VeryPDFDRM_LimitIP' => '78.137.214.118',
        //'TextWatermark_Text' => 'VeryPDF',
        //'TextWatermark_Color' => 'C0C0C0',
        //'TextWatermark_IsTiledText' => 'ON',
        //'TextWatermark_FontName' => 'Arial',
        //'TextWatermark_Opacity' => '30',
        //'TextWatermark_Rotate' => '-45',
        'LocalFile[]' => new CURLFile($realPath)
    ];

	// Initialize cURL for HTTP POST
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_POST, true);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);	// Ignore SSL certificate validation
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);		// Return response instead of printing
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);		// Follow HTTP redirects
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);		// Connection timeout
	curl_setopt($ch, CURLOPT_TIMEOUT, 300);				// Maximum execution time for request

	logMessage("Uploading file: $realPath");

	$response = curl_exec($ch);

	// Handle cURL errors
	if ($response === false) {
		$err = curl_error($ch);
		curl_close($ch);
		logMessage("cURL error: $err", "ERROR");
		return false;
	}

	$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

	//logMessage("Server response: $response\n\n\n\n", "INFO");

	curl_close($ch);

	if ($httpCode !== 200) {
		logMessage("HTTP status code: $httpCode for file $filePath", "ERROR");
		return false;
	}

	return $response;
}

/**
 * downloadFileWithCurl
 * Downloads a file from a remote URL and saves it locally using cURL.
 *
 * @param string $url The remote file URL.
 * @param string $savePath Local path to save the downloaded file.
 * @return bool True if download succeeds, false if it fails.
 *
 * Usage:
 * downloadFileWithCurl("https://example.com/file.vpdf", "/path/to/save/file.vpdf");
 */
function downloadFileWithCurl($url, $savePath) {
    $ch = curl_init($url);

    // Open local file for writing
    $fp = fopen($savePath, 'w');
    if (!$fp) {
        logMessage("Failed to open local file for writing: $savePath", "ERROR");
        return false;
    }

    // cURL options
    curl_setopt($ch, CURLOPT_FILE, $fp);				// Write output directly to file
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);		// Follow redirects
    curl_setopt($ch, CURLOPT_TIMEOUT, 300);				// Maximum execution time
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);		// Connection timeout
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);	// Disable SSL verification (optional)
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);

    // Execute the cURL request
    $success = curl_exec($ch);
    if (!$success) {
        $err = curl_error($ch);
        logMessage("cURL download error: $err", "ERROR");
        fclose($fp);
        curl_close($ch);
        return false;
    }

    // Close file and cURL session
    fclose($fp);
    curl_close($ch);

    logMessage("File downloaded successfully to: $savePath", "SUCCESS");
    return true;
}

/**
 * saveEncryptedPdf
 * Extracts the download link for the DRM-protected PDF (.vpdf) from the server response 
 * and downloads the file to the output directory.
 *
 * @param string $response Server response from uploadPdfWithDRM().
 * @param string $outputDir Directory to save the protected PDF.
 * @param string $originalFile Original PDF file path (used for naming and logging).
 * @return bool True on success, false on failure.
 *
 * How it works:
 * 1. Uses regex to extract the first .vpdf URL from the response.
 * 2. Constructs the output path using the original file name and output directory.
 * 3. Calls downloadFileWithCurl() to download the protected file.
 */
function saveEncryptedPdf($response, $outputDir, $originalFile) {
    // Extract the first .vpdf download link from the response
    if (!preg_match('/https?:\/\/[^\s"\']+\.vpdf/i', $response, $matches)) {
        logMessage("Failed to extract .vpdf download URL for file $originalFile", "ERROR");
        return false;
    }

    $downloadUrl = $matches[0];

    // Extract filename from the original file path
    $fileName = basename($originalFile);
	//$fileName = pathinfo($originalFile, PATHINFO_FILENAME) . '.vpdf';
	
    // Combine output directory and file name to form the full output path
    $outputPath = rtrim($outputDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $fileName;

    logMessage("Starting download of .vpdf file from:\n$downloadUrl\n==>\n$outputPath\n");

    // Use cURL to download the file
    $success = downloadFileWithCurl($downloadUrl, $outputPath);

    if (!$success) {
        logMessage("Failed to download .vpdf file for $originalFile", "ERROR");
        return false;
    }

    return true;
}

/**
 * processDirectory
 * Recursively processes all PDF files in a given input directory, applies DRM, 
 * and saves the protected files to the output directory.
 *
 * @param string $inputDir Directory containing PDF files to process.
 * @param string $outputDir Directory to save DRM-protected PDF files.
 *
 * How it works:
 * 1. Validates the input directory exists.
 * 2. Recursively iterates over all files and filters only PDFs.
 * 3. For each PDF:
 *    a. Creates the corresponding output directory if it doesn't exist.
 *    b. Skips the file if it already exists in the output directory.
 *    c. Calls uploadPdfWithDRM() to apply DRM protection.
 *    d. Calls saveEncryptedPdf() to save the resulting protected PDF.
 *    e. Waits 5 seconds between processing files to avoid server overload.
 */
function processDirectory($strEmail, $inputDir, $outputDir) {
    // Check if input directory exists
    if (!is_dir($inputDir)) {
        logMessage("Input directory does not exist: $inputDir", "ERROR");
        return;
    }

	$pdfFilesArray = [];
    // Get all PDF files recursively from the input directory
    $pdfFiles = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($inputDir, RecursiveDirectoryIterator::SKIP_DOTS)
    );
	foreach ($pdfFiles as $fileInfo) {
		if ($fileInfo->getExtension() === 'pdf') {
			$pdfFilesArray[] = $fileInfo;
		}
	}

	$totalFiles = count($pdfFilesArray);
	$currentIndex = 0;

    // Flag to ensure we only pause after the first processed file
    $firstFileConfirmed = false;
	
    foreach ($pdfFilesArray as $fileInfo)
	{
        if ($fileInfo->getExtension() !== 'pdf') {
            continue; // Skip non-PDF files
        }

		$currentIndex++;
		
        $filePath = $fileInfo->getPathname();
        $relativePath = substr($filePath, strlen($inputDir) + 1); // relative path from inputDir
        $targetFile = $outputDir . DIRECTORY_SEPARATOR . $relativePath;
        $targetDir = dirname($targetFile);

        // Ensure target directory exists, create if not
        if (!is_dir($targetDir)) {
            if (!mkdir($targetDir, 0777, true)) {
                logMessage("Failed to create directory: $targetDir", "ERROR");
                continue;
            }
        }

        // Skip processing if the output file already exists
        if (file_exists($targetFile)) {
            logMessage("[$currentIndex/$totalFiles] Skipped (already exists): $targetFile", "INFO");
            continue;
        }

        logMessage("[$currentIndex/$totalFiles] ((((((((((");
        // Process the file
        logMessage("Processing file: $filePath");
        $response = uploadPdfWithDRM($strEmail, $filePath);

        // Save the encrypted PDF if the upload succeeded
        if ($response) {
            saveEncryptedPdf($response, $targetDir, $filePath);
        }
		logMessage("Finished: $filePath");
        logMessage("))))))))))\n-------------------------------\n");

        /**
         * =========================================================
         * First-file confirmation pause
         * =========================================================
         * After processing the first PDF, the script will pause
         * and wait for user confirmation before continuing.
         * This allows manual verification of DRM output quality.
         */
        if (!$firstFileConfirmed) {

            echo "\n=====================================================\n";
            echo "FIRST FILE COMPLETED\n";
            echo "Please verify the generated protected PDF.\n";
            echo "Type 'continue' to proceed with batch processing.\n";
            echo "Press Ctrl + C to abort execution.\n";
            echo "=====================================================\n";

            $handle = fopen("php://stdin", "r");

            while (true) {
                echo "Command: ";
                $line = trim(fgets($handle));

                if (strtolower($line) === 'continue') {
                    break;
                }

                echo "Invalid input. Please type 'continue' to continue.\n";
            }

            fclose($handle);

            // Ensure this pause only happens once
            $firstFileConfirmed = true;
        }		

        // Log separator and wait 5 seconds before next file
        sleep(5);
    }
}


/**
 * Get email address from command line and ask user to confirm
 * @return string Confirmed email address
 */
function getConfirmedEmailFromCLI() {
    global $argc, $argv;

    // Check if sufficient arguments are provided
    if ($argc < 4) {
        echo "Usage: php drm-protector-batch.php <email> <input_directory> <output_directory>\n";
        exit(1);
    }

    $email = $argv[1]; // Email is now the first argument

    // Display warning to the user
    echo "=====================================================\n";
    echo "WARNING: All uploaded and protected PDF files will be placed\n";
    echo "under the account associated with this email: $email\n\n";
    echo "Please double-check that this email address is correct.\n";
    echo "If you enter the wrong email, the protected PDFs will be placed\n";
    echo "into the wrong account and you will NOT be able to access them.\n";
    echo "=====================================================\n";

    // Prompt user for confirmation
    echo "Type 'YES' to confirm and continue: ";
    $handle = fopen("php://stdin", "r");
    $line = fgets($handle);
    $confirmation = trim($line);
    fclose($handle);

    // Exit if user does not confirm
    if (strtoupper($confirmation) !== 'YES') {
        echo "Email not confirmed. Exiting.\n";
        exit(1);
    }

    return $email;
}

// Main program
$strEmail = getConfirmedEmailFromCLI();
$inputDir = $argv[2];   // Input directory is now the second argument
$outputDir = $argv[3];  // Output directory is now the third argument

// Ensure curl extension is available
if (!extension_loaded('curl')) {
    logMessage("PHP curl extension is not loaded. Please enable php-curl.", "ERROR");
    exit(1);
}

// Process PDF directory
processDirectory($strEmail, $inputDir, $outputDir);

