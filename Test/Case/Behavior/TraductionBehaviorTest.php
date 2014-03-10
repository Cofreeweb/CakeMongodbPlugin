<?php
/**
 * Tests subset validations
 *
 * PHP version 5
 *
 * Copyleft (c) 2013, Cofreeweb
 *
 */
App::uses('Model', 'Model');
App::uses('AppModel', 'Model');

/**
 * MyCompany class
 *
 * @uses          Post
 * @package       Mongodb
 * @subpackage    Mongodb.Test.Case.Behavior
 */
class MyCompany extends AppModel {

/**
 * useDbConfig property
 * DataSource automatically prepend 'test_' to this name
 *
 * @var string 'translate'
 * @access public
 */
    public $useDbConfig = 'translate';

/**
 * useTable property
 * Collection name
 *
 * @var string 'pruebas'
 * @access public
 */
    public $useTable = 'pruebas';


/**
 * mongoSchema property
 * MongoDb Schema for this model
 *
 * @var array
 * @access public
 */
    public $mongoSchema = array(
        'title'  => array('type' => 'string'),
    );

/**
 * actsAs property
 *
 * @var array
 * @access public
 */
    public $actsAs = array(
        'Mongodb.Traduction'
    );

/**
 * validate property
 *
 * @var array
 * @access public
 */
    public $validate = array(
        'title' => 'notempty'
    );

/**
 * collection validate property
 *
 * @var array
 * @access public
 */
    public $collectionValidate = array(
        'title' => 'notempty'
    );
}

/**
 * TraductionBehaviorTest class
 *
 * @uses          CakeTestCase
 * @package       Mongodb
 * @subpackage    Mongodb.Test.Case.Behavior
 */
class TraductionBehaviorTest extends CakeTestCase {

/**
 * Sets up the environment for each test method
 *
 * @return void
 * @access public
 */
    public function setUp() {
        $this->Company = ClassRegistry::init(array('class' => 'MyCompany', 'ds' => 'pruebas'), true);
    }

    public function startTest($method) {
      //clear Company attributes
      $this->Company->create();
    }

/**
 * Destroys the environment after each test method is run
 *
 * @return void
 * @access public
 */
    public function tearDown() {
        unset($this->Company);
    }
/**
 * testValidateFailure method
 *
 * @return void
 * @access public
 */
    // TODO
    // public function testValidateFailure() {
    //     $expected = false;
    //     $result = $this->Company->save(array(
    //         'title' => 'cofreeweb',
    //     ));
    //     $this->assertEqual($expected, $result);

    //     $expected = array('title' => array('only letters and numbers'));
    //     $result = $this->Company->validationErrors;
    //     $this->assertEqual($expected, $result);
    // }

/**
 * testValidateSuccess method
 *
 * @return void
 * @access public
 */
    public function testValidateSuccess() {
        $data = array(
            'title' => 'cofreeweb',
        );

        $result = $this->Company->save($data);
        $this->assertNotEmpty($result);

        $expected = array();
        $result = $this->Company->validationErrors;
        $this->assertEqual($expected, $result);
    }

}