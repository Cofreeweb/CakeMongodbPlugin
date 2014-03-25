<?php

App::uses( 'Paths', 'Mongodb.Lib');

class RevisionBehavior extends ModelBehavior 
{

	public $name = 'Revision';


	public $settings = array();
  
/**
 * Opciones por defecto
 */
  private $__defaults = array(
      'revision' => 'published'
  );
  
/**
 * Opciones por defecto usadas cuando se actualizan registros
 */
  private $__defaultsUpdate = array(
      'revision' => 'draft'
  );
  
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
		
		$query ['conditions'] = $this->_conditionsId( $Model, $query ['conditions']);
		$_conditions = $this->__changeKeys( $Model, $query ['conditions']);
		
		$query ['conditions'] = $_conditions;
		return $query;
	}
	
  public function beforeValidate( Model $Model, $options = array()) 
  {
    $options = array_merge( $this->__defaults, $options);
    $Model->revision = $options ['revision'];
		return true;
	}
	
  public function afterValidate(Model $Model) 
  { 
    if( !$Model->revision)
    {
      return true;
    }
    
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
	
	public function findSubdocumentById( Model $Model, $path, $id, $revision)
  {
    $_path = $path;
    
    if( strpos( $_path, $Model->alias .'.') === false)
    {
      $_path = $Model->alias .'.'. $_path;
    }
    
    $results = $Model->find( 'first', array(
        'conditions' => array(
            $_path .'.id' => $id
        ),
        'revision' => $revision
    ));
    
    if( !$results)
    {
      return false;
    }
    
    $path = str_replace( $Model->alias .'.', '', $path);
    
    $result = Paths::searchId( $results, $path, $id);
        
    return array(
        'document' => $result ['document'],
        'subdocument' => $result ['subdocument'],
        'path' => $result ['path']
    );
  }
  
/**
 * Añade un subdocumento dada una clave
 *
 * @param Model $Model 
 * @param string $key 
 * @param array $data 
 * @param array $options 
 * @return string El id del nuevo subdocumento
 */
  public function addSubdocument( Model $Model, $data, $options = array())
  {
    $options = array_merge( $this->__defaultsUpdate, $options);
    
    $parts = explode( '.', $options ['path']);
    
    if( count( $parts) > 1)
    {
      return $this->addSubSubdocument( $Model, $data, $options);
    }
    
    $options = array_merge( $this->__defaults, $options);
    
    $Model->id = $options ['id'];
    $Model->mongoNoSetOperator = '$push';
    
    // Guardamos el schema para ser reestableecido posteriormente
    $_schema = $Model->mongoSchema;
    $Model->mongoSchema [$options ['revision'] .'.'. $options ['path']] = array();
    
    // Añade la clave "sort" con el número total de subdocumentos que ya hay añadidos
    if( empty( $data ['sort']))
    { 
      $content = $Model->find( 'first', array(
          'conditions' => array(
              $Model->alias .'.id' => $options ['id']
          ),
          'revision' => $options ['revision']
      ));
      
      $data ['sort'] = count( $content [$Model->alias][$options ['path']]);
    }
    
    $data ['id'] = new MongoId();
    
    $data = $this->putDefaults( $Model, $data, $options ['path']);
    
    $Model->save( array(
        $options ['revision'] .'.'. $options ['path'] => $data
    ), array(
        'revision' => false
    ));
    
    $Model->mongoSchema = $_schema;
    return $data ['id']->__toString();
  }
 
/**
 * Añade un subdocumento a un subdocumento
 *
 * @param Model $Model 
 * @param string $key El key a insertar
 * @param string $id El id del key padre
 * @param array $data 
 * @param array $options 
 * @return void
 * @example $this->addSubSubdocument( $Model, 'blocks.photos', '23423423', array( 'title' => 'Un título'))
 */
  public function addSubSubdocument( Model $Model, $data, $options = array())
  {  
    $options = array_merge( $this->__defaultsUpdate, $options);
    
    // Dividide las partes por .
    $parts = explode('.', $options ['path']);

    // El key de el subdocumento a actualizar
    $key2 = end( $parts);

    // El (los) key(s) padres del subdocument a actualizar
    $parent_key = str_replace( '.'. $key2, '', $options ['path']);
    $content = $this->findSubdocumentById( $Model, $parent_key, $options ['id'], 'draft');
    
    $subdocument = $content ['subdocument'];
    
    // Añade la clave "sort" con el número total de subdocumentos que ya hay añadidos
    if( empty( $data ['sort']))
    {
      $count = Paths::count( $content ['document'], $options ['path']);
      $data ['sort'] = ($count + 1);
    }
    
    if( empty( $data ['id']))
    {
      $data ['id'] = new MongoId();
    }
    
    $data = $this->putDefaults( $Model, $data, $options ['path']);
    
    $subdocument [$key2][] = $data;

    $subdocument = $this->__unStringId( $subdocument);
    $options ['path'] = $content ['path'];
    $this->updateSubdocument( $Model, $parent_key, $options ['id'], $subdocument, $options);
    return is_string( $data ['id']) ? $data ['id'] : $data ['id']->__toString();
  }
  
  
/**
 * Actualiza un subdocumento
 *
 * @param Model $Model 
 * @param string $key 
 * @param string $id 
 * @param string $data 
 * @param string $options 
 * @return void
 */
  public function updateSubdocument( Model $Model, $key, $id, $data, $options = array())
  {
    $options = array_merge( $this->__defaultsUpdate, $options);
    $revision = $options ['revision'];
    
    // Si utiliza el Behavior Sortable, le decimos que no ordene nada, si no, no funcionará
    $Model->noSort = true;
    
    // Los datos del documento a actualizar
    $content = $Model->findSubdocumentById( $key, $id, $revision);

    // El subdocumento a actualizar
    $subdocument = $content ['subdocument'];

    // Hacemos de la ID un objeto de Mongo
    if( isset( $subdocument ['id']) && is_string( $subdocument ['id']))
    {
      $subdocument ['id'] = new MongoId( $subdocument ['id']);
    }
    
    // // Borramos la id de los datos
    // if( isset( $data ['id']))
    // {
    //   unset( $data ['id']);
    // }
    
    $path = explode( '.', $key);
       
    if( empty( $options ['no_merge']))
    {
      $data = array_merge( $subdocument, $data);
    }
    
    $data = $this->__unStringId( $data);
    
    // Si el path tiene más de un nivel lo actualizamos con las claves de "path" que nos devuelve findSubdocumentById()
    if( count( $path) > 1)
    {
      $path = Paths::toString( $content ['path']);
      $fields = array(
          $revision .'.'. $path => $data
      );
    }
    // Si no, usamos el "modo Mongo"
    else
    {
      $fields = array(
          $revision .'.'. $key .'.$' => $data
      );
    }
    
    
    $conditions = array(
        $revision .'.'. $key .'.id' => new MongoId( $id)
    );
    
    
    return $Model->updateAll( $fields, $conditions);
  }
  
/**
 * Borra un subdocumento
 *
 * @param Model $Model 
 * @param string $key el path 
 * @param string $id el id a borrar
 * @param array $options 
 * @return void
 */
  public function deleteSubdocument( Model $Model, $key, $id, $options = array())
  {
    $options = array_merge( $this->__defaultsUpdate, $options);
    $revision = $options ['revision'];
    
    // Primero toma el contenido actual, antes de actualizarlo
    $content = $Model->findSubdocumentById( $key, $id, $revision);
    $parent_data = Paths::getParent( $content, $key);
    $parent = $Model->findSubdocumentById( $parent_data ['path'], $parent_data ['document']['id'], $revision);
    $subdocument = $parent ['subdocument'];
    
    $key3 = end( explode( '.', $key));

    foreach( $subdocument [$key3] as $key2 => $data)
    {
      if( $data ['id'] == $id)
      {
        unset( $subdocument [$key3][$key2]);
      }
    }
    
    $subdocument [$key3] = array_values( $subdocument [$key3]);
    $options ['no_merge'] = true;
    
    $this->updateSubdocument( $Model, $parent_data ['path'], $parent_data ['document']['id'], $subdocument, $options);
  }
  
  
  
  public function moveSubdocument( Model $Model, $id, $parent_id, $options = array())
  {
    $options = array_merge( $this->__defaultsUpdate, $options);
    
    // Si utiliza el Behavior Sortable, le decimos que no ordene nada, si no, no funcionará
    $Model->noSort = true;
    
    // Los datos del documento a actualizar
    $content = $this->findSubdocumentById( $Model, $options ['path'], $id, $options ['revision']);
    
    $this->deleteSubdocument( $Model, $options ['path'], $id);
    
    $parts = explode( '.', $options ['path']);
    
    unset( $parts [count( $parts) - 1]);

    $this->addSubdocument( $Model, $content ['subdocument'], array(
        'id' =>  $parent_id,
        'path' => $options ['path'],
    ));
  }

  
/**
 * Borra un subcontenido dado su ID
 *
 * @param Model $Model 
 * @param string $key El key a borrar
 * @param string $id El id del key a borrar
 * @param string $options 
 * @return void
 * @example $this->Entry->deleteSubSubdocument( 'blocks.photos', '5321d584cae8b7fbf100000', array( 'revision' => 'draft'));
 */
  public function deleteSubSubdocument( Model $Model, $key, $id, $options)
  {
    // Dividide las partes por .
    $parts = explode('.', $key);
    
    // El key de el subdocumento a actualizar
    $key2 = end( $parts);
    
    // El (los) key(s) padres del subdocument a actualizar
    $parent_key = str_replace( '.'. $key2, '', $key);
    
    $content = $Model->find( 'first', array(
        'conditions' => array(
            $Model->alias .'.'. $key.'.id' => new MongoId( $id)
        ),
        'revision' => 'draft'
    ));
    
    if( empty( $content))
    {
      return false;
    }
    
    $subcontents = $content [$Model->alias][$parent_key];
    
    foreach( $subcontents as $key3 => $_content)
    {
      if( isset( $_content [$key2]))
      {
        foreach( $_content [$key2] as $key4 => $__content)
        {
          if( $__content ['id'] == $id)
          {
            $subcontent = $_content;
            unset( $subcontent [$key2][$key4]);
            $subcontent_id = $subcontent ['id'];
            $subcontent = $this->__unStringId( $subcontent);
            $subcontent [$key2] = array_values( $subcontent [$key2]);
            $this->updateSubdocument( $Model, $parent_key, $subcontent_id, $subcontent, $options);
          }
        }
      }
    }
    
    return true;
  }
  
  
  private function __unStringId( $results)
	{
	  foreach( $results as $key => $result)
	  {
	    if( $key == 'id' && !($result instanceof MongoId))
	    {
	      $results [$key] = new MongoId( $result);
	    }
	    
	    if( is_array( $result))
	    {
	      $results [$key] = $this->__unStringId( $result);
	    }
	  }
	  
	  return $results;
	}
  
  
  public function getPath( $results, $paths, $lastkey, $id, $path = null, $keypaths = array())
  {
    foreach( $results as $key => $result)
    {
      if( isset( $result [$lastkey]))
      {
        foreach( $result [$lastkey] as $key2 => $_result)
        {
          if( isset( $_result ['id']) && $_result ['id'] == $id)
          {
            return array(
                'result' => $result,
                'keypaths' => array_merge( $keypaths, array( $path => $key), array( $lastkey => $key2))
            );
            
            return $result;
          }
        }
      }
      else
      {  
        foreach( $paths as $path)
        {
          if( isset( $result [$path]))
          {
            return $this->getPath( $result [$path], $paths, $lastkey, $id, $path, array_merge( $keypaths, array( $path => $key)));
          }
        }
      }
    }
    
    return false;
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
      if( !is_array( $value) )
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
      
    }
    
    $data = $this->__unStringId( $data);
    return $data;
  }
  
	

