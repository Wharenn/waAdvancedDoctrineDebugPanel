<?php

class waAdvancedDoctrineDebugPanelPluginConfiguration extends sfPluginConfiguration
{
  public function initialize()
  {
    $this->dispatcher->connect('debug.web.load_panels', array('waAdvancedDoctrineDebugPanel', 'listenToAddPanelEvent'));
  }
}