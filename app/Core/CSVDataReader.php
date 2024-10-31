<?php

namespace App\Core;

use LLPhant\Embeddings\Document;
use LLPhant\Embeddings\DataReader\DataReader;
use Illuminate\Support\Facades\Log;

final class CSVDataReader implements DataReader
{
    public string $sourceType = 'files';

    /**
     * @template T of Document
     *
     * @param  class-string<T>  $documentClassName
     * @param  string[]  $extensions
     */
    public function __construct(public string $filePath, public readonly string $documentClassName = Document::class, private readonly array $extensions = [])
    {
    }

    /**
     * @return Document[]
     */
    public function getDocuments(): array
    {
        if (! file_exists($this->filePath)) {
            return [];
        }

        $documents = [];

        if (($csvFile = fopen($this->filePath, 'r')) !== false) {
            // Read the header row
            $headers = fgetcsv($csvFile);
            
            // Process each row
            while (($row = fgetcsv($csvFile)) !== false) {
                // Combine headers and values to create an associative array
                $rowAssoc = array_combine($headers, $row);
                
                // Build the string with "header: value" format for each key-value pair
                $rowStr = implode("\n", array_map(function($key, $value) {
                    return "$key: $value";
                }, array_keys($rowAssoc), $rowAssoc));
                
                array_push($documents, $this->getDocument($rowStr, $this->filePath));
            }
    
            // Close the file
            fclose($csvFile);
        } else {
            Log::error("Error: Could not open file.");
        }

        return $documents;
    }


    private function getDocument(string $content, string $entry): mixed
    {
        $document = new $this->documentClassName();
        $document->content = $content;
        $document->sourceType = $this->sourceType;
        $document->sourceName = uniqid('', true);
        $document->hash = \hash('sha256', $content);

        return $document;
    }

    private function validExtension(string $fileExtension): bool
    {
        if ($this->extensions === []) {
            return true;
        }

        return in_array($fileExtension, $this->extensions);
    }
}
