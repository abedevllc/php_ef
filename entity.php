<?php

namespace Entity;

if(!defined("EntityFramework")){ die("Access Denied!"); }

class Entity
{
	private $Database;
	private $TablePrefix;
	private $DataSets;
	public $LowerCase;
	public $Pluralize;
	public $DefaultCollation;
	public $Attributes;
	private $Language;
	
	public function __construct($DatabaseType, $DatabaseServer, $DatabaseName, $DatabaseUsername, $DatabasePassword, $DatabaseCharset = "utf8", $TablePrefix = "", $Language = null, $LowerCase = true, $Pluralize = false, $Collation = "utf8_general_ci")
	{
		$this->LowerCase = $LowerCase;
		$this->Pluralize = $Pluralize;
		$this->Collation = $Collation;
		$this->TablePrefix = $TablePrefix;
		$this->Language = $Language;
		
		$this->Initialize($DatabaseType, $DatabaseServer, $DatabaseName, $DatabaseUsername, $DatabasePassword, $DatabaseCharset, $TablePrefix);
	}
	
	private function Initialize($DatabaseType, $DatabaseServer, $DatabaseName, $DatabaseUsername, $DatabasePassword, $DatabaseCharset, $TablePrefix)
	{
		$this->InitializeConstants();
		$this->InitializeDatabase($DatabaseType, $DatabaseServer, $DatabaseName, $DatabaseUsername, $DatabasePassword, $DatabaseCharset, $TablePrefix);
		$this->InitializeIncludes();
	}
	
	private function InitializeConstants()
	{
		if(!defined("DS"))
		{
			define("DS", DIRECTORY_SEPARATOR);
		}
		
		define("ENTITY_BASE_PATH", dirname(__FILE__));
		define("ENTITY_DATABASE_PATH", ENTITY_BASE_PATH . DS . "database");		
		define("ENTITY_MODEL_PATH", ENTITY_BASE_PATH . DS . "model");		
	}
	
	private function InitializeIncludes()
	{
		require_once(ENTITY_MODEL_PATH . DS . "table.php");
		require_once(ENTITY_MODEL_PATH . DS . "attribute.php");
	}
	
	private function InitializeDatabase($DatabaseType, $DatabaseServer, $DatabaseName, $DatabaseUsername, $DatabasePassword, $DatabaseCharset, $TablePrefix)
	{
		switch(strtolower($DatabaseType))
		{
			case "mysqli": 
			
				require_once(ENTITY_DATABASE_PATH . DS . "mysqli.php");			
				$this->Database = new \Entity\Database\Mysqli($DatabaseServer, $DatabaseName, $DatabaseUsername, $DatabasePassword, $DatabaseCharset, $TablePrefix, true, $this->Language);
				
				break;
		}
	}

	private function ConvertDatabaseObject($DbObject, $Table)
	{
		$result = null;
		
		if($DbObject != null && $Table != null && $Table->Class != null && !empty($Table->Class) && $Table->Attributes != null && count($Table->Attributes) > 0)
		{
			$ClassName = $Table->Name . "Model";
			
			if(!class_exists($ClassName))
			{
				eval("class " . $ClassName . " extends " . $Table->Class . " { " .
					"private \$Table;".
					"private \$Entity;".
					"public function __construct(\$Entity, \$Table) { \$this->Entity = \$Entity; \$this->Table = \$Table; } ".
					"public function Include(\$Property, \$TargetTable) { return \$this->Entity->Include(\$this, \$this->Table, \$Property, \$TargetTable); }".
					"public function Includes(\$Property, \$TargetTable) { return \$this->Entity->Includes(\$this, \$this->Table, \$Property, \$TargetTable); }".
					"}");
			}
			
			$result = new $ClassName($this, $Table);

			foreach($result as $key => $value)
			{
				$propertyName = strtolower($key);
				
				if(isset($DbObject->$propertyName))
				{
					$result->$key = $DbObject->$propertyName;
				}
			}
		}
		
		return $result;
	}
			
	private function GetForeignKeys()
	{
		$result = array();
		
		if($this->DataSets != null && count($this->DataSets) > 0)
		{
			foreach($this->DataSets as $Key => $table)
			{
				if($table != null && $table->Attributes != null)
				{
					foreach($table->Attributes as $attribute)
					{
						if($attribute->IsFK)
						{
							array_push($result, $attribute);
						}
					}
				}
			}
		}
		
		return $result;
	}

	public function GetPropertyNameByString($Object, $String)
	{
		$result = null;
		
		if($Object != null)
		{
			foreach ($Object as $key => $value)
			{
				if(strtolower($key) == strtolower($String))
				{
					$result = $key;
					break;
				}
			}
		}
		
		return $result;
	}

