<?php

/**
 * waAdvancedDoctrineDebugPanel is based on sfWebDebugPanelDoctrine
 * Adds several informations and controls to optimize sql queries
 *
 * @author     Wharenn
 */
class waAdvancedDoctrineDebugPanel extends sfWebDebugPanel
{
  protected $colors = array(
    300 => array('#000','Emergency'),
    200 => array('#7D0000','Critical'),
    150 => array('#F00','Warning'),
    75 => array('#D97400','Average'),
    0 => array('#0F0','Good')
  );

  protected $tablesCalculated = false;
  protected $info = array();
  protected $logs = null;

  /**
   * Method called by project configuration to add this bar to the sf web debug bar
   *
   * @param $event
   */
  public static function listenToAddPanelEvent(sfEvent $event)
  {
    $webDebugToolbar = $event->getSubject();
    
    $webDebugToolbar->setPanel('databasedebug', new waAdvancedDoctrineDebugPanel($webDebugToolbar));
  }

  /**
   * Constructor.
   *
   * @param sfWebDebug $webDebug The web debut toolbar instance
   */
  public function __construct(sfWebDebug $webDebug)
  {
    parent::__construct($webDebug);

    $this->webDebug->getEventDispatcher()->connect('debug.web.filter_logs', array($this, 'filterLogs'));
  }

  /**
   * Get the title/icon for the panel
   *
   * @return string $html
   */
  public function getTitle()
  {
    if ($sqlLogs = $this->getLogs())
    {
      $nbQueries = sizeof($sqlLogs);

      // Apply a color depending on the number of queries
      foreach($this->colors as $min => $content)
      {
        if($nbQueries >= $min)
        {
          $backgroundColor = $content[0];
          $label = $content[1];
          break;
        }
      }

      $color = 'white';
    }
    else
    {
      $backgroundColor = 'none';
      $color = 'black';
      $label = 'Really?';
      $nbQueries = 0;
    }
    
    return '<img src="'.$this->webDebug->getOption('image_root_path').'/database.png" alt="SQL queries" /> <span style="background-color: '.$backgroundColor.'; color: '.$color.';"> '.$nbQueries.' ('.$label.')</span>';
  }

  /**
   * Get the verbal title of the panel
   *
   * @return string $title
   */
  public function getPanelTitle()
  {
    return 'Advanced Doctrine Debug Panel';
  }

  /**
   * Get the html content of the panel
   *
   * @return string $html
   */
  public function getPanelContent()
  {
    $sqlLogs = $this->getLogs();

    // Add table uses summary
    // Sort table uses
    $idTables = array();
    $counts = array();
    $i = 0;
    foreach(self::$tables as $table => $nbUse)
    {
      $counts[$table] = $nbUse;
      $idTables[$table] = $i++;
    }
    
    $tableSummary = array();
    
    if(is_array($counts))
    {
      arsort($counts, SORT_NUMERIC);
  
      // Build summary of table uses
      foreach($counts as $table => $nbUse)
      {
        $tableSummary[] = sprintf('<div style="float: left; margin-right: 10px; line-height: 15px;"><a href="#" onclick="jQuery(\'#sfWebDebugBarCancelLink\').show(); jQuery(\'#sfWebDebugAdvancedDatabaseLogs\').children(\'ol\').children(\'li\').hide(); jQuery(\'#sfWebDebugAdvancedDatabaseLogs\').children(\'ol\').children(\'li.info\').show(); jQuery(\'#sfWebDebugAdvancedDatabaseLogs\').children(\'ol\').children(\'li.table-'.$table.'\').show(); return false;" title="Only display queries on this table"><span style="color: blue;"> %s</span> (%s)</a></div>', $table, $nbUse);
      }
    }

    // Add color legend
    $legend = '<div style="float: left; font-weight: bold; padding: 2px; margin: 2px 2px 2px 0;">SQL status legend :</div>';
    foreach($this->colors as $min => $content)
    {
      $legend .= '<div style="background-color: '.$content[0].'; color: white; float: left; margin: 2px; padding: 2px;">&gt;= '.$min.' queries ('.$content[1].')</div>';
    }

    $liStyle= ' style="line-height: 120% !important;
      padding: 5px 0px !important;
      border-bottom: 1px solid silver !important;
      list-style-type: decimal !important;
      margin-bottom:0"';

    $liInfoStyle= ' style="line-height: 120% !important;
      padding: 5px 0px !important;
      border-bottom: 1px solid silver !important;
      list-style-type: decimal !important;
      background: #CCC; text-indent: 10px;
      text-shadow:1px 1px 1px rgba(0, 0, 0, 0.2);"';



    // Build information and query rows
    $queries = array();
    foreach($sqlLogs as $i => $log)
    {
      $table = $log['table'];

      $message = '';
      if(array_key_exists($i, $this->info))
      {
        foreach($this->info[$i] as $mess)
        {
          $message .= '<li'.$liInfoStyle.' class="info" style=""><b>'.$mess['message'].' queries:</b></li>';
        }
      }
      $message .= '<li'.$liStyle.' class="table-'.$table.' sfWebDebugDatabaseQuery">'.$log['log'].' <a href="#" style="color: blue;" onclick="jQuery(this).parent().children(\'span.select\').show(); jQuery(this).hide(); return false;">(View select content)</a></li>';
      $queries[] = $message;
    }

    return '
      <div id="sfWebDebugAdvancedDatabaseLogs">
        <div style="overflow: auto; margin-bottom: 10px;">'.$legend.'</div>
        <b>Table call summary (click on a table to filter queries)</b>
        <div style="overflow: auto; margin-bottom: 10px;">'.implode("\n", $tableSummary).'</div>
        <b>SQL queries <span id="sfWebDebugBarCancelLink" style="display: none;">(<a href="#" style="color: blue" onclick="jQuery(\'#sfWebDebugAdvancedDatabaseLogs\').children(\'ol\').children(\'li\').show(); jQuery(this).parent().hide(); return false;">Cancel table filters</a>)</a></span></b>
        <ol style="margin-left: 20px">'.implode("\n", $queries).'</ol>
      </div>
    ';
  }

