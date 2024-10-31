<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use LLPhant\Embeddings\DocumentSplitter\DocumentSplitter;
use LLPhant\Embeddings\EmbeddingGenerator\OpenAI\OpenAI3SmallEmbeddingGenerator;
use LLPhant\Embeddings\VectorStores\Redis\RedisVectorStore;
use LLPhant\Embeddings\VectorStores\Redis\IndexAlgorithmType;
use Predis\Client;
use Predis\Command\Argument\Search\CreateArguments;
use Predis\Command\Argument\Search\SchemaFields\TextField;
use Predis\Command\Argument\Search\SchemaFields\VectorField;
use App\Core\CSVDataReader;


class CSVEmbedderTask implements ShouldQueue
{
    use Queueable;
    protected $data;
    public $timeout = 3600;

    /**
     * Create a new job instance.
     */


    public function __construct($data)
    {
        $this->data = $data;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $predis = Redis::connection();

        $res = $predis->hget("data:".$this->data, "filename");
        $filename = str_replace(' ', '', $res);
        Log::info("Processing data for filename: {$filename}");
        
        $indexName = "phpilot_rag_".pathinfo(basename($filename), PATHINFO_FILENAME)."_".date("Ymd_His")."_idx";
        Log::info("Creating index: {$indexName}");

        $this->embedFile($filename, $indexName);
        
        // Optional: Log completion
        Log::info("Task completed successfully for CSVEmbedderTask");
    }

    private function embedFile($filename, $indexName): void
    {
        $dataReader = new CSVDataReader($filename);
        $documents = $dataReader->getDocuments();
        Log::info("Number of documents: ".count($documents));

        $redisClient = new Client([
            'scheme' => 'tcp',
            'host' => env('REDIS_HOST', '127.0.0.1'),
            'port' => env('REDIS_PORT', 6379),
            'password' => env('REDIS_PASSWORD', ''),
        ]);

        // Using a custom method to create the index before adding the documents
        // This is copied and modified from RedisVectorStore.php
        $this->createIndex($redisClient, 1536, $indexName);

        // Now I can use the RedisVectorStore to add the documents
        $redisVectorStore = new RedisVectorStore($redisClient, $indexName);

        foreach ($documents as $document) {
            $splittedDocuments = DocumentSplitter::splitDocuments([$document], 1500);
            $embeddingGenerator = new OpenAI3SmallEmbeddingGenerator();
            $embeddedDocuments = $embeddingGenerator->embedDocuments($splittedDocuments);
            $redisVectorStore->addDocuments($embeddedDocuments);
        }
    }

    // This is copied and modified from RedisVectorStore.php
    // I want to create the index before adding the documents
    // To be removed once the RedisVectorStore.php is updated and the method made public
    // And above all, we can choose FLAT or HNSW as the index algorithm and the vector dimension
    // Choosing between hash and JSON would be nice too
    private function createIndex(Client $client, int $vectorDimension, string $indexName): void
    {
        $schema = [
            new TextField('$.content', 'content'),
            new TextField('$.formattedContent', 'formattedContent'),
            new TextField('$.sourceType', 'sourceType'),
            new TextField('$.sourceName', 'sourceName'),
            new TextField('$.hash', 'hash'),
            new VectorField('$.embedding', 'FLAT', [
                'DIM', $vectorDimension,
                'TYPE', 'FLOAT32',
                'DISTANCE_METRIC', 'COSINE',
            ], 'embedding'),
        ];

        $client->ftcreate($indexName, $schema,
            (new CreateArguments())
                ->on('JSON')
                ->prefix([$indexName.':'])
        );
    }
}
