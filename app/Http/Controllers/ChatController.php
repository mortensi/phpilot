<?php

namespace App\Http\Controllers;

use LLPhant\Embeddings\EmbeddingGenerator\OpenAI\OpenAI3SmallEmbeddingGenerator;
use LLPhant\Embeddings\VectorStores\Redis\RedisVectorStore;
use LLPhant\Query\SemanticSearch\QuestionAnswering;
use Illuminate\Support\Facades\Log;
use Predis\Client;
use LLPhant\Chat\OpenAIChat;
use LLPhant\Audio\OpenAIAudio;
use LLPhant\OpenAIConfig;
use Illuminate\Http\Request;
use Psr\Http\Message\StreamInterface;
use App\Core\RedisSemanticCache;


class ChatController extends Controller
{
    protected $predis;

    public function __construct()
    {
        $this->predis = new Client([
            'scheme' => 'tcp',
            'host' => env('REDIS_HOST', '127.0.0.1'),
            'port' => env('REDIS_PORT', 6379),
            'password' => env('REDIS_PASSWORD', ''),
        ]);
    }

    public function index()
    {
        return view('chat');
    }


    public function chat(Request $request)
    {   
        // Get the session ID for user history TBD
        $value = $request->session()->getId();

        $cache = new RedisSemanticCache($this->predis);

        $docs = $cache->isInCache($request->input('q'));

        // If the question is in the cache, return the answer
        if ($docs[0] > 0) {
            echo $docs[2][1];
            return;
        }

       /*
       // GenAI chat business logic
       $responseStream = OpenAI::chat()->createStreamed([
           'model' => 'gpt-3.5-turbo',
           'messages' => [
               ['role' => 'user', 'content' => $request->input('q')],
           ],
       ]);

       // This returns the whole message
       //return $response['choices'][0]['message']['content'];
       // Loop through each chunk of data from OpenAI
       foreach ($responseStream as $delta) {
           // Stream the delta content back to the client
           echo $delta['choices'][0]['delta']['content'] ?? '';
           ob_flush();  // Send the output buffer to the browser
           flush();     // Flush the system output buffer
       }

       // Close the stream properly
       ob_flush();
       flush();
       */

       // Implement using https://github.com/theodo-group/LLPhant
       $config = new OpenAIConfig();
       $config->model = 'gpt-3.5-turbo';
       $config->apiKey = config('openai.api_key');

       $embeddingGenerator = new OpenAI3SmallEmbeddingGenerator($config);

       try {
           $redisVectorStore = new RedisVectorStore($this->predis, "phpilot_rag_alias");
       }catch (\Exception $e) {
           Log::error("Failed to create RedisVectorStore: " . $e->getMessage());
           return "Please create an index and associate the alias with it";
       }

       $redisVectorStore = new RedisVectorStore($this->predis, "phpilot_rag_alias");

        $qa = new QuestionAnswering(
            $redisVectorStore,
            $embeddingGenerator,
            new OpenAIChat($config)
        );

        $fullAnswer = "";
        $stream = $qa->answerQuestionStream($request->input('q'));

        $streamToIterator = function (StreamInterface $stream): \Generator {
            while (!$stream->eof()) {
                yield $stream->read(32); 
                ob_flush();
                flush();
            }
        };

        $iteratorStream = $streamToIterator($stream);

        foreach ($iteratorStream as $chunk) {
            echo $chunk;
            $fullAnswer .= $chunk;
        }

        // Add the question and answer to the cache
        $cache->addToCache($request->input('q'), $fullAnswer);
    }
}

