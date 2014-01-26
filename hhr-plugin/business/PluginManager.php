<?php

require_once dirname(__FILE__).'/../dto/HhReplayerDto.php';
require_once 'Parser/Factory.php';

/**
 * Description of hhr_business_PluginManager
 *
 * @author jonj
 */
class hhr_business_PluginManager
{

	public function logToArray($log)
	{
		$log = str_replace("\r", '', $log);
		return explode("\n", $log);
	}

    /**
     * 
     * @param type $logContent
     * @return type
     */
	public function getParser($log)
	{
		$lines = $this->logToArray($log);
		$parser = Parser_Factory::getParser($lines);
		return $parser;
	}

	public function getLogJSON($parser)
	{
		$hand = $parser->parse();
		return $hand->toJSON();
	}
  
  /**
   * 
   * @global type $wpdb
   * @param type $logContent
   * @param type $link
   * @param type $username
   * @return type
   */
  public function save($logContent, $logJSON)
  {
    global $wpdb;

    $table_name = $wpdb->prefix . 'hhreplayer';

    $wpdb->insert(
        $table_name, 
        array(
			'hh_log' => $logContent,
			'hh_log_json' => $logJSON,
			'hh_time' => current_time('mysql')
		)
    );
	
	return $wpdb->insert_id;
  }
  
  /**
   * @global type $wpdb
   * 
   * @param type $id
   * @return \hhr_dto_HhReplayerDto
   */
  public function getLog($id)
  {
    global $wpdb;

    $table_name = $wpdb->prefix . 'hhreplayer';

    $sql = "SELECT * 
			FROM  $table_name 
			WHERE hh_id = {$id}
			";
    
    $row = $wpdb->get_results($sql);
    
    if (empty($row)) {
      return null;
    }
    
    $resultDto = $row[0];
    $dto = new hhr_dto_HhReplayerDto();
    
    $dto->setId($resultDto->hh_id);
    $dto->setDate(DateTime::createFromFormat('Y-m-d H:i:s', $resultDto->hh_time));
    $dto->setLog($resultDto->hh_log);
	$dto->setLogJSON($resultDto->hh_log_json);
    $dto->setPostedBy($resultDto->hh_posted_by);
    $dto->setDescription($resultDto->hh_description);
    
    return $dto;
  }

}
