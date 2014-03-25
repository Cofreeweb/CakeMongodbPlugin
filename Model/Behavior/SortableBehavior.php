<?php

class SortableBehavior extends ModelBehavior 
{

	public $name = 'Sortable';


	public $settings = array();
  
/**
 * El nombre de la columna o key que sirve para ordenar
 *
 * @var string
 */
  private $__column = 'sort';
  
/**
 * Setup
 *
 * @param Model $Model 
 * @param array $config 
 * @return void
 */
  public function setup( Model $Model, $config = array()) 
  {
    $this->settings = $config;
	}
	

	
/**
 * AfterFind callback
 *
 * Ordena los resultados de las keys indicadas en settings
 *
 * @param Model $Model 
 * @param array $results 
 * @return void
 */	
	public function afterFind( Model $Model, $results) 
	{
	  if( isset( $Model->noSort))
	  {
	    return $results;
	  }
	  
	  foreach( $this->settings as $path)
	  {
	    $parts = explode( '.', $path);
	    $last = end( $parts);
	    
	    if( isset( $results [0][$Model->alias]))
  	  {
  	    foreach( $results as $key => &$result)
  	    {
  	      $this->sorting( $Model, $result [$Model->alias], $path);
  	    }
  	  }
	  }
	  
	  return $results;
	}
	
	
	public function sorting( Model $Model, &$result, $path)
	{
	  $parts = explode( '.', $path);
	  $last = end( $parts);

	  foreach( $result as $key => &$data)
	  {    
	    if( in_array( $key, $parts))
	    {
	      if( $key == $last)
	      {
	        $data = Hash::sort( $data, '{n}.sort', 'asc');
	      }
	      elseif( is_array( $data))
	      {
	        foreach( $data as $key2 => &$data2)
	        {
	          $this->sorting( $Model, $data2, $path);
	        }
	      }
	    }
	  }
	}
	
/**
 * Devuelve la última clave de un string separado (o no) por puntos
 *
 * @param string $key 
 * @return string
 * @example $this->__getKey( 'draft.blocks.uploads')
 * @example $this->__getKey( 'draft.blocks')
 */
	private function __getKey( $key)
	{
	  $parts = explode('.', $key);
	  return end( $parts);
	}
}
