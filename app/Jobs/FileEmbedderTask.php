<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use LLPhant\Embeddings\DocumentSplitter\DocumentSplitter;
use LLPhant\Embeddings\EmbeddingGenerator\OpenAI\OpenAI3SmallEmbeddingGenerator;
use LLPhant\Embeddings\VectorStores\Redis\RedisVectorStore;
use Predis\Client;
use App\Core\CSVDataReader;


class FileEmbedderTask implements ShouldQueue
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

        $this->processFile($filename, $indexName);
        
        // Optional: Log completion
        Log::info("Task completed successfully for data", ['data' => $this->data]);
    }


    private function processFile($filename, $indexName): void
    {

    }
}
