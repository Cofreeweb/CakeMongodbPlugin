<?php
/**
 * Tests specific to the sql compatible behavior
 *
 * PHP version 5
 *
 * Copyright (c) 2010, Andy Dawson
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @filesource
 * @copyright     Copyright (c) 2010, Andy Dawson
 * @link          www.ad7six.com
 * @package       mongodb
 * @subpackage    mongodb.tests.cases.behaviors
 * @since         v 1.0 (14-Dec-2010)
 * @license       http://www.opensource.org/licenses/mit-license.php The MIT License
 */


App::uses('Model', 'Model');
App::uses('AppModel', 'Model');


/**
 * SqlCompatiblePost class
 *
 * @uses          Post
 * @package       mongodb
 * @subpackage    mongodb.tests.cases.behaviors
 */
class RevisionPost extends AppModel {

/**
 * useDbConfig property
 *
 * @var string 'test_mongo'
 * @access public
 */
	public $useDbConfig = 'test_mongo';

/**
 * actsAs property
 *
 * @var array
 * @access public
 */
	public $actsAs = array(
		'Mongodb.Schemaless',
    'Mongodb.SqlCompatible',
    'Mongodb.Revision',
	);
  
  
  public $mongoSchema = array(
    'title' => array( 'type' => 'string'),
    'body' => array( 'type' => 'text', 'default' => null),
    'blocks' => array(
        'title' => array( 'type' => 'string'),
        'subtitle' => array( 'type' => 'string'),
        'sort' => array( 'type' => 'integer'),
    		'photos' => array(
    		    'filename' => array( 'type' => 'string'),
    		    'title' => array( 'type' => 'string'),
    		    'comments' => array(
    		        'body' => array( 'type' => 'string')
    		    ),
    		    'sort' => array( 'type' => 'integer'),
    		)
    ),
    'created' => array( 'type' =>'datetime'),
    'modified' => array( 'type' =>'datetime'),
  );
}

/**
 * SqlCompatibleTest class
 *
 * @uses          CakeTestCase
 * @package       mongodb
 * @subpackage    mongodb.tests.cases.behaviors
 */
class RevisionTest extends CakeTestCase {

/**
 * Default db config. overriden by test db connection if present
 *
 * @var array
 * @access protected
 */
	protected $_config = array(
		'datasource' => 'Mongodb.MongodbSource',
		'host' => 'localhost',
		'login' => '',
		'password' => '',
		'database' => 'test_mongo',
		'port' => 27017,
		'prefix' => '',
		'persistent' => false,
	);
 
  
	public function setUp() {
		$connections = ConnectionManager::enumConnectionObjects();

		if (!empty($connections['test']['classname']) && $connections['test']['classname'] === 'mongodbSource') {
			$config = new DATABASE_CONFIG();
			$this->_config = $config->test;
		}

		if(!isset($connections['test_mongo'])) {
			ConnectionManager::create('test_mongo', $this->_config);
			$this->Mongo = new MongodbSource($this->_config);
		}

		$this->Post = ClassRegistry::init( array(
		    'class' => 'RevisionPost', 
		    'alias' => 'Post', 
		    'ds' => 'test_mongo'
		), true);
	}

/**
 * Sets up the environment for each test method
 *
 * @return void
 * @access public
 */
	public function startTest($method) 
	{
		$this->_setupData();
	}
  
/**
 * Crea los datos ficticios para poder hacer las pruebas a partir de $Model->mongoSchema
 *
 * @return void
 * @access protected
 */
	protected function _setupData() 
	{
		$this->Post->deleteAll(true, false);
		$this->Post->primaryKey = '_id';
    
		for ($i = 1; $i <= 4; $i++) 
		{
			$data = $this->_createData( $this->Post->mongoSchema);
      $this->Post->create();
      $this->Post->save( $data, array(
        'revision' => 'draft'
      ));
		}
	}
	
/**
 * Crea los datos ficticios a partir de los campos dados
 *
 * @param string $fields 
 * @param string $is_subdocument 
 * @return array
 */
	protected function _createData( $fields, $is_subdocument = false)
	{
	  $return = array();
	  
	  if( $is_subdocument)
	  {
	    $fields ['id'] = array(
	      'type' => 'mongoid'
	    );
	  }
	  
	  if( $is_subdocument)
	  {
	    for( $i = 1; $i < 4; $i++) 
	    { 
	      $return [] = $this->__createData( $fields, $is_subdocument, $i);
	    }
	    
	    return $return;
	  }
	  
	  return $this->__createData( $fields, $is_subdocument);
	}  
	
	
	private function __createData( $fields, $is_subdocument, $i = false)
	{
	  $db = $this->Post->getDataSource();
	  
	  foreach( $fields as $key => $data)
		{
		  if( isset( $data ['type']) &&  !is_array( $data ['type']) && ($data ['type'] == 'mongoid' || array_key_exists( $data ['type'], $db->columns)))
		  {
		    if( $key == 'sort' && $i)
		    {
		      $return [$key] = $i;
		    }
		    else
		    {
  		    $return [$key] = $this->_record( $data ['type']);
		    }
		  }
		  else
		  {
		    $return [$key] = $this->_createData( $data, true);
		  }
		}
		
		return $return;
	}
	
/**
 * Crea un dato concreto atendiendo al type
 *
 * @param string $type 
 * @return mixed
 */
	protected function _record( $type)
	{
	  $cases = array(
	    'mongoid' => new MongoId(),
	    'string' => 'Lorem ipsum dolor sit amet '. rand( 0, 9999999),
	    'text' => 'Lorem ipsum dolor sit amet '. rand( 0, 9999999),
	    'integer' => rand( 0, 9999),
	    'boolean' => rand( 0, 1),
	    'datetime' => date( 'Y-m-d H:i:s')
	  );
	  
	  return $cases [$type];
	}
	
	
/**
 * Destroys the environment after each test method is run
 *
 * @return void
 * @access public
 */
	public function endTest($method) 
	{
    // $this->Post->deleteAll( true);
	}

