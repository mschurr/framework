<?php
/* This could really use some sort of memory caching... will add later. */

class Group_Service_Provider_db extends Group_Service_Provider
{	
	protected /*Database*/ $db;

	public function __construct()
	{
		$this->db = App::getDatabase();
	}

	public /*Group_Provider*/ function load(/*int*/$guid)
	{
		$group = new Group_Provider_db($guid);
		
		if($group->valid())
			return $group;
		return null;
	}
	
	public /*array<Group_Provider>*/ function groups(/*int*/ $offset=0, /*int*/$limit=50)
	{
		$query = $this->db->query("SELECT `groupid` FROM `groups` ORDER BY `groupid` ASC LIMIT ".(int)$offset.", ".(int)$limit.";");
		
		$result = array();
		
		foreach($query as $row) {
			$result[] = new Group_Provider_db($row['groupid']);
		}
		
		return $result;
	}
	
	public /*array<Group_Provider>*/ function groupsForUser(User_Provider $user)
	{
		$statement = $this->db->prepare("SELECT `groupid` FROM `group_membership` WHERE `userid` = ?;");
		$query = $statement->execute($user->id());
		
		$result = array();
		
		foreach($query as $row) {
			$result[] = new Group_Provider_db($row['groupid']);
		}
		
		return $result;
	}
	
	public /*Group_Provider*/ function create(/*String*/$name)
	{
		if(str_contains($name, '%'))
			return null;
		
		$statement = $this->db->prepare("SELECT `groupid` FROM `groups` WHERE `name` LIKE ? LIMIT 1;");
		$query = $statement->execute($name);
		
		if(len($query) > 0)
			return null;
		
		$statement = $this->db->prepare("INSERT INTO `groups` (`name`) VALUES (?);");
		$query = $statement->execute($name);
		return new Group_Provider_db($query->insertId);
	}
	
	public /*void*/ function delete(/*Group_Provider*/$group)
	{
		$statement = $this->db->prepare("DELETE FROM `groups` WHERE `groupid` = ?;");
		$query = $statement->execute($group->id());
	}
}

class Group_Provider_db extends Group_Provider
{
	protected $id;
	protected $name;
	protected $valid = false;
	protected $db;
	
	public /*void*/ function __construct(/*int*/$id)
	{
		$this->db = App::getDatabase();
		$this->id = $id;
		
		$statement = $this->db->prepare("SELECT * FROM `groups` WHERE `groupid` = ?;");	
		$query = $statement->execute($this->id);
		
		if(len($query) > 0) {
			$this->valid = true;
			$this->name = $query['name'];
		}
	}
	
	public /*void*/ function __destruct()
	{
	}
	
	public /*int*/ function id()
	{
		return $this->id;
	}
	
	public /*bool*/ function valid()
	{
		return $this->valid;
	}
	
	public /*string*/ function name()
	{
		return $this->name;
	}
	
	public /*void*/ function setName(/*string*/$name)
	{
		$statement = $this->db->prepare("SELECT `groupid` FROM `groups` WHERE `name` LIKE ? LIMIT 1;");
		$query = $statement->execute($name);
		
		if(len($query) > 0)
			throw new GroupServiceException("That name is already in use.");

		$this->name = $name;
		$statement = $this->db->prepare("UPDATE `groups` SET `name` = ? WHERE `groupid` = ?;");
		$statement->execute($name, $this->id);
	}
	
	protected $privileges;
	public /*array<int>*/ function privileges()
	{
		if($this->privileges === null) {
			$statement = $this->db->prepare("SELECT `privilegeid` FROM `group_privileges` WHERE `groupid` = ?;");
			$query = $statement->execute($this->id);
			
			$this->privileges = array();
			
			foreach($query as $row) {
				$this->privileges[] = (int) $row['privilegeid'];
			}
		}
		
		return $this->privileges;
	}

	public /*bool*/ function hasPrivilege(/*int*/$id)
	{
		return in_array($id, $this->privileges());
	}
	
	public /*void*/ function addPrivilege(/*int*/$id)
	{
		if($this->hasPrivilege($id))
			return;
			
		$this->privileges[] = (int) $id;
		
		$statement = $this->db->prepare("INSERT INTO `group_privileges` (`groupid`, `privilegeid`) VALUES (?, ?);");
		$statement->execute($this->id, $id);
	}
	
	public /*void*/ function removePrivilege(/*int*/$id)
	{
		if(!$this->hasPrivilege($id))
			return;
			
		$this->privileges = array_diff($this->privileges, array($id));
					
		$statement = $this->db->prepare("DELETE FROM `group_privileges` WHERE `groupid` = ? AND `privilegeid` = ?;");
		$statement->execute($this->id, $id);
	}
		
	public /*array<User_Provider>*/ function users(/*int*/ $offset=0, /*int*/$limit=50)
	{
		$statement = $this->db->prepare("SELECT `userid` FROM `group_membership` WHERE `groupid` = ? ORDER BY `userid` ASC LIMIT ".(int)$offset.", ".(int)$limit.";");
		$query = $statement->execute($this->id);
		
		$result = array();
		
		foreach($query as $row) {
			$result[] = User_Service_Provider::load($row['userid']);
		}
		
		return $result;
	}
	
	public /*bool*/ function hasUser(/*User_Provider*/$user)
	{
		$statement = $this->db->prepare("SELECT * FROM `group_membership` WHERE `groupid` = ? AND `userid` = ?;");
		$query = $statement->execute($this->id, $user->id());
		return len($query) > 0;
	}
	
	public /*void*/ function removeUser(/*User_Provider*/$user)
	{
		$statement = $this->db->prepare("DELETE FROM `group_membership` WHERE `groupid` = ? AND `userid` = ?;");
		$statement->execute($this->id, $user->id());
	}
	
	public /*void*/ function addUser(/*User_Provider*/$user)
	{
		$statement = $this->db->prepare("INSERT INTO `group_membership` (`groupid`, `userid`) VALUES (?, ?);");
		$statement->execute($this->id, $user->id());
	}
}

/*
CRON::register('24h',function(){
	// Clean up references to non-existant groups.
})
*/
