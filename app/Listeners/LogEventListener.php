<?php

namespace App\Listeners;

use Illuminate\Log\Events\MessageLogged;
use Illuminate\Support\Facades\Redis;
use Predis\Client;

class LogEventListener
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
    /**
     * Handle the event.
     *
     * @param  MessageLogged  $event
     * @return void
     */
    public function handle(MessageLogged $event)
    {
        // Retrieve log message and level
        $message = $event->message;
        $level = $event->level;

        // Perform custom actions based on log level
        if ($level === 'error') {
            // Custom action for error logs
            $this->handleErrorLog($this->convertMessageLoggedToString($event));
        } elseif ($level === 'warning') {
            // Custom action for warning logs
            $this->handleWarningLog($this->convertMessageLoggedToString($event));
        } elseif ($level === 'info') {
            // Custom action for warning logs
            $this->handleInfoLog($this->convertMessageLoggedToString($event));
        }
        // You can add more conditions for other log levels as needed
    }

    protected function handleErrorLog($message)
    {
        // Implement your error log handling logic
        // Example: send an email alert or trigger a notification
        // Add the log message to the Redis stream
        $this->predis->xadd('phpilot:log', ['message' => $message], '*', ['trim' => ['MAXLEN', '~', 2]]);
        // Send an alert, notification, or perform any other custom action here
    }

    protected function handleWarningLog($message)
    {
        // Implement your warning log handling logic
        // Example: log it to a monitoring system or trigger a notification
        $this->predis->xadd('phpilot:log', ['message' => $message], '*', ['trim' => ['MAXLEN', '~', 2]]);
    }

    protected function handleInfoLog($message)
    {
        // Implement your warning log handling logic
        // Example: log it to a monitoring system or trigger a notification
        $this->predis->xadd('phpilot:log', ['message' => $message], '*', ['trim' => ['MAXLEN', '~', 2]]);
    }

    /**
     * Convert MessageLogged event to a string format.
     *
     * @param  MessageLogged  $event
     * @return string
     */
    protected function convertMessageLoggedToString(MessageLogged $event): string
    {
        // Format the message, level, and context as a string
        $contextString = json_encode($event->context); // Convert context to JSON for readability
        return "[{$event->level}] {$event->message}";
    }
}
