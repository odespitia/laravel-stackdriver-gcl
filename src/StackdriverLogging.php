<?php

namespace LaravelStackdriverGcl;

use ErrorException;
use Google\Cloud\Logging\LoggingClient;
use Illuminate\Support\Facades\Config;
use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Logger;

/**
 * StackdriverLogging
 *
 * @author Anonimo <odespitia@gmail.com>
 */
class StackdriverLogging extends AbstractProcessingHandler
{
    /**
     * The Stackdriver logger
     * @var Google\Cloud\Logging\Logger
     */
    private $logger;

    /**
     * Configuracion 
     *
     * @var Object
     */
    private $configStack;

    /**
     * Labels
     *
     * @var Array
     */
    private $labels;

    /**
     * Google Cloud client logger
     *
     * @var $LoggingClient
     */
    protected $loggingClient;

    /**
     * StackdriverHandler constructor.
     */
    public function __construct()
    {
        $this->configStack = (object) Config::get('logging.channels.stackdriver');

        putenv("GOOGLE_APPLICATION_CREDENTIALS=".$this->configStack->credentials);

        $this->labels = $this->configStack->labels;
        
        // Instantiates a client
        $this->loggingClient = new LoggingClient([
            'projectId' => $this->configStack->projectId
        ]);
            
        // Selects the log to write to
        $this->logger = $this->loggingClient->logger($this->configStack->logName);
        
        parent::__construct(Logger::DEBUG, true);
    }

    /**
     * Writes the record down to the log
     *
     * @param  array $record
     * @return void
     */
    protected function write(array $record): void
    {
        $this->labels += [
            'route' => isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] : 'Not Found',
            'typeLogs' => 'default',
            'userId' => !empty(auth()->id()) ? auth()->id() . "" : '0'
        ]; 
        // set options, according to Google Stackdirver API documentation
        $options = [
            'severity' => $record['level_name'],
            'labels' => $this->labels 
        ];

        // set data, based on the $record array received as parameter from Monolog
        $data = [
            'message' => $record['message'],
        ];

        // Add exception data to payload
        if (is_array($record['context']) && isset($record['context']['exception'])) {
            $data['code'] = $record['context']['exception']->getCode();
            $data['file'] = $record['context']['exception']->getFile();
            $data['line'] = $record['context']['exception']->getLine();

            // Some exception don't have trace or severity
            if ($record['context']['exception'] instanceof ErrorException) {
                $data['severity'] = $record['context']['exception']->getSeverity();
                $data['trace'] = $record['context']['exception']->getTraceAsString();    
            }
        }

        // write the entry
        $entry = $this->logger->entry($data, $options);
        $this->logger->write($entry);
    }

    public function customsLogs($log): void
    {
        
        if (gettype($log) == "object") {
            
            $code = (get_class($log) === 'Symfony\Component\HttpKernel\Exception\NotFoundHttpException') ? $log->getStatusCode() : $log->getCode();
            
            $severity  = 'DEBUG';
            $code = ($code > 99 && $code < 512) ? $code : 500;
            
            if ($code == 0 || $code == 500) {
                $severity = 'CRITICAL';
            }elseif ($code == 404) {
                $severity = 'WARNING';
            }

            $this->labels += [
                'route' => isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] : 'Not Found',
                'typeLogs' => 'customs',
                'code' => "$code",
                'userId' => !empty(auth()->id()) ? auth()->id() . "" : '0'
            ]; 
            // Creates the log entry ### ' - Trace: '. $log->getTraceAsString()
            $entry = $this->logger->entry($log->getMessage() . ' - Line: '. $log->getLine(). ' - File: '. $log->getFile() , [
                'severity' => $severity,
                'labels' => $this->labels,
            ]);
            
            // Writes the log entry
            $this->logger->write($entry);

            return;
        }
    }
}
