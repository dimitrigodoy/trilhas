<?php
class ChatRoomMessage extends Table
{
	protected $_name    = "trails_chat_room_message";
	protected $_primary = array( "chat_room_id" , "chat_id" , "person_id" );	
	
	public $filters = array(
		'*'  => 'StringTrim',
		'chat_room_id' => 'Int'
	);
	
	public $validators = array( 
		'chat_room_id'		=> array(  'Int' ,  'NotEmpty' ), 
		'chat_id'		=> array(  'Int' ,  'NotEmpty' ), 
		'person_id'		=> array(  'Int' ,  'NotEmpty' ) 
	);
	
	protected $_dependentTables = array(  );
	
	protected $_referenceMap = array( 
		array(
			 'refTableClass' => 'Person',
			 'refColumns' => array( 'id' ),
			 'columns' => array( 'person_id' )
		),
		array(
			 'refTableClass' => 'Chat',
			 'refColumns' => array( 'id' ),
			 'columns' => array( 'chat_id' )
		),
		array(
			 'refTableClass' => 'ChatRoom',
			 'refColumns' => array( 'id' ),
			 'columns' => array( 'chat_room_id' )
		) 
	);
	
	public function fetchMessage()
	{
		$room = new Zend_Session_Namespace("room");
		
		if( $room->ids )
		{
			$ids = join( "," , $room->ids );
			$db  = $this->getAdapter();
			$sql = "
				SELECT person_id , ds , created
				FROM trails_chat_room_message
				JOIN trails_chat ON chat_id = chat.id
				WHERE trails_chat_room_id IN ($ids) 
				AND created > ( CURRENT_TIMESTAMP - INTERVAL '10 seconds' )
				ORDER BY created";
			
			return $db->fetchAll( $sql );
		}
		else
			return array();
	}
}