  /**
   * Filter the logs to only include the entries from sfDoctrineLogger
   *
   * @param sfEvent $event
   * @param array $Logs
   * @return array $newLogs
   */
  public function filterLogs(sfEvent $event, $newSqlogs)
  {
    $newLogs = array();
    foreach ($newSqlogs as $newSqlog)
    {
      if ('sfDoctrineConnectionProfiler' != $newSqlog['type'])
      {
        $newLogs[] = $newSqlog;
      }
    }

    return $newLogs;
  }

  protected function getLogs()
  {
    if(is_null($this->logs))
    {
      $this->logs = $this->getSqlLogs();
    }

    return $this->logs;
  }

  /**
   * Build the sql logs and return them as an array
   *
   * @return array $newSqlogs
   */
  protected function getSqlLogs()
  {
    $logs = array();
    //$bindings = array();
    $i = 0;
    $previousLine = array();
    
    $sqlLogTitles = array('Doctrine_Connection_Mysql', 'Doctrine_Connection_Statement');
    foreach ($this->webDebug->getLogger()->getLogs() as $log)
    {
      // Store the log if it is not a doctrine query log
      if (!in_array($log['type'], $sqlLogTitles))
      {
        $previousLine[] = $log;
        continue;
      }

      // Add log info to the debug display list
      $y = $i;
      if(sizeof($previousLine) > 0)
      {
        $this->info[$y++] = $previousLine;
        $previousLine = array();
      }

      if (preg_match('/^.*?(\b(?:SELECT|INSERT|UPDATE|DELETE)\b.*)$/', $log['message'], $match))
      {
        // Extract the targeted table and update its uses counter
        $table = self::extractTableFromSQL($match[1]);
        if(!$this->tablesCalculated)
        {
          if(!array_key_exists($table, self::$tables))
          {
            self::$tables[$table] = '1';
          }
          else
          {
            self::$tables[$table]++;
          }
        }

        $logs[$i++] = array('log' => $this->formatSql($match[1]), 'table' => $table);

        //$bindings[$i - 1] = array();
      }
      /*else if (preg_match('/Binding (.*) at position (.+?) w\//', $log['message'], $match))
      {
        $bindings[$i - 1][] = $match[2].' = '.$match[1];
      }*/
    }

    $logs = $this->reintroduceValuesIntoQueries($logs);

    if(!$this->tablesCalculated)
    {
      $this->tablesCalculated = true;
    }

    return $logs;
  }

  /**
   * Retrieve parameters values after the query and add it into the query
   * 
   * @param array $logs query logs with values at the end
   * @return array query logs with replaced values
   */
  protected function reintroduceValuesIntoQueries($logs)
  {
    foreach ($logs as $i => $log)
    {
      // Do we have bindings to reintroduce into query ?
      if(strstr($log['log'],'?'))
      {
        // There is bindind to do
        $start = strrpos($log['log'], '- (');
        $binds = substr($log['log'], $start+3);
        $binds = substr($binds, 0, strlen($binds)-1);
        $binds = explode(', ', $binds);

        $this->queries[$i] = $log['log'];
        foreach($binds as $bind)
        {
          $this->queries[$i] = preg_replace('/\?/', (is_numeric($bind) ? $bind : "'$bind'"), $this->queries[$i],1);
        }

        $start = strrpos($this->queries[$i], '- (');
        $this->queries[$i] = substr($this->queries[$i], 0, $start-1);

        $logs[$i]['log'] = $this->queries[$i];
      }
    }

    return $logs;
  }

  private static $tables = array();

  /**
   * Extract the table from a sql query log
   *
   * @param string $sql
   */
  protected static function extractTableFromSQL($sql)
  {
    $exp = '|FROM `([^ \(]*)`|ms';
    $returnArray = array();
    preg_match_all($exp, $sql, $matches);
    
    if(sizeof($matches) > 0 && sizeof($matches[0]) > 0)
    {
      foreach($matches[0] as $index => $value)
      {
        $returnArray = preg_replace($exp,'$1', $matches[0][$index]);
      }
    }
    else
    {
      return '';
    }
    
    return $returnArray;
  }


  /**
   * Format a SQL with some colors on SQL keywords to make it more readable
   *
   * @param  string $sql    SQL string to format
   * @return string $newSql The new formatted SQL string
   */
  public function formatSql($sql)
  {
    // Replace disturbing selected fields by an *
    $sql = preg_replace('/SELECT (.*) FROM/', 'SELECT <span style="display: none;" class="select">$1</span> FROM', $sql);

    // Add colors
    $sql = preg_replace('/\b(SELECT|LIMIT|ASC|COUNT|DESC|IN|LIKE|DISTINCT)\b/', '<span class="sfWebDebugLogInfo">\\1</span>', $sql);

    // Add colors and line breaks
    $sql = preg_replace('/\b(UPDATE|SET|FROM|WHERE|LEFT JOIN|INNER JOIN|RIGHT JOIN|ORDER BY|GROUP BY|DELETE|INSERT|INTO|VALUES)\b/', '<br/><span class="sfWebDebugLogInfo">\\1</span>', $sql);
    
    return $sql;
  }
}