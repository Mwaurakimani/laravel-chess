<?php

namespace Database\Seeders\Classes;

trait DataExtractor
{
    public function getData($fileName)
    {
        // Build the file path
        $filePath = database_path("seeders/Classes/Data/{$fileName}");

        // Check if file exists
        if (!file_exists($filePath)) {
            throw new \Exception("Data file not found: {$filePath}");
        }

        // Get file contents and decode JSON
        $json = file_get_contents($filePath);
        $data = json_decode($json, true);

        // Handle JSON errors
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception("Error parsing JSON from {$filePath}: " . json_last_error_msg());
        }

        return $data;
    }
}
