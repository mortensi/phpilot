<?php

namespace App\Http\Controllers;

use LLPhant\Embeddings\EmbeddingGenerator\OpenAI\OpenAI3SmallEmbeddingGenerator;
use LLPhant\Embeddings\VectorStores\Redis\RedisVectorStore;
use LLPhant\Query\SemanticSearch\QuestionAnswering;
use Illuminate\Support\Facades\Log;
use Predis\Client as PredisClient;
use LLPhant\Chat\OpenAIChat;
use LLPhant\OpenAIConfig;
use Illuminate\Http\Request;
use Psr\Http\Message\StreamInterface;
use App\Core\RedisSemanticCache;


class ChatController extends Controller
{
    protected $predis;
    protected $sessionId;

    public function __construct(Request $request, PredisClient $redis)
    {
        $this->predis = $redis;
        $this->sessionId = $request->session()->getId();
    }


    public function index(Request $request)
    {
        $history = $this->predis->xrange(sprintf('phpilot:memory:%s', $this->sessionId), '-', '+');
        return view('chat', ['history' => $history]);
    }


    public function chat(Request $request)
    {   
        $cache = new RedisSemanticCache($this->predis);
        $docs = $cache->isInCache($request->input('q'));

        // If the question is in the cache, return the answer
        if ($docs[0] > 0) {
            echo $docs[2][3];
            $this->predis->xadd(sprintf('phpilot:memory:%s', $this->sessionId), 
                ['UserMessage' => $request->input('q'), 'AiMessage' => $docs[2][3]],
                 '*',
                  ['trim' => ['MAXLEN', '~', 20]]);

            // Set the history expiration time, same as session lifetime
            $this->predis->expire(sprintf('phpilot:memory:$s', $this->sessionId), config('session.lifetime'));
            return;
        }

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
        // Note that the LLPhant current implementation sends in the system prompt the message ""Use the following pieces of context to answer the question..." together with the context retrieved from the database.
        // The user prompt, instead, only contains the question. The system prompt can usually be flexible to contain the context; however,
        // I miss the system prompt definition like "you are a helpful assistant, you can answer questions about the following topics..."
        // See examples here https://platform.openai.com/docs/guides/text-generation
        // Ideally, the system prompt should contain the personalization of the chat https://platform.openai.com/docs/guides/text-generation#system-messages
        // but given that the system prompt can be personalized, I use my own system prompt, paying attention to include {context} in the message, which is replaced by the context.
        // It would be nice to separate retrieval from the user prompt, for more granular configuration.
        // To account for the conversation history, we can inject it in the system prompt too by passing another placeholder like {history} and replace it with the conversation history.
        
        // Get custom system prompt from Redis
        $systemPrompt = $this->predis->get('phpilot:prompt:system');
        
        // Process the conversation history and add to the system prompt
        $systemPrompt = str_replace('{history}', $this->getSummarizedHistory($request->input('q')), $systemPrompt);
        
        // Use my own system prompt
        $qa->systemMessageTemplate = $systemPrompt;

        // Summarized history and question, to be used for retrieval
        $summarizedHistory = $this->getSummarizedHistory($request->input('q'));
        $stream = $qa->answerQuestionStream($summarizedHistory);

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
        $cache->addToCache($summarizedHistory, $fullAnswer);

        // Add the question and answer to the history
        $this->predis->xadd(sprintf('phpilot:memory:%s', $this->sessionId), 
            ['UserMessage' => $request->input('q'), 'AiMessage' => $fullAnswer],
            '*',
            ['trim' => ['MAXLEN', '~', 20]]);

        // Set the history expiration time, same as session lifetime
        $this->predis->expire(sprintf('phpilot:memory:$s', $this->sessionId), config('session.lifetime'));
    }


    private function getSummarizedHistory($question)
    {
        // Let's fetch the conversation history from Redis and summarize it with the question
        // Given the following conversation and a follow up question, rephrase the follow up question to be a standalone question, in its original language.
        $history = $this->predis->xrange(sprintf('phpilot:memory:%s', $this->sessionId), '-', '+');
        $historyText = json_encode($history);

        $config = new OpenAIConfig();
        $config->model = 'gpt-3.5-turbo';
        $config->apiKey = config('openai.api_key');
        $chat = new OpenAIChat($config);

        // Generate a response to the follow up question
        $response = $chat->generateText(sprintf("Given the following conversation and a follow up question, rephrase the follow up question to be a standalone question, use only the English language. \n\n Chat history: /n%s \n\n Follow up input: %s", $historyText, $question));
        
        return $response;
    }


    public function reset(Request $request)
    {  
       $this->predis->del(sprintf('phpilot:memory:%s', $this->sessionId));
       
       return response()->json([
           'success' => true,
           'message' => 'History dropped successfully!'
       ], 200);   
   }

       /*
       // GenAI chat business logic example using the OpenAI API
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
}

