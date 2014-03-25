<?php

/**
*  Paths
*
*  Se encarga de ayudar a gestionar los subdocumentos de MongoDB, con la búsqueda de documentos dentro de arrays multidimensionales con varios niveles
*/
class Paths
{
/**
 * Aquí se van a guardar los keys del rastro para llegar hasta a la id
 */
  protected static $_keys = array();
  
/**
 * Los resultados iniciales de la búsqueda
 */
  protected static $_results = false;
  
  
/**
 * Busca el array dado un id
 * Este método es llamado recursivamente por sí mismo para poder encontrar el id
 *
 * @param array $results Los resultados de una find() con el model
 * @param string $paths El path del array a buscar p.e. 'comments.user'
 * @param string $id El id a buscar
 * @param string $path El último path en el que el propio método ha buscado. No se define en la llamada del método
 *
 * @return array 'document': El documento a buscar
 *               'document': El recorrido dentro del array hasta llegar a ese documento
 *               'path': Un array con los keys númericos de posición donde se encuentran los elementos padres del documento a buscar
 *
 * @example Paths::searchId( $results, 'blocks.photos', '5328b1ffcae8b7463d000000)
 */
  public function searchId( $results, $paths, $id, $path = false)
  {
    $paths_array = explode( '.', $paths);
    $lastkey = $paths_array [count( $paths_array) - 1];
    
    // Si es el inicio de la búsqueda, se setea las keys
    if( !$path)
    {
      self::$_keys = array();
      self::$_results = $results;
    }
    
    // Recorre los resultados
    foreach( $results as $key => $result)
    {
      // Si el key es numérico lo guarda en self::$_keys, 
      // para después tener anotado el camino del los padres del documento a buscar
      if( is_numeric( $key))
      {
        self::$_keys [$path] = $key;
      }
      
      // Si es el último elemento del path hacemos el recorrido para ver si la id coincide
      if( isset( $result [$lastkey]))
      {
        foreach( $result [$lastkey] as $key2 => $_result)
        {
          self::$_keys [$lastkey] = $key2;
          
          if( isset( $_result ['id']) && $_result ['id'] == $id)
          {
            return array_merge( self::build( $_result), array( 'path' => self::$_keys));
          }
        }
      }
      // Si no, vuelve a llamar al método para seguir búscando
      else
      {  
        foreach( $paths_array as $_path)
        {
          if( isset( $result [$_path]))
          { 
            return self::searchId( $result [$_path], $paths, $id, $_path);
          }
        }
      }
    }
    
    return false;
  }
  
/**
 * Devuelve los resultados de la búsqueda de un id dentro de un array
 * 
 * Retorna una array con dos claves:
 *   'document': El documento a buscar
 *   'document': El recorrido dentro del array hasta llegar a ese documento
 *
 * @param array $result 
 * @return array
 */
  public function build( $result)
  {
    $keys = self::$_keys;
    $alias = key( self::$_results);
    $document= $results = self::$_results;
    
    $return = false;
    
    $count = 1;
    
    if( empty( $keys))
    {
      return array();
    }
    
    $column1 = key( $keys);
    $key1 = current( $keys);
    $subdocument = $results [$alias][$column1][$key1];
    $document[$alias][$column1] = $subdocument;
    
    if( $count == count( $keys))
    {
      return compact( 'subdocument', 'document');
    }
    
    next( $keys);
    $count++;

    $column2 = key( $keys);
    $key2 = current( $keys);
    $subdocument = $results [$alias][$column1][$key1][$column2][$key2];
    $document[$alias][$column1][$column2] = $subdocument;
    
    if( $count == count( $keys))
    {
      return compact( 'subdocument', 'document');
    }
    
    next( $keys);
    $count++;
    
    $column3 = key( $keys);
    $key3 = current( $keys);
    $subdocument = $results [$alias][$column1][$key1][$column2][$key2][$column3][$key3];
    $document[$alias][$column1][$column2][$column3] = $subdocument;
    
    if( $count == count( $keys))
    {
      return compact( 'subdocument', 'document');
    }
    
    next( $keys);
    $count++;
    
    $column4 = key( $keys);
    $key4 = current( $keys);
    $subdocument = $results [$alias][$column1][$key1][$column2][$key2][$column3][$key3][$column4][$key4];
    $document[$alias][$column1][$column2][$column3][$column4] = $subdocument;
    
    if( $count == count( $keys))
    {
      return compact( 'subdocument', 'document');
    }
    
    next( $keys);
    $count++;
    
    $column5 = key( $keys);
    $key5 = current( $keys);
    $document[$alias][$column1][$column2][$column3][$column4][$column5] = 
        $results [$alias][$column1][$key1][$column2][$key2][$column3][$key3][$column4][$key4][$column5][$key5];
    
    return compact( 'subdocument', 'document');
  }
  
  
  public function toString( $path)
  {
    $return = '';
    foreach( $path as $key => $value)
    {
      $return .= "$key.$value.";
    }
    
    return substr( $return, 0, -1);
  }
  
  public function count( $content, $path)
  {
    $alias = key( $content);
    $parts = explode( '.', $path);
    
    $keys = "['$alias']";
    
    foreach( $parts as $key)
    {
      $keys .= "['$key']";
    }
    
    eval( '$count = count( $content'. $keys .');');
    return $count;
  }
  
  public function getParent( $content, $path)
  {
    if( empty( $content ['document']))
    {
      return false;
    }
    
    $document = $content ['document'];
    $alias = key( $document);
    $parts = explode( '.', $path);
    unset( $parts [count( $parts) - 1]);
    
    $keys = "['$alias']";
    
    foreach( $parts as $key)
    {
      $keys .= "['$key']";
    }
    
    eval( '$return = $document'. $keys .';');
    return array(
        'document' => $return,
        'path' => implode( '.', $parts)
    );
  }
}
