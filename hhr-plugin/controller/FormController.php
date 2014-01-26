<?php

include_once dirname(__FILE__) . '/../business/PluginManager.php';

global $viewContent;
$viewContent = array();

/**
 * Description of form
 *
 * @author jonj
 */
class hhr_controller_FormController
{

  public function index($fullMode = false)
  {
	global $viewContent, $wp_query;
  
	if(isset($wp_query->query_vars['hhr_id'])) {
		$shortId = urldecode($wp_query->query_vars['hhr_id']);
		$id = hhr_dto_HhReplayerDto::shortIdToId($shortId);
		
		$manager = new hhr_business_PluginManager();
		$result = $manager->getLog($id);
		
		$viewContent['dto'] = $result;
    
		if ($fullMode)
			return $this->_requireToVar(dirname(__FILE__) . '/../html/viewFull.phtml');
		else
			return $this->_requireToVar(dirname(__FILE__) . '/../html/view.phtml');
	} else {
		return $this->_requireToVar(dirname(__FILE__) . '/../html/form.phtml');
	}
  }
    
  /**
   * @param type $file
   * @return type
   */
  private function _requireToVar($file)
  {
    ob_start();
    require($file);
    return ob_get_clean();
  }

}