/**
 * Modifica las conditions de búsqueda para añadir el key de la revisión
 *
 * @param Model $Model 
 * @param array $data 
 * @return array
 */
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
  
  
/**
 * Transforma los ids en objetos de MongoId()
 *
 * @param Model $Model 
 * @param array $conditions 
 * @return array
 */
  protected function _conditionsId( Model $Model, $conditions)
  {
    foreach( $conditions as $key => &$condition)
    {
      $_keys = explode( '.', $key);
      
      if( in_array( 'id', $_keys) && end( $_keys) == 'id' && !($condition instanceof MongoId))
      {
        $condition = new MongoId( $condition);
      }
    }
    
    return $conditions;
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
  
  public function discard( Model $Model, $id)
  {
    $record = $Model->find( 'first', array(
        'conditions' => array(
            $Model->alias .'.id' => $id
        ),
        'revision' => 'published',
    ));
    
    $record [$Model->alias] = $this->normalizeValues( $Model, $record [$Model->alias]);
    
    $Model->mongoSchema = array(
        'draft' => array(' type' =>'string'),
        'published' => array(' type' =>'string'),
        'history' => array(' type' =>'string'),
    );
    
    $Model->id = $id;
    
    $Model->saveField( 'draft', $record [$Model->alias]);
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
    
    if( $published)
    {
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
  
  public function putDefaults( Model $Model, $data, $path)
  {
    $defaults = $this->getDefaults( $Model, $path);
    return array_merge( $defaults, $data);
  }
  
  public function getDefaults( Model $Model, $path)
  {
    $schema = $Model->mongoSchema;
    
    $key = key( $schema);
    
    if( in_array( $key, array( 'draft', 'published')))
    {
      $schema = $schema [$key];
    }
    
    $keys = "";
    
    $parts = explode( '.', $path);
    
    foreach( $parts as $key)
    {
      $keys .= "['$key']";
    }
    
    eval( '$fields = $schema'. $keys .';');
    
    if( !is_array( $fields))
    {
      debug( $keys);
      _d( $schema);
    }
    $return = array();
    
    $db = $Model->getDataSource();
    
    foreach( $fields as $key => $data)
    {
      if( isset( $data ['type']) && array_key_exists( $data ['type'], $db->columns))
      {
        $value = isset( $data ['default']) ? $db->value( $data ['default']) : $db->value( '');
        $return [$key] = $value;
      }
      else
      {
        $return [$key] = array();
      }
    }
    
    return $return;
  }
  
}
