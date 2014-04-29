<?php
/**
 * PostFixture
 *
 */
class PostFixture extends CakeTestFixture {

/**
 * Fields
 *
 * @var array
 */
	public $fields = array(
		'title' => array( 'type' => 'string'),
    'body' => array( 'type' => 'text', 'default' => null),
    'photos' => array(
        'filename' => array( 'type' => 'string'),
    		'title' => array('type' => 'string', 'default' => NULL),
    		'options' => array(
    		    'title' => array('type' => 'string', 'default' => NULL),
    		    'text' => array('type' => 'string', 'default' => NULL),
    		)   		
    ),
    'created' => array( 'type' =>'datetime'),
    'modified' => array( 'type' =>'datetime'),
	);

/**
 * Records
 *
 * @var array
 */
	public $records = array(
		array(
			'id' => '5331e57ccae8b72623000000',
			'title' => 'Lorem ipsum dolor sit amet',
			'body' => 'Lorem ipsum dolor sit amet, aliquet feugiat. Convallis morbi fringilla gravida, phasellus feugiat dapibus velit nunc, pulvinar eget sollicitudin venenatis cum nullam, vivamus ut a sed, mollitia lectus. Nulla vestibulum massa neque ut et, id hendrerit sit, feugiat in taciti enim proin nibh, tempor dignissim, rhoncus duis vestibulum nunc mattis convallis.',
			'modified' => '2014-03-31 15:24:50'
		),
	
	);

}
