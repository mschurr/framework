<?php
/* This could really use some sort of caching. */

abstract class Group_Service_Provider_db
{	
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
	
	public abstract /*array<Group_Provider>*/ function groupsForUser(User_Provider $user)
	{
		$statement = $this->db->prepare("SELECT `groupid` FROM `group_membership` WHERE `userid` = ?;");
		$query = $statement->execute($user->id());
		
		$result = array();
		
		foreach($query as $row) {
			$result[] = new Group_Provider_db($row['groupid']);
		}
		
		return $result;
	}
	
	public abstract /*Group_Provider*/ function create(/*String*/$name)
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
	
	public abstract /*void*/ function delete(/*Group_Provider*/$group)
	{
		$statement = $this->db->prepare("DELETE FROM `groups` WHERE `groupid` = ?;");
		$query = $statement->execute($group->id());
	}
}

abstract class Group_Provider_db
{
	protected $id;
	protected $name;
	protected $valid = false;
	
	public /*void*/ function __construct(/*int*/$id)
	{
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
	
	public abstract /*void*/ function setName(/*string*/$name)
	{
		$this->name = $name;
		$statement = $this->db->prepare("UPDATE `groups` SET `name` = ? WHERE `groupid` = ?;");
		$statement->execute($name, $this->id);
	}
	
	protected $privelages;
	public abstract /*array<int>*/ function privelages()
	{
		if($this->privelages === null) {
			$statement = $this->db->prepare("SELECT `privelageid` FROM `group_privelages` WHERE `groupid` = ?;");
			$query = $statement->execute($this->id);
			
			$this->privelages = array();
			
			foreach($query as $row) {
				$this->privelages[] = (int) $row['privelageid'];
			}
		}
		
		return $this->privelages;
	}

	public abstract /*bool*/ function hasPrivelage(/*int*/$id)
	{
		return in_array($id, $this->privelages());
	}
	
	public abstract /*void*/ function addPrivelage(/*int*/$id)
	{
		if($this->hasPrivelage($id))
			return;
			
		$this->privelages[] = (int) $id;
		
		$statement = $this->db->prepare("INSERT INTO `group_privelages` (`groupid`, `privelageid`) VALUES (?, ?);");
		$statement->execute($this->id, $id);
	}
	
	public abstract /*void*/ function removePrivelage(/*int*/$id)
	{
		if(!$this->hasPrivelage($id))
			return;
			
		$this->privelages = array_diff($this->privelages, array($id));
					
		$statement = $this->db->prepare("DELETE FROM `group_privelages` WHERE `groupid` = ? AND `privelageid` = ?;");
		$statement->execute($this->id, $id);
	}
		
	public abstract /*array<User_Provider>*/ function users(/*int*/ $offset=0, /*int*/$limit=50)
	{
		$statement = $this->db->prepare("SELECT `userid` FROM `group_membership` WHERE `groupid` = ? ORDER BY `userid` ASC LIMIT ".(int)$offset.", ".(int)$limit.";");
		$query = $statement->execute($this->id);
		
		$result = array();
		
		foreach($query as $row) {
			$result[] = User_Service_Provider::load($row['userid']);
		}
		
		return $result;
	}
	
	public abstract /*bool*/ function hasUser(/*User_Provider*/$user)
	{
		$statement = $this->db->prepare("SELECT * FROM `group_membership` WHERE `groupid` = ? AND `userid` = ?;");
		$query = $statement->execute($this->id, $user->id());
		return len($query) > 0;
	}
	
	public abstract /*void*/ function removeUser(/*User_Provider*/$user)
	{
		$statement = $this->db->prepare("DELETE FROM `group_membership` WHERE `groupid` = ? AND `userid` = ?;");
		$statement->execute($this->id, $user->id());
	}
	
	public abstract /*void*/ function addUser(/*User_Provider*/$user)
	{
		$statement = $this->db->prepare("INSERT INTO `group_membership` (`groupid`, `userid`) VALUES (?, ?);");
		$statement->execute($this->id, $user->id());
	}
}
