<?php

namespace App\Core;

use LLPhant\Embeddings\EmbeddingGenerator\OpenAI\OpenAI3SmallEmbeddingGenerator;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Predis\Client;
use LLPhant\OpenAIConfig;
use Predis\Command\Argument\Search\CreateArguments;
use Predis\Command\Argument\Search\SchemaFields\TextField;
use Predis\Command\Argument\Search\SchemaFields\VectorField;
use Predis\Command\Argument\Search\SearchArguments;


class RedisSemanticCache
{
    private $predis;
    private $embeddingGenerator;

    public function __construct(Client $predis)
    {
        $this->predis = $predis;

        $config = new OpenAIConfig();
        $config->model = 'gpt-3.5-turbo';
        $config->apiKey = config('openai.api_key');
        $this->embeddingGenerator = new OpenAI3SmallEmbeddingGenerator($config);

        if (!$this->indexExists()) {
            $this->createIndex();
        }
    }

    private function indexExists()
    {
        try {
            return $this->predis->ftinfo('phpilot_cache_idx') !== null;
        } catch (\Exception $e) {
            return false;
        }
    }

    private function createIndex()
    {
        try {
            $schema = [
                new TextField('$.question', 'question'),
                new TextField('$.answer', 'answer'),
                new VectorField('$.vector', "HNSW", [
                    'DIM', 1536,
                    'TYPE', 'FLOAT32',
                    'DISTANCE_METRIC', 'COSINE',
                ], 'vector'),
            ];
    
            $this->predis->ftCreate("phpilot_cache_idx", $schema,
                (new CreateArguments())
                    ->on('JSON')
                    ->prefix(["phpilot:cache:"]));

            Log::info("phpilot_cache_idx created");
        } catch (\Exception $e) {
            Log::error("Index creation failed: " . $e->getMessage());
        }
    }

    public function isInCache($question)
    {
        return $this->semanticSearch($question, 0, 1);
    }


    public function semanticSearch($question, $start=0, $limit=10)
    {
        try {
            $embedding = $this->embeddingGenerator->embedText($question);
            $binaryQueryVector = '';
            foreach ($embedding as $value) {
                $binaryQueryVector .= pack('f', $value);
            }

            $res = $this->predis->ftSearch(
                'phpilot_cache_idx',
                '@vector:[VECTOR_RANGE $radius $query_vector]=>{$YIELD_DISTANCE_AS: dist_field}',
                (new SearchArguments())
                    ->addReturn(2, 'question', 'answer')
                    ->dialect('2')
                    ->params(['query_vector', $binaryQueryVector, 'radius', 0.1])
                    ->sortBy('dist_field', 'ASC')
                    ->limit($start, $limit));
            return $res;

        } catch (\Exception $e) {
            Log::error("Error in semantic search: " . $e->getMessage());
            return null;
        }
    }


    public function fullTextSearch($question, $start=0, $limit=10)
    {
        try {
            $arguments = new SearchArguments();
            $arguments->addReturn(2, 'question', 'answer');
            $arguments->dialect(2);
            $arguments->limit($start, $limit);
    
            // Full-text search on both question and answer fields, if the field is not specified
            $res = $this->predis->ftSearch("phpilot_cache_idx", $question, $arguments);
            return $res;

        } catch (\Exception $e) {
            Log::error("Error in semantic search: " . $e->getMessage());
            return null;
        }
    }


    public function addToCache($question, $answer)
    {
        try {
            $vector = $this->embeddingGenerator->embedText($question);

            $cacheEntryKey = 'phpilot:cache:' . Str::uuid()->toString();
            $data = json_encode([
                'question' => $question,
                'answer' => $answer,
                'vector' => $vector
            ], JSON_THROW_ON_ERROR);

            $this->predis->jsonset($cacheEntryKey, '$', $data);
            $this->predis->expire($cacheEntryKey, 2628000); // Expire after 1 month
        } catch (\Exception $e) {
            Log::error("Error adding to cache: " . $e->getMessage());
        }
    }


    public function flushCache()
    {
        try {
            $this->predis->ftDropIndex('phpilot_cache_idx', true);
        } catch (\Exception $e) {
            Log::error("Error flushing cache: " . $e->getMessage());
        }
    }
}
