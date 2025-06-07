<?php
require 'vendor/autoload.php';

use Google\Cloud\Storage\StorageClient;

/**
 * Uploads a file to Google Cloud Storage and returns the public URL.
 *
 * @param string $bucketName The name of your GCS bucket.
 * @param string $sourceFilePath The path to the file to upload.
 * @param string $destinationBlobName The name of the object in GCS.
 * @return string The public URL of the uploaded file.
 * @throws Exception
 */
function uploadToGCS(string $bucketName, string $sourceFilePath, string $destinationBlobName): string
{
    // Authenticate with Google Cloud
    $storage = new StorageClient();

    // Get the bucket
    $bucket = $storage->bucket($bucketName);

    // Upload the file
    $object = $bucket->upload(
        fopen($sourceFilePath, 'r'),
        [
            'name' => $destinationBlobName
        ]
    );

    // Make the file public (optional, depends on your needs)
    $object->update(['acl' => []], ['predefinedAcl' => 'publicRead']);

    return $object->info()['mediaLink'];
} 