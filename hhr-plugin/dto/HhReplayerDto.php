<?php

/**
 * Description of hhreplayerDto
 *
 * @author jonj
 */
class hhr_dto_HhReplayerDto
{

  protected $_id;
  protected $_shortId;

  /**
   *
   * @var DateTime
   */
  protected $_date;
  protected $_log;
  protected $_logJSON;
  protected $_postedBy;
  protected $_description;

  public function getId()
  {
    return $this->_id;
  }

	public static function shortIdToId($shortId)
    {
        return base_convert($shortId, 36, 10);
    }

    public static function buildShortId($id)
    {
        if (is_null($id))
            return;

        return base_convert($id, 10, 36);
    }

  /**
   * 
   * @return DateTime
   */
  public function getDate()
  {
    return $this->_date;
  }

  public function getLog()
  {
    return $this->_log;
  }

  public function getLogJSON()
  {
    return $this->_logJSON;
  }

  public function getPostedBy()
  {
    return $this->_postedBy;
  }

  public function getDescription()
  {
    return $this->_description;
  }

  public function setId($id)
  {
    $this->_id = $id;
  }

  public function setDate(DateTime $date)
  {
    $this->_date = $date;
  }

  public function setLog($log)
  {
    $this->_log = $log;
  }

  public function setLogJSON($log)
  {
    $this->_logJSON = $log;
  }

  public function setPostedBy($postedBy)
  {
    $this->_postedBy = $postedBy;
  }

  public function setDescription($description)
  {
    $this->_description = $description;
  }

}