	public function InitializeEntities($Entities = null)
	{
		$DataSets = null;

		if($Entities != null)
		{
			$DataSets = $Entities;
		}
		else
		{
			$DataSets = array();

			foreach($this->DataSets as $Key => $table)
			{
				array_push($DataSets, $Key);
			}
		}

		if($DataSets != null && count($DataSets) > 0)
		{
			$hasUpdate = false;
			
			foreach($DataSets as $Key)
			{
				$hasUpdate = $this->Database->CheckTable($this->DataSets[$Key]);
			}
			
			if($hasUpdate)
			{
				$Fkeys = $this->GetForeignKeys();
				
				if($Fkeys != null && count($Fkeys) > 0)
				{
					foreach($Fkeys as $FK)
					{
						$this->Database->CheckConstraint($FK);
					}
				}
			}
		}
	}
	
	public function AddTable($Key, $Class)
	{
		$result = null;
		
		if($Key != null && $Class != null)
		{
			if($this->DataSets == null)
			{
				$this->DataSets = array();
			}
			
			if(!isset($this->DataSets[$Key]))
			{
				$this->DataSets[$Key] = new \Entity\Model\Table($Key, $Class, $this, $this->LowerCase, $this->Pluralize, $this->Collation);
				
				$result = $this->DataSets[$Key];
			}
		}
		
		return $result;			
	}

	public function Table($ObjectTable)
	{
		$result = null;

		if($ObjectTable != null)
		{			
			if($ObjectTable instanceof \Entity\Model\Table)
			{
				$result = $ObjectTable;
			}
			else	
			{
				$DB_Table = $this->Database->GetTable($ObjectTable, $ObjectTable, $ObjectTable);
				
				if($DB_Table != null && $DB_Table->object_key != null && !empty($DB_Table->object_key) && $DB_Table->object_class != null && !empty($DB_Table->object_class))
				{
					$result = new \Entity\Model\Table($DB_Table->object_key, $DB_Table->object_class, $this, $this->LowerCase, $this->Pluralize, $this->Collation);
				}
			}
		}

		return $result;
	}

	public function Get($Table, $Columns = null, $Distincts = null, $Where = null, $Limit = null, $Sort = null, $GroupBy = null)
	{
		$result = null;
		
		$db_result = $this->Database->Get($Table, $Columns, $Distincts, $Where, $Limit, $Sort, $GroupBy);
			
		if($db_result != null)
		{
			if(is_object($db_result) && !isset($db_result->db_function))
			{
				$result = $this->ConvertDatabaseObject($db_result, $Table);
			}
			else if(is_array($db_result) && !isset($db_result->db_function))
			{
				$result = array();

				foreach($db_result as $db_r)
				{
					array_push($result, $this->ConvertDatabaseObject($db_r, $Table));
				}
			}
			else if(isset($db_result->db_function))
			{
				$result = $db_result->db_function;
			}
		}
		
		return $result;
	}

	public function Gets($Table, $Columns = null, $Distincts = null, $Where = null, $Limit = null, $Sort = null, $GroupBy = null)
	{
		$result = null;
		
		$db_result = $this->Database->Gets($Table, $Columns, $Distincts, $Where, $Limit, $Sort, $GroupBy);
	
		if($db_result != null)
		{
			$result = array();

			foreach($db_result as $db_r)
			{
				array_push($result, $this->ConvertDatabaseObject($db_r, $Table));
			}
		}
		
		return $result;
	}

	public function GetByPK($Table, $PK)
	{
		$Table = $this->Table($Table);

		if($Table != null)
		{
			return $Table->GetByPK($PK);
		}

		return null;
	}

	public function Count($Table, $Columns = null, $Distincts = null, $Where = null, $Limit = null, $Sort = null, $GroupBy = null)
	{
		return $this->Database->Count($Table, $Columns, $Distincts, $Where, $Limit, $Sort, $GroupBy);
	}

	public function GetSQL($Table, $Columns = null, $Distincts = null, $Where = null, $Limit = null, $Sort = null, $GroupBy = null)
	{
		return $this->Database->GetSQL($Table, $Columns, $Distincts, $Where, $Limit, $Sort, $GroupBy);
	}

	public function RealEscapeString($String)
	{
		return $this->Database->RealEscapeString($String);
	}

