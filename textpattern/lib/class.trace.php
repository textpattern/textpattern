<?php

class Trace {
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
    $this->bigBang = $this->getmicrotime();
    $this->memFunc = is_callable('memory_get_peak_usage');
  }
  
  public static function setQuiet($quiet)
  {
    self::$quiet = $quiet;
  }

  function getmicrotime()
  {
    list($usec, $sec) = explode(" ", microtime());

    return ((float) $usec + (float) $sec);
  }
  
  private function traceAdd($msg, $query = false)
  {
    $trace['level'] = sizeof($this->nest);
    $trace['begin'] = $this->getmicrotime();
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
    if (self::$quiet) return;

    $start = sizeof($this->trace);

    if ($this->isPeak()) {
      $this->memWhere = array($start-1, $start);
    }

    $this->traceAdd($msg, $query);
    array_push($this->nest, $start);
  }    
  
  public function stop($msg = null)
  {
    if (self::$quiet) return;

    $start = array_pop($this->nest);
    $this->trace[$start]['end'] = $this->getmicrotime();

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
    if (self::$quiet) return;

    $start = sizeof($this->trace);

    if ($this->isPeak()) {
      $this->memWhere = array($start-1, $start);
    }

    $this->traceAdd($msg);
  }

  private function out($str)
  {
    if (self::$quiet) return '';

    return "\n<!-- " . str_replace('--', '- - ', $str) . "-->\n";
  }

  public function summary()
  {
    $out = "Trace summary:\n";
    $out .= "Runtime   : ". sprintf('%5.3f', ($this->getmicrotime() - $this->bigBang) * 1000) . " ms\n";
    $out .= "Query time: ". sprintf('%5.3f', $this->queryTime * 1000) . " ms\n";
    $out .= "Queries   : ". $this->queries . "\n";

    if ($this->memFunc) {
      $out .= "Memory (*): ". ceil(memory_get_peak_usage() / 1024) . " kB\n";
    }

    return $this->out($out);
  }

  public function result()
  {
    $tracelog = "Trace log:\n  Time(ms) | Duration | Trace\n";
    $querylog = "Query log:\nDuration | Query\n";

    foreach($this->trace as $nr => $trace) {
      $tracelog .= (in_array($nr, $this->memWhere)) ? '*' : ' ';
      $tracelog .= sprintf(' %8.3f | ', ($trace['begin'] - $this->bigBang) * 1000);
      $line = '';

      if (isset($trace['end'])) {
        $line .= sprintf('%8.3f | ', ($trace['end'] - $trace['begin']) * 1000);
      }
      else {
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