	public function tearDown() 
	{
		unset($this->Post);
		ClassRegistry::flush();
	}
  
/**
 * Prueba de una insercion de un subdocumento
 *
 * @return void
 */
  public function testAddSubdocument()
  {
    $post = $this->Post->find( 'first', array(
        'revision' => 'draft'
    ));
    
    
    // Ponemos un subdocumento de primer nivel
    $data = array(
        'title' => 'Un día en la vida',
        'subtitle' => 'Una noche más'
    );
    
    $document_id = $this->Post->addSubdocument( $data, array(
        'id' => $post ['Post']['_id'],
        'path' => 'blocks'
    ));
    
    // Buscamos el id que acabamos de insertar
    $new_post = $this->Post->find( 'first', array(
        'conditions' => array(
            'Post.blocks.id' => $document_id
        ),
        'revision' => 'draft'
    ));
    
    $has = false;

    foreach( $new_post ['Post']['blocks'] as $block)
    {
      if( $data ['title'] == $block ['title'] && $data ['subtitle'] == $block ['subtitle'])
      {
        $has = true;
      }
    }
    
    $this->assertEqual( $has, true);
    
    
    // Documento de segundo nivel
    $data = array(
        'filename' => 'fichero.pdf',
        'title' => 'Una noche en la ópera'
    );
    
    $document_id = $this->Post->addSubdocument( $data, array(
        'id' => $post ['Post']['blocks'][0]['id'],
        'path' => 'blocks.photos'
    ));
    
    $new_post = $this->Post->find( 'first', array(
        'conditions' => array(
            'Post.blocks.photos.id' => $document_id
        ),
        'revision' => 'draft'
    ));

    $has = false;

    foreach( $new_post ['Post']['blocks'][0]['photos'] as $photo)
    {
      if( $data ['title'] == $photo ['title'] && $data ['filename'] == $photo ['filename'])
      {
        $has = true;
      }
    }
    
    $this->assertEqual( $has, true);
    
    
    // Documento de tercer nivel
    $data = array(
        'body' => 'Estamos la mar de contentos',
    );
    
    $document_id = $this->Post->addSubdocument( $data, array(
        'id' => $post ['Post']['blocks'][0]['photos'][0]['id'],
        'path' => 'blocks.photos.comments'
    ));
    
    $new_post = $this->Post->find( 'first', array(
        'conditions' => array(
            'Post.blocks.photos.comments.id' => $document_id
        ),
        'revision' => 'draft'
    ));

    $has = false;
    
    foreach( $new_post ['Post']['blocks'][0]['photos'][0]['comments'] as $comment)
    {
      if( $data ['body'] == $comment ['body'])
      {
        $has = true;
      }
    }

    $this->assertEqual( $has, true);
  }
    
/**
 * Verifica la toma de un subschema dado un path tipo 'column.subcolumn'
 *
 * @return void
 */
  public function testGetSubSchema()
  {
    $_schema = $this->Post->mongoSchema ['blocks']['photos'];
    $schema = $this->Post->getSubSchema( 'blocks.photos');
    $this->assertEqual( Hash::contains( $schema, $_schema), true);
  }
  
/**
 * Verifica el correcto desplazamiento de un subdocumento dentro de otro subdocumento
 *
 * @return void
 */
  public function testMoveSubdocument()
  {
    $post = $this->Post->find( 'first', array(
        'revision' => 'draft'
    ));
    
    $id = $post ['Post']['blocks'][0]['photos'][0]['id'];

    $parent_id = $post ['Post']['blocks'][1]['id'];
    
    $this->Post->moveSubdocument( $id, $parent_id, array(
        'path' => 'blocks.photos'
    ));
    
    $new_post = $this->Post->find( 'first', array(
        'conditions' => array(
            'Post.blocks.photos.id' => $id
        ),
        'revision' => 'draft'
    ));
    
    $document = $this->Post->findSubdocumentById( 'blocks.photos', $id, 'draft');
    
    $this->assertEqual( $document ['document']['Post']['blocks']['id'] == $parent_id, true);
  }
  
  
/**
 * Verifica el borrado correcto de los subdocumentos
 *
 * @return void
 */
  public function testDeleteSubdocument()
  {
    $post = $this->Post->find( 'first', array(
        'revision' => 'draft'
    ));

    // Borra un subdocument de primer nivel
    $id = $post ['Post']['blocks'][0]['id'];
    
    $_post = $this->Post->find( 'first', array(
        'conditions' => array(
            'Post.blocks.id' => $id
        ),
        'revision' => 'draft'
    ));
    
    $this->Post->deleteSubdocument( 'blocks', $id);
    
    $new_post = $this->Post->find( 'first', array(
        'conditions' => array(
            'Post.blocks.id' => $id
        ),
        'revision' => 'draft'
    ));
    
    // Pasará la prueba si primero busca el id y luego no
    $this->assertEqual( $_post && !$new_post, true);
    
    // Borra un subdocument de segundo nivel
    $id = $post ['Post']['blocks'][1]['photos'][0]['id'];
    
    $_post = $this->Post->find( 'first', array(
        'conditions' => array(
            'Post.blocks.photos.id' => $id
        ),
        'revision' => 'draft'
    ));
    
    $this->Post->deleteSubdocument( 'blocks.photos', $id);
    
    $new_post = $this->Post->find( 'first', array(
        'conditions' => array(
            'Post.blocks.photos.id' => $id
        ),
        'revision' => 'draft'
    ));
    
    // Pasará la prueba si primero busca el id y luego no
    $this->assertEqual( $_post && !$new_post, true);
  }
  
  
/**
 * Comprueba que actualiza bien un campo de los subdocumentos, en concreto el 'sort'
 * Lo hace con un foreach, actualizando varios, con lo que aseguramos que se actualizan bien los subdocumentos
 *
 * @return void
 */
  public function testSortSubdocuments()
  {
    // Primer nivel
    $post = $this->Post->find( 'first', array(
        'revision' => 'draft'
    ));
    
    $blocks = $post ['Post']['blocks'];
    $blocks = array_reverse( $blocks);
    
    foreach( $blocks as $key => $block)
    {
      $this->Post->updateSubdocument( 'blocks', $block ['id'], array(
          'sort' => $key
      ), array(
          'revision' => 'draft'
      ));
      
      $document = $this->Post->findSubdocumentById( 'blocks', $block ['id'], 'draft');
      $this->assertEqual( $document ['subdocument']['sort'] == $key, true);
    }
    
    
    // Segundo nivel
    $post = $this->Post->find( 'first', array(
        'revision' => 'draft'
    ));
    
    $photos = $post ['Post']['blocks'][0]['photos'];
    $photos = array_reverse( $photos);

    foreach( $photos as $key => $photo)
    {
      $this->Post->updateSubdocument( 'blocks.photos', $photo ['id'], array(
          'sort' => $key
      ), array(
          'revision' => 'draft'
      ));
      
      $document = $this->Post->findSubdocumentById( 'blocks.photos', $photo ['id'], 'draft');
      $this->assertEqual( $document ['subdocument']['sort'] == $key, true);
    }
  }
  
}
