<?php

class RevisionBehavior extends ModelBehavior 
{

	public $name = 'Revision';


	public $settings = array();
  
  
  private $__defaults = array(
      'revision' => 'published'
  );

  
  public function beforeValidate(Model $Model, $options = array()) 
  {
    $options = array_merge( $this->__defaults, $options);
    $Model->revision = $options ['revision'];
		return true;
	}
	
  public function afterValidate(Model $Model) 
  {    
    $data = $this->normalizeValues( $Model, $Model->data [$Model->alias]);
    
    $schema = $Model->mongoSchema;
    $Model->mongoSchema = array(
        'draft' => $schema,
        'published' => $schema,
        'history' => $schema,
    );
    
    unset( $data [$Model->primaryKey]);
    $Model->data = array(
        $Model->alias => array(
            'id' => $Model->id,
            $Model->revision => $data
        )
    );
    
    if( !empty( $Model->id))
    {
      $_data = $Model->find( 'first', array(
          'conditions' => array(
              $Model->alias .'.'. $Model->primaryKey => $Model->id
          ),
          'revision' => $Model->revision
      ));
      unset( $_data [$Model->alias][$Model->primaryKey]);
      $Model->data [$Model->alias][$Model->revision] = array_merge( $_data [$Model->alias], $Model->data [$Model->alias][$Model->revision]);
    }
    
		return true;
	}
  
/**
 * Normaliza los valores de los subdocumentos
 *
 * @param Model $Model 
 * @param array $data 
 * @return void
 */
  public function normalizeValues( Model $Model, $data)
  {
    $db = $Model->getDataSource();
    $schema = $Model->mongoSchema;
    $data ['modified'] = date( 'Y-m-d H:i:s');
    unset( $data [$Model->primaryKey]);
    
    foreach( $data as $key => &$value)
    {
      $type = $Model->getColumnType($key);
      
      if( !empty( $type))
      {
        $colType = $db->columns[$Model->getColumnType($key)];

        if( array_key_exists('format', $colType))
        {
          $value = call_user_func( $colType['formatter'], $value);
        }
      }
    }
    
    return $data;
  }
  
	public function afterFind(Model $Model, $results, $primary = false) 
	{
	  if( isset( $results [0][$Model->alias][$Model->revision]))
	  {
	    foreach( $results as $key => $result)
  		{
  		  $results [$key][$Model->alias] = array_merge( $result [$Model->alias][$Model->revision], array(
  		      $Model->primaryKey => $result [$Model->alias][$Model->primaryKey]
  		  ));
  		  unset( $results [$key][$Model->alias][$Model->revision]);
  		}
	  }
		
		return $results;
	}


	public function beforeFind(Model $Model, $query) 
	{
	  $query = array_merge( $this->__defaults, $query);
		$Model->revision = $query ['revision'];
		
		$_conditions = $this->__changeKeys( $Model, $query ['conditions']);
		
		$query ['conditions'] = $_conditions;
		
		return $query;
	}

  
  private function __changeKeys( Model $Model, $data)
  {
    $return = array();
    
    foreach( $data as $condition => $value)
		{
		  if( $condition != $Model->alias .'.'. $Model->primaryKey)
		  {
		    $key = str_replace( $Model->alias, $Model->alias .'.'. $Model->revision, $condition);
  		  $return [$key] = $value;
		  }
		  else
		  {
		    $return [$condition] = $value;
		  }
		  
		}
		
		return $return;
  }
  
  
  public function published( Model $Model, $id)
  {
    $record = $Model->find( 'first', array(
        'conditions' => array(
            $Model->alias .'.id' => $id
        ),
        'revision' => 'draft',
    ));
    
    $record [$Model->alias] = $this->normalizeValues( $Model, $record [$Model->alias]);
    $this->saveHistory( $Model, $id);
    $Model->id = $id;
    $Model->mongoSchema = array(
        'draft' => array(' type' =>'string'),
        'published' => array(' type' =>'string'),
        'history' => array(' type' =>'string'),
    );
    $Model->saveField( 'published', $record [$Model->alias]);
  }
  
  public function saveHistory( Model $Model, $id)
  {    
    $published = $Model->find( 'first', array(
        'conditions' => array(
            $Model->alias .'.id' => $id
        ),
        'revision' => 'published',
    ));
    
    unset( $published [$Model->alias][$Model->primaryKey]);
    $history = $Model->field( 'history', array(
        $Model->alias .'.id' => $id
    ));
    $history [] = $this->normalizeValues( $Model, $published [$Model->alias]);

    $Model->mongoSchema = array(
        'draft' => array(' type' =>'string'),
        'published' => array(' type' =>'string'),
        'history' => array(' type' =>'string'),
    );
    
    $Model->id = $id;
    $Model->saveField( 'history', $history);
  } 
}