	public function Include($Object, $ObjectTable, $Property, $TargetTable)
	{
		$result = null;
		
		if($Object != null && $ObjectTable != null && $TargetTable != null)
		{
			$ObjectTargetTable = null;
			
			if($TargetTable instanceof \Entity\Model\Table)
			{
				$ObjectTargetTable = $TargetTable;
			}
			else	
			{
				$DB_Table = $this->Database->GetTable($TargetTable, $TargetTable);
				
				if($DB_Table != null && $DB_Table->object_key != null && !empty($DB_Table->object_key) && $DB_Table->object_class != null && !empty($DB_Table->object_class))
				{
					$ObjectTargetTable = new \Entity\Model\Table($DB_Table->object_key, $DB_Table->object_class, $this, $this->LowerCase, $this->Pluralize, $this->Collation);
				}				
			}
			
			if($ObjectTargetTable != null)
			{
				$FKS = $ObjectTable->FKS();
				
				if($FKS != null && count($FKS) > 0)
				{
					$FK = null;
					
					foreach($FKS as $key)
					{
						if(strtolower($key->FK_RefTable) == strtolower($ObjectTargetTable->Name) && $ObjectTargetTable->HasPK($key->FK_RefColumn))
						{
							$FK = $key;
							break;
						}
					}
					
					if($FK != null)
					{
						$propertyName = $this->GetPropertyNameByString($Object, $FK->Name);
					
						if($propertyName != null && !empty($propertyName))
						{
							$Object->$Property = $this->GetByPK($ObjectTargetTable, $Object->$propertyName);							
							$result = $Object;
						}
					}
				}				
			}
		}
		
		return $result;
	}

	public function Includes($Object, $ObjectTable, $Property, $TargetTable)
	{
		$result = null;

		if($Object != null && $ObjectTable != null && $TargetTable != null)
		{
			$ObjectTargetTable = null;
			
			if($TargetTable instanceof \Entity\Model\Table)
			{
				$ObjectTargetTable = $TargetTable;
			}
			else	
			{
				$DB_Table = $this->Database->GetTable(null, $TargetTable);
				
				if($DB_Table != null && $DB_Table->object_key != null && !empty($DB_Table->object_key) && $DB_Table->object_class != null && !empty($DB_Table->object_class))
				{
					$ObjectTargetTable = new \Entity\Model\Table($DB_Table->object_key, $DB_Table->object_class, $this->LowerCase, $this->Pluralize, $this->Collation);
				}
			}
			
			if($ObjectTargetTable != null)
			{
				$FKS = $ObjectTargetTable->FKS();
				
				if($FKS != null && count($FKS) > 0)
				{
					$FK = null;
					
					foreach($FKS as $key)
					{
						if(strtolower($key->FK_RefTable) == strtolower($ObjectTable->Name) && $ObjectTable->HasPK($key->FK_RefColumn))
						{
							$FK = $key;
							break;
						}
					}
					
					$propertyName = $this->GetPropertyNameByString($Object, $FK->FK_RefColumn);
					
					if($FK != null && $propertyName != null && !empty($propertyName) && isset($Object->$propertyName))
					{
						$where = array();

						$obj_where = new \stdClass();
						$obj_where->Column = $FK->Name;
						$obj_where->Operator = "=";
						$obj_where->Value = $Object->$propertyName;
						
						array_push($where, $obj_where);

						$Object->$Property = $this->Gets($ObjectTargetTable, null, null, $where);	

						$result = $Object;
					}
				}				
			}
		}

		return $result;
	}

	public function Add($Object, $Table)
	{
		if($Object != null && $Table != null)
		{
			return $this->Database->Insert($Object, $Table);
		}
		else
		{
			return null;
		}
	}

	public function Validate($Object, $Table)
	{
		if($Object != null && $Table != null)
		{
			return $this->Database->Validate($Object, $Table);
		}
		else
		{
			return null;
		}
	}

	public function Remove($Object, $Table)
	{
		if($Object != null && $Table != null)
		{
			return $this->Database->Remove($Object, $Table);
		}
		else
		{
			return null;
		}
	}

	public function RemoveByPK($PK, $Table)
	{
		if($PK != null && $Table != null)
		{
			return $this->Database->RemoveById($PK, $Table);
		}
		else
		{
			return null;
		}
	}

	public function RemoveByWhere($Where, $Table)
	{
		if($Where != null && $Table != null && count($Where) > 0)
		{
			return $this->Database->RemoveByWhere($Where, $Table);
		}
		else
		{
			return null;
		}
	}

	public function Update($Object, $Table, $Where = null)
	{
		if(is_array($Object))
		{
			return $this->Database->UpdateColumns($Object, $Table, $Where);
		}
		else if($Object != null && $Table != null)
		{
			return $this->Database->Update($Object, $Table);
		}
		else
		{
			return false;
		}
	}

	public function SQL($Query)
	{
		$result = null;

		if($Query != null && !empty($Query))
		{
			$result = $this->Database->SQL($Query);
		}

		return $result;
	}

	public function GetTablePrefix()
	{
		return $this->TablePrefix;
	}

	public function HasPost()
	{
		if(isset($_POST["ef-post-data"]) && isset($_POST["ef-post-data"]))
		{
			return true;
		}
		else
		{
			return false;
		}
	}

	public function PostTable()
	{
		if($this->HasPost())
		{
			$table_key = $_POST["ef-post-data"];
			return $this->Table($table_key);
		}
		else
		{
			return null;
		}
	}
}

?>