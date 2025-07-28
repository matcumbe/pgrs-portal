<?php
require 'vendor/autoload.php';

use Google\Cloud\Storage\StorageClient;

/**
 * Uploads a file to Google Cloud Storage or local filesystem and returns the URL.
 *
 * @param string $bucketName The name of your GCS bucket (ignored for local storage).
 * @param string $sourceFilePath The path to the file to upload.
 * @param string $destinationBlobName The name of the object in storage.
 * @return string The public URL of the uploaded file.
 * @throws Exception
 */
function uploadToGCS(string $bucketName, string $sourceFilePath, string $destinationBlobName): string
{
    try {
        // Check if source file exists and is readable
        if (!file_exists($sourceFilePath)) {
            error_log("Source file not found: " . $sourceFilePath);
            throw new Exception("Upload file not found");
        }

        // Check if we're running locally (no GCS credentials)
        $credentialsFile = __DIR__ . '/service-account.json';
        if (!file_exists($credentialsFile)) {
            error_log("GCS credentials not found, using local storage");
            return handleLocalStorage($sourceFilePath, $destinationBlobName);
        }

        // If we have credentials, try GCS upload
        try {
            $storage = new StorageClient([
                'keyFilePath' => $credentialsFile
            ]);
            $bucket = $storage->bucket($bucketName);
            $object = $bucket->upload(
                fopen($sourceFilePath, 'r'),
                ['name' => $destinationBlobName]
            );
            return $object->signedUrl(new \DateTime('+ 10 years'));
        } catch (Exception $e) {
            error_log("GCS upload failed, falling back to local storage: " . $e->getMessage());
            return handleLocalStorage($sourceFilePath, $destinationBlobName);
        }
    } catch (Exception $e) {
        error_log("Error in uploadToGCS: " . $e->getMessage());
        throw $e;
    }
}

/**
 * Handles local file storage when GCS is not available.
 *
 * @param string $sourceFilePath The temporary uploaded file path.
 * @param string $destinationBlobName The desired filename.
 * @return string The relative URL to access the file.
 * @throws Exception
 */
function handleLocalStorage(string $sourceFilePath, string $destinationBlobName): string
{
    // Define local storage directory
    $storageDir = __DIR__ . '/assets/payment_proofs';
    
    // Create directory if it doesn't exist
    if (!file_exists($storageDir)) {
        if (!mkdir($storageDir, 0777, true)) {
            error_log("Failed to create storage directory: " . $storageDir);
            throw new Exception("Failed to create storage directory");
        }
    }

    // Clean the destination filename
    $filename = basename($destinationBlobName);
    $destPath = $storageDir . '/' . $filename;

    // Move the uploaded file
    if (!move_uploaded_file($sourceFilePath, $destPath)) {
        error_log("Failed to move uploaded file from $sourceFilePath to $destPath");
        throw new Exception("Failed to save uploaded file");
    }

    // Return relative URL
    return 'assets/payment_proofs/' . $filename;
} 