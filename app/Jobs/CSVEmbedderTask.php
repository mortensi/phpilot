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

        $redisVectorStore = new RedisVectorStore($redisClient, $indexName);
        $redisVectorStore->createIndex(vectorDimension: 1536, algorithmType: IndexAlgorithmType::HNSW);

        foreach ($documents as $document) {
            $splittedDocuments = DocumentSplitter::splitDocuments([$document], 1500);
            $embeddingGenerator = new OpenAI3SmallEmbeddingGenerator();
            $embeddedDocuments = $embeddingGenerator->embedDocuments($splittedDocuments);
            $redisVectorStore->addDocuments($embeddedDocuments);
        }
    }
}
