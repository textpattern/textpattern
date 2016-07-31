<?php

class Trace
{
    private static $quiet = false;
    private $bigBang;
    private $memFunc;
    private $memPeak = 0;
    private $memWhere = array();
    private $queries = 0;
    private $queryTime = 0;
    private $trace = array();
    private $nest = array();

    public function __construct()
    {
        $this->bigBang = isset($_SERVER['REQUEST_TIME_FLOAT']) ? $_SERVER['REQUEST_TIME_FLOAT'] : microtime(true);
        $this->memFunc = is_callable('memory_get_peak_usage');
    }

    public static function setQuiet($quiet)
    {
        self::$quiet = $quiet;
    }

    private function traceAdd($msg, $query = false)
    {
        $trace['level'] = sizeof($this->nest);
        $trace['begin'] = microtime(true);
        $trace['query'] = $query;
        $trace['msg']   = $msg;
        array_push($this->trace, $trace);
    }

    private function isPeak()
    {
        if ($this->memFunc) {
            $peak = memory_get_peak_usage();

            if ($peak > $this->memPeak) {
                $this->memPeak = $peak;

                return true;
            }
        }

        return false;
    }

    public function start($msg, $query = false)
    {
        if (self::$quiet) {
            return;
        }

        $start = sizeof($this->trace);

        if ($this->isPeak()) {
            $this->memWhere = array($start-1, $start);
        }

        $this->traceAdd($msg, $query);
        array_push($this->nest, $start);
    }

    public function stop($msg = null)
    {
        if (self::$quiet) {
            return;
        }

        $start = assert_int(array_pop($this->nest));
        $this->trace[$start]['end'] = microtime(true);

        if ($this->trace[$start]['query']) {
            $this->queries++;
            $this->queryTime += $this->trace[$start]['end'] - $this->trace[$start]['begin'];
        }

        if ($this->isPeak()) {
            $this->memWhere = array($start);

            if (null !== $msg) {
                array_push($this->memWhere, sizeof($this->trace));
            }
        }

        if (null !== $msg) {
            $this->traceAdd($msg);
        }
    }

    public function log($msg)
    {
        if (self::$quiet) {
            return;
        }

        $start = sizeof($this->trace);

        if ($this->isPeak()) {
            $this->memWhere = array($start-1, $start);
        }

        $this->traceAdd($msg);
    }

    private function out($str)
    {
        if (self::$quiet) {
            return '';
        }

        return "\n<!-- " . str_replace('--', '- - ', $str) . "-->\n";
    }

    public function summary($array = false)
    {
        $summary = array(
            'Runtime'    => sprintf('%4.2f', (microtime(true) - $this->bigBang) * 1000) . ' ms',
            'Query time' => sprintf('%4.2f', $this->queryTime * 1000) . ' ms',
            'Queries'    => $this->queries,
        );

        if ($this->memFunc) {
            $summary['Memory (*)'] = ceil(memory_get_peak_usage() / 1024) . ' kB';
        }

        $out = "Trace summary:\n";
        foreach ($summary as $key => $value) {
            $out .= sprintf("%-10s: %s\n", $key, $value);
        }

        return $array ? $summary : $this->out($out);
    }

    public function result()
    {
        $tracelog = "Trace log:\n  Time(ms) | Duration | Trace\n";
        $querylog = "Query log:\nDuration | Query\n";

        foreach ($this->trace as $nr => $trace) {
            $tracelog .= (in_array($nr, $this->memWhere)) ? '*' : ' ';
            $tracelog .= sprintf(' %8.2f | ', ($trace['begin'] - $this->bigBang) * 1000);
            $line = '';

            if (isset($trace['end'])) {
                $line .= sprintf('%8.2f | ', ($trace['end'] - $trace['begin']) * 1000);
            } else {
                $line .= str_repeat(' ', 8) . ' | ';
            }

            if ($trace['query']) {
                $querylog .= $line . $trace['msg'] . "\n";
            }

            $line .= str_repeat("\t", $trace['level']) . $trace['msg'] . "\n";
            $tracelog .= $line;
        }

        return $this->out($tracelog).($this->queries ? $this->out($querylog) : '');
    }
}
