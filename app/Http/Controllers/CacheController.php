<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Core\RedisSemanticCache;
use Predis\Client;
use Illuminate\Support\Facades\Log;

class CacheController extends Controller
{
    protected $predis;
    protected $cache;

    public function __construct()
    {
        $this->predis = new Client([
            'scheme' => 'tcp',
            'host' => env('REDIS_HOST', '127.0.0.1'),
            'port' => env('REDIS_PORT', 6379),
            'password' => env('REDIS_PASSWORD', ''),
        ]);

        $this->cache = new RedisSemanticCache($this->predis);
    }

    public function index(Request $request)
    {
        $query = $request->input('q') ?? '*';
        $searchType = $request->input('s') ?? 'fulltext';
        $entries = [];
        $res = null;

        if ($searchType === 'fulltext') {
            if ($query === '') {
                $res = $this->cache->fullTextSearch($query, 0, 100);
            } else {
                $res = $this->cache->fullTextSearch($query, 0, 100);
            }
        } else {
            if ($query === '') {
                return redirect()->route('cache.index');
            }
            $res = $this->cache->semanticSearch($query, 0, 100);
        }

        $docs_only = array_slice($res, 1);
        $data = [];
        if ($res) {
            
            while (!empty(array_slice($docs_only, 1))) {
                $parts = explode(':', array_shift($docs_only));
                $id = end($parts);
                $doc = array_shift($docs_only); // get the next document
                $tmp = [
                    'id' => $id,
                    'question' => $doc[1],
                    'answer' => $doc[3],
                ];
                $data[] = $tmp;
            }
        }

        return view('cache_index', ['data' => $data]);
    }

    public function delete(Request $request)
    {
        $id = $request->input('id');
        $this->predis->del(sprintf('phpilot:cache:%s', $id));
        return redirect()->route('cache.index');
    }

    public function save(Request $request)
    {
        $id = $request->input('id');
        $content = $request->input('content');

        if ($id) {
            $this->predis->jsonset(sprintf('phpilot:cache:%s', $id), "$.answer", json_encode($content));
        }

        return redirect()->route('cache.index');
    }
}


