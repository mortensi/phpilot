<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use Predis\Client as PredisClient;
use Predis\Command\Argument\Search\SearchArguments;
use Predis\PredisException;
use Predis\Command\Argument\Search\DropArguments;
use App\Jobs\CSVEmbedderTask;

class DataController extends Controller
{
    protected $predis;

    public function __construct(PredisClient $redis)
    {
        $this->predis = $redis;
    }

    // This method will handle requests to '/data'
    public function index(Request $request)
    {
        $data = [];
        $idx_overview = [];
        // The Redis facade is designed to handle core Redis commands, but not custom module commands like those from RediSearch.
        // In any case, using the facade will add a prefix to the index name
        // E.g. FT.SEARCH phpilot:phpilot_data_idx *
        // Unless you use raw execution e.g. Redis::executeRaw
        // This works
        // $test = Redis::ftsearch('phpilot_data_idx', '*');
        // but not this $existingIndexes = Redis::ftlist();
        // This works
        //$existingIndexes = Redis::ftinfo("phpilot_data_idx");

        // Let's use the Predis client directly: if using the facade, the index name will be prefixed
        //$predis = Redis::connection();
        
        $arguments = new SearchArguments();
        $arguments->addReturn(1, 'filename');
        $arguments->sortBy('uploaded', "DESC");
        $arguments->dialect(2);
        $arguments->limit(0, 50);

        $docs = $this->predis->ftSearch("phpilot_data_idx", "*", $arguments);
        $docs_only = array_slice($docs, 1);

        try {
            while (!empty(array_slice($docs_only, 1))) {
                $parts = explode(':', array_shift($docs_only));
                $id = end($parts);
                $doc = array_shift($docs_only); 
                $data = $data + [$id => basename($doc[1])];
            }
        } catch (PredisException $e) {
            return response()->json(['error' => 'Failed to retrieve documents: ' . $e->getMessage()], 500);
        }

        // Check the indexes
        $idxOverview = [];
        $idxAliasInfo = null;

        // Try to get index alias info
        try {
            $idxAliasInfo = $this->predis->ftInfo('phpilot_rag_alias');
        } catch (PredisException $e) {
            // Alias does not exist
            Log::info("The phpilot_rag_alias alias does not exist");
        }

        // Retrieve the list of indexes
        $indexes = $this->predis->executeRaw(['FT._LIST']);
        
        // Filter for indexes starting with "phpilot_rag"
        $ragIndexes = array_filter($indexes, fn($idx) => strpos($idx, 'phpilot_rag') === 0);
        
        // Retrieve information for each filtered index
        foreach ($ragIndexes as $idx) {
            // Retrieve index information
            $idxInfo = $this->predis->ftInfo($idx->getPayload());
            $tmp = [
                'name' => $idx->getPayload(),
                'docs' => $idxInfo[9],
                'is_current' => false,
            ];

            // Check to what index the alias is pointing
            if ($idxAliasInfo && $idxInfo[1]->getPayload() === $idxAliasInfo[1]->getPayload()) {
                $tmp = [
                    'name' => $idx->getPayload(),
                    'docs' => $idxInfo[9],
                    'is_current' => true,
                ];
            }

            $idxOverview[] = $tmp;
        }

        return view('data_index', ['data' => $data, 'idx_overview' => $idxOverview]); 
    }

    public function create(Request $request)
    {
        // Assuming $request contains data for the task
        $data = $request->all();
        Log::info("Creating task for data", ['data' => $data['id']]);
        // Dispatch the job asynchronously
        CSVEmbedderTask::dispatch($data['id']);
        return redirect()->route('data.index');
    }

    public function current(Request $request)
    {
        $data = $request->all();
        Log::info("Pointing phpilot_rag_alias to: ", ['data' => $data['name']]);
        $this->predis->ftAliasUpdate('phpilot_rag_alias', $data['name']);
        return redirect()->route('data.index');
    }

    public function drop(Request $request)
    {
        $data = $request->all();
        Log::info("Dropping index: ", ['data' => $data['name']]);
        $this->predis->ftDropIndex($data['name'], (new DropArguments())->dd());
        return redirect()->route('data.index');
    }

    public function remove(Request $request)
    {
        $data = $request->all();

        $filename = $this->predis->hget("data:".$data['id'], "filename");
        Log::info("Removing file: ".$filename);

        // Check if the file exists before trying to delete
        // Use a relative path, as the file is stored in the 'storage/app' directory
        if (Storage::exists('uploads/'.basename($filename))) {
            if (Storage::delete('uploads/'.basename($filename))) {
                Log::info("File deleted successfully.");
            } else {
                Log::info("File could not be deleted.");
            }
        } else {
            Log::info("File does not exist.");
        }

        $this->predis->del("data:".$data['id']);
        return redirect()->route('data.index');
    }

    public function upload(Request $request)
    {
        Log::debug("Uploading file");

        // Validate the incoming request to ensure a file is uploaded
        $request->validate([
            'asset' => 'required|mimes:csv,txt,pdf|max:10000', //kb
        ]);

        Log::debug("File validated");

        // Retrieve the uploaded file
        $file = $request->file('asset');

        // Get the original filename (e.g., the user-uploaded file's name)
        $originalFilename = $file->getClientOriginalName();
        Log::info("File uploaded: ".$originalFilename);
        
        // Store the file in the 'uploads' directory and get the stored path
        $filePath = $file->storeAs('uploads', $originalFilename);

        // If you want the full path to the file (useful for processing or archiving)
        $fullPath = storage_path('app/' . $filePath);  // Full local path to the file

        // Generate a unique key for Redis
        $key = 'data:' . Str::uuid()->toString();

        // Store data using Redis HSET
        Redis::hset($key, 'filename', $fullPath);
        Redis::hset($key, 'uploaded', Carbon::now()->timestamp);

        return redirect()->route('data.index');
    }

}
