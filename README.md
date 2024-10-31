# phpilot

phpilot is yet another PHP GenAI chatbot. Based on [Minipilot](https://github.com/redis/minipilot) (developed in Python with Flask, OpenAI and LangChain), phpilot is a demo written in PHP with OpenAI, LLPhant, Blade and JQuery. 

From the browser UI you will be able to:

1. Load CSV data, split, embed, store and index in Redis
2. Customize the system and user prompt based on the type of chatbot running (based on the ingested and indexed data)
3. Use semantic caching, with a UI panel to review, edit and remove entries
4. Load and ingest multiple CSV files and create the corresponding indexes. However, only one index at time is used using the Redis aliasing mechanism
5. Application logs are appended to a Redis stream, to review the latest logs directly from the UI


## Configuring phpilot

Configure the following variables as environment variables (using `export`) or in a `.env` file stored in the root directory of the project

```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=your_database_name
DB_USERNAME=your_username
DB_PASSWORD=your_password

REDIS_CLIENT=predis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

SESSION_DRIVER=redis
CACHE_DRIVER=redis
QUEUE_CONNECTION=redis

OPENAI_API_KEY=your_openai_api_key
```


## Installation

```
git clone https://github.com/mortensi/phpilot.git
cd phpilot

composer install

php artisan serve

php artisan queue:work
```

Then, before launching, cache configurations and routes.

```
php artisan config:cache
php artisan route:cache
php artisan view:cache
```