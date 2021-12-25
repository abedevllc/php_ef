<?php

namespace Entity\Database;

if(!defined("EntityFramework")){ die("Access Denied!"); }

class Mysqli
{
	private $Server;
	private $Database;
	private $TablePrefix;
	private $Username;
	private $Password;
	private $Charset;
	private $EntityFrameworkHistoryTable = "ef_history";
	private $Language;
	
	public function __construct($Server, $Database, $Username, $Password, $Charset = "utf8", $TablePrefix = "", $Language = null)
	{
		$this->Server = $Server;
		$this->Database = $Database;
		$this->Username = $Username;
		$this->Password = $Password;
		$this->Charset = $Charset;
		$this->TablePrefix = $TablePrefix;
		$this->Language = $Language;
				
		if(!defined("ENTITY_DATABASE_HISTORY_TABLE"))
		{
			define("ENTITY_DATABASE_HISTORY_TABLE", "ef_history");
		}
		
		$this->DataSets = array();
		$this->Initialize();
	}
	
	private function GetDb()
	{
		$db = new \mysqli($this->Server, $this->Username, $this->Password, $this->Database);
		
		if(!$db->connect_errno)
		{
			$db->set_charset($this->Charset);
		}
		
		// Database not exists
		if($db->connect_errno == 1049)
		{			
			$db = new \mysqli($this->Server, $this->Username, $this->Password);
			
			if(!$db->connect_errno)
			{				
				$db->set_charset($this->Charset);
		
				if($db->query("create database " . $db->real_escape_string(strtolower($this->Database)) . " character set " . $db->real_escape_string(strtolower($this->Charset)) . " collate utf8_bin;"))
				{	
					$db->close();
					$db = new \mysqli($this->Server, $this->Username, $this->Password, $this->Database);
			
					if($db)
					{
						$db->set_charset($this->Charset);
					}
				}
			}
		}
		
		return $db;
	}
	
	private function Initialize()
	{
		$result = false;
		
		$db = $this->GetDb();
		
		if($db)
		{
			$this->EntityFrameworkHistoryTable = strtolower($db->real_escape_string($this->TablePrefix)). ENTITY_DATABASE_HISTORY_TABLE;
			
			if($db->query("create table if not exists " . $db->real_escape_string($this->EntityFrameworkHistoryTable) . " (".
				"id int(11) auto_increment primary key,".
				"object_key varchar(100) not null,".
				"object_class varchar(255) not null,".
				"object_name varchar(100) not null,".
				"object_md5_value varchar(50) not null,".
				"object_value text not null,".
				"object_date timestamp)Engine=InnoDB;"))
			{
				$result = true;
			}
			
			$db->close();
		}
		
		return $result;
	}
	
	private function RegisterTableInformation($Table)
	{
		$result = false;
		
		if($Table != null && $Table->Name != null && !empty($Table->Name) && $this->EntityFrameworkHistoryTable != null && $Table->Attributes != null && count($Table->Attributes) > 0)
		{
			$db = $this->GetDb();
			
			if($db)
			{
				if($cmd = $db->prepare("insert into " . $db->real_escape_string($this->EntityFrameworkHistoryTable) . "(object_name, object_key, object_class, object_md5_value, object_value) VALUES(?, ?, ?, ?, ?)"))
				{
					$table_name = strtolower($Table->Name);
					$table_key = strtolower($Table->Key);
					$table_class = $Table->Class;
					$table_value = $Table->GetValue();
					$table_md5_value = $Table->GetMD5Value();
					
					$cmd->bind_param("sssss", $table_name, $table_key, $table_class, $table_md5_value, $table_value);
					
					if($cmd->execute())
					{
						$result = true;
					}
					
					
					$cmd->close();
				}
				
				$db->close();
			}		
		}
		
		return $result;
	}
	
	private function UpdateTableInformation($Table)
	{
		$result = false;
		
		if($Table != null && $Table->Name != null && !empty($Table->Name) && $this->EntityFrameworkHistoryTable != null && $Table->Attributes != null && count($Table->Attributes) > 0)
		{
			$db = $this->GetDb();
			
			if($db)
			{
				if($cmd = $db->prepare("update " . $db->real_escape_string($this->EntityFrameworkHistoryTable) . " set object_md5_value = ?, object_value = ? where object_name = ?"))
				{
					$table_name = strtolower($Table->Name);
					$table_value = $Table->GetValue();
					$table_md5_value = $Table->GetMD5Value();
					
					$cmd->bind_param("sss", $table_md5_value, $table_value, $table_name);
					
					if($cmd->execute())
					{
						$result = true;						
					}
					
					$cmd->close();
				}
			}
			
			$db->close();
		}
		
		return $result;
	}
	
	private function CreateTable($Table)
	{
		$result = false;
		
		if($Table != null && !empty($Table->Name) && $Table->Attributes != null && count($Table->Attributes) > 0)
		{
			$db = $this->GetDb();
			
			if($db)
			{
				$columns = "";
				$indexes = array();
				$multiple_pks = "";
				$has_multiple_pks = false;

				$PKS = $Table->PKS();

				if($PKS != null && count($PKS) > 1)
				{
					$has_multiple_pks = true;

					foreach($PKS as $PK_Key)
					{
						$multiple_pks = $multiple_pks . $db->real_escape_string($PK_Key->Name) . ",";
					}

					$multiple_pks = rtrim($multiple_pks, " ");
					$multiple_pks = rtrim($multiple_pks, ",");

					$multiple_pks = " ,primary key(" . $multiple_pks . ")";
				}

				foreach($Table->Attributes as $Attribute)
				{
					$type = " " . $Attribute->Type;
					
					if($type != "date" && $type != "datetime" && $type != "timestamp" && $type != "time" && $type != "year" && $Attribute->Length > 0)
					{
						$type = $type . "(" . $Attribute->Length . ")";
					}
					
					$auto_increment = ($Attribute->IsAutoIncrement) ? " auto_increment" : "";
					$pk = (($Attribute->IsPK) && !$has_multiple_pks) ? " primary key" : "";
					$unique = ($Attribute->IsUnique) ? " unique" : "";
					$allow_null = (!$Attribute->IsPK && !$Attribute->IsIndex && !$Attribute->IsUnique && $Attribute->AllowNull) ? " null" : " not null";
					$default = ($Attribute->DefaultValue != null && !empty($Attribute->DefaultValue)) ? " default ". $db->real_escape_string($Attribute->DefaultValue) . "" : "";
					$collation = ($Attribute->Collation != null && !empty($Attribute->Collation)) ? " collate '". $db->real_escape_string($Attribute->Collation) . "'" : "";
										
					$columns = $columns . $db->real_escape_string($Attribute->Name) . $db->real_escape_string($type) . $auto_increment .  $pk . $unique . $allow_null . $default . $collation . ", ";
					
					if($Attribute->IsIndex)
					{
						array_push($indexes, $Attribute);
					}
				}			
				
				$columns = rtrim($columns, " ");
				$columns = rtrim($columns, ",");
				
				if($indexes != null && count($indexes) > 0)
				{
					$columns = $columns . ", ";
					
					foreach($indexes as $index)
					{
						$columns = $columns . "index(" . $db->real_escape_string($index->Name) . "(". $index->Length . ")), ";
					}
					
					$columns = rtrim($columns, " ");
					$columns = rtrim($columns, ",");
				}
				
				$query = "create table if not exists " . $db->real_escape_string($this->TablePrefix. $Table->Name) . " (" . $columns . $multiple_pks . ")Engine=InnoDB;";
			
				if($db->query($query))
				{
					$result = true;
				}
			}
			
			$db->close();			
		}
		
		return $result;
	}

	private function UpdateTable($DB_Table, $Table)
	{
		$result = false;
		
		if($Table != null && $Table->Attributes != null && count($Table->Attributes) > 0 && $DB_Table != null && !empty($Table->Name) && $DB_Table->object_value != null && !empty($DB_Table->object_value))
		{
			$Added_Attributes = array();
			$Removed_Attributes = array();
			$Modified_Attributes = array();
			$FKeys = array();
			
			$DB_Table_Object = json_decode($DB_Table->object_value);
			
			if($DB_Table_Object != null && $DB_Table_Object->Attributes != null &&  count($DB_Table_Object->Attributes) > 0)
			{
				$Attributes = $Table->Attributes;
				$DB_Attributes = $DB_Table_Object->Attributes;
				
				foreach($Attributes as $Attribute)
				{
					$foundAttributes = array_filter($DB_Attributes, function($DB_Attribute) use ($Attribute) 
					{
						return $DB_Attribute->Name == $Attribute->Name;
					});
					
					if($foundAttributes == null || count($foundAttributes) <= 0)
					{
						array_push($Added_Attributes, $Attribute);
					}
					else 
					{
						if(count($foundAttributes) >= 1)
						{
							$array_keys = array_keys($foundAttributes);
							
							if($array_keys != null && count($array_keys) > 0)
							{
								$key = $array_keys[0];
								$foundAttribute = $foundAttributes[$key];
								
								if($foundAttribute->Type != $Attribute->Type || $foundAttribute->Length != $Attribute->Length || $foundAttribute->IsPK != $Attribute->IsPK || $foundAttribute->IsFK != $Attribute->IsFK || $foundAttribute->IsAutoIncrement != $Attribute->IsAutoIncrement || $foundAttribute->FK_RefTable != $Attribute->FK_RefTable || $foundAttribute->FK_RefColumn != $Attribute->FK_RefColumn || $foundAttribute->DefaultValue != $Attribute->DefaultValue || $foundAttribute->Collation != $Attribute->Collation || $foundAttribute->AllowNull != $Attribute->AllowNull || $foundAttribute->IsIndex != $Attribute->IsIndex || $foundAttribute->IsUnique != $Attribute->IsUnique)
								{
									array_push($Modified_Attributes, $Attribute);
								}
								
								if($Attribute->IsFK && $foundAttribute->FK_RefTable != null && !empty($foundAttribute->FK_RefTable) && $foundAttribute->FK_RefColumn != null && !empty($foundAttribute->FK_RefColumn))
								{
									array_push($FKeys, $Attribute);
								}
							}
						}
					}
				}
				
				foreach($DB_Attributes as $DB_Attribute)
				{
					$found = array_filter($Attributes, function($Attribute) use ($DB_Attribute) 
					{
						return $Attribute->Name == $DB_Attribute->Name;
					});
					
					if(!$found)
					{
						array_push($Removed_Attributes, $DB_Attribute);
					}
				}
			}
			
			$Added_successfully = true;
			$Removed_successfully = true;
			$Modified_successfully = true;
			
			if($Added_Attributes != null && count($Added_Attributes) > 0)
			{
				foreach($Added_Attributes as $Attribute)
				{
					if(!$this->AddTableColumn($Attribute))
					{
						$Added_successfully = false;
					}
				}
			}
			
			if($Removed_Attributes != null && count($Removed_Attributes) > 0)
			{
				foreach($Removed_Attributes as $Attribute)
				{
					if(!$this->RemoveTableColumn($Attribute))
					{
						$Removed_successfully = false;
					}
				}
			}
			
			if($Modified_Attributes != null && count($Modified_Attributes) > 0)
			{
				foreach($Modified_Attributes as $Attribute)
				{
					if(!$this->UpdateTableColumn($Attribute))
					{
						$Modified_successfully = false;
					}
				}
			}
			
			if($Added_successfully && $Removed_successfully && $Modified_successfully)
			{
				if($this->UpdateTableInformation($Table))
				{
					if($FKeys != null && count($FKeys) > 0)
					{
						foreach($FKeys as $FK)
						{
							$this->CheckConstraint($FK);
						}
					}
					
					$result = true;
				}
			}
		}
		
		return $result;
	}
		
	private function TableExists($Table)
	{
		$result = false;
				
		if($Table != null && !empty($Table->Name))
		{
			if($db)
			{
				$db = $this->GetDb();
				
				if($cmd = $db->prepare("SELECT id, object_name FROM " . $db->real_escape_string($this->EntityFrameworkHistoryTable) . " WHERE object_name = ?"))
				{
					$table_name = strtolower($Table->Name);
					$cmd->bind_param("s", $table_name);
					
					if($cmd->execute())
					{
						$cmd->bind_result($id, $object_name);
						$cmd->fetch();		
						
						if($id != null && is_numeric($id) && $id > 0 && $object_name != null && !empty($object_name) && $object_name == $Table->Name)
						{
							$result = true;
						}
					}
					
					$cmd->close();
				}
			}
			
			$db->close();
		}
		
		return $result;
	}
	
	public function GetTable($ObjectName, $TableKey = null, $TableClass = null)
	{
		$result = null;
		
		if(($ObjectName != null && !empty($ObjectName)) || ($TableKey != null && !empty($TableKey)) || ($TableClass != null && !empty($TableClass)))
		{
			$db = $this->GetDb();
			
			if($db)
			{
				if($cmd = $db->prepare("SELECT id, object_name, object_key, object_class, object_md5_value, object_value, object_date FROM " . $db->real_escape_string($this->EntityFrameworkHistoryTable) . " WHERE (object_name = ? or object_key = ? or object_class = ?)"))
				{
					$ObjectName = strtolower($ObjectName);
					$TableKey = strtolower($TableKey);
					$TableClass = strtolower($TableClass);
					
					$cmd->bind_param("sss", $ObjectName, $TableKey, $TableClass);
					
					if($cmd->execute())
					{
						$cmd->bind_result($id, $object_name, $object_key, $object_class, $object_md5_value, $object_value, $object_date);
											
						$cmd->fetch();
							
						if($object_value != null && !empty($object_value))
						{
							$result = new \stdClass();
							$result->id = $id;
							$result->object_name = $object_name;
							$result->object_key = $object_key;
							$result->object_class = $object_class;
							$result->object_md5_value = $object_md5_value;
							$result->object_value = $object_value;
							$result->object_date = $object_date;
						}
					}
					
					$cmd->close();
				}
			}
			
			$db->close();
		}
		
		return $result;
	}
	
	private function AddTableColumn($Attribute)
	{
		$result = false;
		
		if($Attribute != null && $Attribute->Table != null)
		{
			$db = $this->GetDb();
			
			if($db)
			{
				$type = " " . $Attribute->Type;
					
				if($type != "date" && $type != "datetime" && $type != "timestamp" && $type != "time" && $type != "year" && $Attribute->Length > 0)
				{
					$type = $type . "(" . $Attribute->Length . ")";
				}
				
				$auto_increment = ($Attribute->IsAutoIncrement) ? " auto_increment" : "";
				$pk = ($Attribute->IsPK) ? " primary key" : "";
				$unique = ($Attribute->IsUnique) ? " unique" : "";
				$allow_null = (!$Attribute->IsPK && !$Attribute->IsIndex && !$Attribute->IsUnique && $Attribute->AllowNull) ? " null" : " not null";
				$default = ($Attribute->DefaultValue != null && !empty($Attribute->DefaultValue)) ? " default ". $db->real_escape_string($Attribute->DefaultValue) . "" : "";
				$collation = ($Attribute->Collation != null && !empty($Attribute->Collation)) ? " collate '". $db->real_escape_string($Attribute->Collation) . "'" : "";
				
				$column = $db->real_escape_string($Attribute->Name) . $db->real_escape_string($type) . $auto_increment .  $pk . $unique . $allow_null . $default . $collation;
				$index = "";
				
				if($Attribute->IsIndex)
				{
					$index = ", add index(" . $db->real_escape_string($Attribute->Name) . "(" . $db->real_escape_string($Attribute->Length) . "))";
				}
				
				$query = "alter table " . $db->real_escape_string($this->TablePrefix . $Attribute->Table) . " add " . $column . $index . ";";
				
				if($db->query($query))
				{
					$result = true;
				}
				
				$db->close();
			}
		}
		
		return $result;
	}
	
	private function RemoveTableColumn($Attribute)
	{
		$result = false;
		
		if($Attribute != null && $Attribute->Table != null)
		{
			$db = $this->GetDb();
			
			if($db)
			{				
				$query = "alter table " . $db->real_escape_string($this->TablePrefix . $Attribute->Table) . " drop column " . $db->real_escape_string($Attribute->Name) . ";";
				
				if($db->query($query))
				{
					$result = true;
				}
				
				$db->close();
			}
		}
		
		return $result;
	}
	
	private function UpdateTableColumn($Attribute)
	{
		$result = false;
		
		if($Attribute != null && $Attribute->Table != null)
		{
			$db = $this->GetDb();
			
			if($db)
			{				
				$type = " " . $Attribute->Type;
					
				if($type != "date" && $type != "datetime" && $type != "timestamp" && $type != "time" && $type != "year" && $Attribute->Length > 0)
				{
					$type = $type . "(" . $Attribute->Length . ")";
				}
				
				$auto_increment = ($Attribute->IsAutoIncrement) ? " auto_increment" : "";
				$pk = ($Attribute->IsPK) ? " primary key" : "";
				$unique = ($Attribute->IsUnique) ? " unique" : "";
				$allow_null = (!$Attribute->IsPK && !$Attribute->IsIndex && !$Attribute->IsUnique && $Attribute->AllowNull) ? " null" : " not null";
				$default = ($Attribute->DefaultValue != null && !empty($Attribute->DefaultValue)) ? " default ". $db->real_escape_string($Attribute->DefaultValue) . "" : "";
				$collation = ($Attribute->Collation != null && !empty($Attribute->Collation)) ? " collate '". $db->real_escape_string($Attribute->Collation) . "'" : "";
				
				$column = $db->real_escape_string($Attribute->Name) . $db->real_escape_string($type) . $auto_increment .  $pk . $unique . $allow_null . $default . $collation;
				
				$index = "";
				
				if($Attribute->IsIndex)
				{
					$index = ", add index(" . $db->real_escape_string($Attribute->Name) . "(" . $db->real_escape_string($Attribute->Length) . "))";
				}
				
				$query = "alter table " . $db->real_escape_string($this->TablePrefix . $Attribute->Table) . " modify " . $column . $index . ";";
				
				if($db->query($query))
				{
					$result = true;
				}
				
				$db->close();
			}
		}
		
		return $result;
	}
	
	private function GetObjectKeyValue($Object)
	{
		$result = array();
		
		if($Object != null)
		{
			foreach ($Object as $key => $value)
			{
				$result[strtolower($key)] = $value;
			}
		}
		
		return $result;
	}
	
	private function GetParamLetterByType($Type)
	{
		$result = "";
		
		if($Type == "int")
		{
			$result = "i";
		}
		else if($Type == "double" || $Type == "float" || $Type == "decimal" || $Type == "real")
		{
			$result = "d";
		}
		else if($Type == "blob" || $Type == "tinyblob" || $Type == "mediumblob" || $Type == "longblob")
		{
			$result = "b";
		}
		else
		{
			$result = "s";
		}
		
		return $result;
	}
	
	private function GetValueByType($db, $Type, $Value)
	{
		if($Value === true)
		{
			return 1;
		}
		else if($Value === false)
		{
			return 0;
		}
		else if(($Value === null || empty($Value) || $Value == "null") && ($Value != 0 || $Value != "0"))
		{
			return 'null';
		}
		else if($Type == "double" || $Type == "float" || $Type == "decimal" || $Type == "real" || $Type == "int" || $Type == "bit" || $Type == "boolean" || $Type == "tinyint")
		{
			return $Value;
		}
		else if($Type == "blob" || $Type == "tinyblob" || $Type == "mediumblob" || $Type == "longblob")
		{
			return "'" . $db->real_escape_string($Value) . "'";
		}
		else
		{
			return "'" . $db->real_escape_string($Value) . "'";
		}
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

	private function CheckDuplicate($Table, $TablePK, $Attribute, $Value, $ObjectPK, $ObjectPKType, $Type)
	{
		$result = false;
	
		if($Table != null && $Attribute != null && $TablePK != null && $Value != null && !empty($Table) && !empty($Attribute) && !empty($Value))
		{
			$db = $this->GetDb();
			
			if($db)
			{
				$query = "";

				if($ObjectPK != null && !empty($ObjectPK) && $ObjectPKType != null)
				{
					$query = "SELECT ". $db->real_escape_string($Attribute) . " FROM " . $db->real_escape_string($this->TablePrefix.$Table) . " WHERE " . $db->real_escape_string($Attribute) . " = ? AND " . strtolower($TablePK) . " != ?";
				}
				else
				{
					$query = "SELECT ". $db->real_escape_string($Attribute) . " FROM " . $db->real_escape_string($this->TablePrefix.$Table) . " WHERE " . $db->real_escape_string($Attribute) . " = ?";
				}
				
				if($cmd = $db->prepare($query))
				{
					if($ObjectPK != null && !empty($ObjectPK) && $ObjectPKType != null)
					{
						$cmd->bind_param($this->GetParamLetterByType($Type).$this->GetParamLetterByType($ObjectPKType), $Value, $ObjectPK);
					}
					else
					{
						$cmd->bind_param($this->GetParamLetterByType($Type), $Value);
					}
					
					if($cmd->execute())
					{
						$cmd->bind_result($db_value);
				
						$cmd->fetch();
						
						if($db_value != null && $db_value == $Value)
						{
							$result = true;
						}
						
						$cmd->close();
					}
				}
				
				$db->close();
			}
		}
	
		return $result;
	}
	
	private function CheckForeignKey($Table, $Attribute, $Value, $Type)
	{
		$result = false;
	
		if($Table != null && $Attribute != null && $Value != null && !empty($Table) && !empty($Attribute) && !empty($Value))
		{
			$db = $this->GetDb();
			
			if($db)
			{
				if($cmd = $db->prepare("SELECT ". $db->real_escape_string($Attribute) . " FROM " . $db->real_escape_string($this->TablePrefix.$Table) . " WHERE " . $db->real_escape_string($Attribute) . " = ?"))
				{
					$cmd->bind_param($this->GetParamLetterByType($Type), $Value);
					
					if($cmd->execute())
					{
						$cmd->bind_result($db_value);
						
						$cmd->fetch();
						
						if($db_value != null && $db_value == $Value)
						{
							$result = true;
						}
						
						$cmd->close();
					}
				}
				
				$db->close();
			}
		}
	
		return $result;
	}
	
	private function RemoveByPKS($PKS, $Table)
	{
		$result = false;
		
		if($PKS != null && $Table != null && $Table->Attributes != null && count($Table->Attributes) > 0 && count($PKS) > 0)
		{
			$db = $this->GetDb();
			
			if($db)			
			{
				$where = " where ";
				
				foreach($PKS as $pk)
				{
					$where = $where . $pk->name . "=" . $this->GetValueByType($db, $pk->type, $pk->value) .", ";
				}
				
				$where = rtrim($where, " ");
				$where = rtrim($where, ",");
				
				if($where != null && !empty($where))
				{
					$query = "delete from " . $db->real_escape_string($this->TablePrefix .$Table->Name) . $where;
				
					if($db->query($query))
					{
						$result = true;
					}
				}
				
				$db->close();
			}
		}	
		
		return $result;
	}

	private function GetQueryColumns($Columns, $db)
	{
		$result = "";

		if($Columns != null && count($Columns) > 0)
		{
			foreach($Columns as $Column)
			{
				$result = $result . strtolower($db->real_escape_string($Column)). ",";
			}
		}
		else
		{
			$result = "*";
		}

		$result = rtrim($result, " ");
		$result = rtrim($result, ",");

		return $result;
	}

	private function GetQueryDistincts($Distincts, $db)
	{
		$result = "";

		if($Distincts != null && count($Distincts) > 0)
		{
			$result = "distinct(";

			foreach($Distincts as $Distinct)
			{
				$result = $result . strtolower($db->real_escape_string($Distinct)). ",";
			}
		}

		$result = rtrim($result, " ");
		$result = rtrim($result, ",");

		if($result != null && !empty($result))
		{
			$result = $result . ")";
		}
		
		return $result;
	}
	
	private function GetQueryLimit($Limit, $db)
	{
		$result = "";

		if($Limit != null)
		{
			if(count($Limit) == 1 && isset($Limit["start"]) && $Limit["start"] !== null)
			{
				$result = " limit ". $db->real_escape_string($Limit["start"]). " ";
			}
			else if(count($Limit) == 2 && isset($Limit["start"]) && $Limit["start"] !== null && isset($Limit["end"]) && $Limit["end"] !== null)
			{
				$result = " limit ". $db->real_escape_string($Limit["start"]) . "," . $db->real_escape_string($Limit["end"]). " ";
			}
		}

		return $result;
	}

	private function GetQuerySort($Sort, $db)
	{
		$result = "";

		if($Sort != null && count($Sort) > 0)
		{
			$result = " order by ";

			foreach($Sort as $key => $value)
			{
				$result = $result . $db->real_escape_string($key) . " " . $db->real_escape_string($value) . ", ";
			}
		}

		$result = rtrim($result, " ");
		$result = rtrim($result, ",");

		return $result;
	}

	private function GetQueryGroup($Groups, $db)
	{
		$result = "";

		if($Groups != null && count($Groups) > 0)
		{
			$result = " group by ";

			foreach($Groups as $Group)
			{
				$result = $result . strtolower($db->real_escape_string($Group)). ",";
			}
		}

		$result = rtrim($result, " ");
		$result = rtrim($result, ",");

		return $result;
	}

	private function GetQueryWhere($Where, $db)
	{
		$result = "";

		if($Where != null && count($Where) > 0)
		{
			$result = " where ";

			foreach($Where as $w)
			{
				if(is_object($w) && $w != null && $w->Column != null && $w->Operator != null && !empty($w->Column) && !empty($w->Operator))
				{
					if($w->Value === null)
					{
						$result = $result . $db->real_escape_string($w->Column) . " " . $db->real_escape_string($w->Operator) . " null ";
					}
					else if(is_numeric($w->Value))
					{
						$result = $result . $db->real_escape_string($w->Column) . " " . $db->real_escape_string($w->Operator) . " " . $db->real_escape_string($w->Value). " ";
					}
					else
					{
						$result = $result . $db->real_escape_string($w->Column) . " " . $db->real_escape_string($w->Operator) . " '" .$db->real_escape_string($w->Value). "' ";
					}
				}		
				else
				{
					$result = $result . $w . " ";
				}		
			}
		}

		$result = rtrim($result, " ");
		$result = rtrim($result, ",");

		return $result;
	}

	public function CheckTable($Table)
	{
		$result = false;
		
		if($Table != null && $Table->Name != null && !empty($Table->Name))
		{
			$DB_Table = $this->GetTable($Table->Name);
		
			if($DB_Table != null)
			{			
				if($DB_Table->object_md5_value != null && !empty($DB_Table->object_md5_value))
				{
					$Table_md5_value = $Table->GetMD5Value();
					
					// Table Schema has been changed
					if($Table_md5_value != $DB_Table->object_md5_value)
					{
						$this->UpdateTable($DB_Table, $Table);
					}
				}
		
				$result = false;
			}
			else
			{
				if($this->CreateTable($Table))
				{
					$this->RegisterTableInformation($Table);
				}
				
				$result = true;
			}
		}
		else
		{
			$result = false;
		}
		
		return $result;
		
	}
	
	public function CheckConstraint($Key)
	{
		$result = false;
		
		if($Key != null && $Key->IsFK && $Key->Table != null && !empty($Key->Table) && $Key->FK_RefTable != null && !empty($Key->FK_RefTable) && $Key->FK_RefColumn != null && !empty($Key->FK_RefColumn))
		{
			$db = $this->GetDb();
			
			if($db)
			{
				$query = "alter table " . $db->real_escape_string($this->TablePrefix.$Key->Table) . " add constraint `" . $db->real_escape_string($this->TablePrefix.$Key->Table) . "_" . $db->real_escape_string($Key->Name)."_". $db->real_escape_string($Key->FK_RefTable) . "_" . $db->real_escape_string($Key->FK_RefColumn) . "` foreign key (" . $db->real_escape_string($Key->Name).") references ". $db->real_escape_string($this->TablePrefix.$Key->FK_RefTable)."(" . $db->real_escape_string($Key->FK_RefColumn) . ")";
				
				if($db->query($query))
				{
					$result = true;
				}
				
				$db->close();
			}
		}
		
		return $result;
	}
	
	public function Validate($Object, $Table)
	{
		$result = null;
		
		if($Object != null && $Table != null && $Table->Attributes != null && count($Table->Attributes) > 0)
		{
			$result = array();
			
			$ObjectKeyValue = $this->GetObjectKeyValue($Object);
				
			if($ObjectKeyValue != null && count($ObjectKeyValue) > 0)
			{
				foreach($Table->Attributes as $Attribute)
				{
					$State = new \stdClass;
					
					if(!$Attribute->AllowNull && !$Attribute->IsAutoIncrement && $Attribute->DefaultValue == null && (!isset($ObjectKeyValue[$Attribute->Name]) || $ObjectKeyValue[$Attribute->Name] == null))
					{
						$State->Success = false;
						$State->Error = ($this->Language != null) ? str_replace("[s]", $Attribute->Name, $this->Language->Text("EF_ERROR_CAN_NOT_BE_NULL")) : $Attribute->Name." can not be null";
						$State->ErrorNr = 1000;
						
						array_push($result, $State);
					}
					
					if(isset($ObjectKeyValue[$Attribute->Name]) && $ObjectKeyValue[$Attribute->Name] != null && $Attribute->Length > 0 && strlen($ObjectKeyValue[$Attribute->Name]) > $Attribute->Length)
					{
						$State->Success = false;
						$State->Error = ($this->Language != null) ? str_replace("[s]", $Attribute->Name, $this->Language->Text("EF_ERROR_INVALID_LENGTH")) : "Invalid length of " . $Attribute->Name . ". Allowed length " . $Attribute->Length;
						$State->ErrorNr = 1001;
						
						array_push($result, $State);
					}
					
					if(($Attribute->IsPK || $Attribute->IsUnique) && !$Attribute->IsAutoIncrement)
					{
						$prop_pk_name = $this->GetPropertyNameByString($Object, $Table->PK()->Name);
						
						if($this->CheckDuplicate($Table->Name, $Table->PK()->Name, $Attribute->Name, $ObjectKeyValue[$Attribute->Name], $Object->$prop_pk_name, $Table->PK()->Type, $Attribute->Type))
						{
							$State->Success = false;
							$State->Error = ($this->Language != null) ? str_replace("[s]", $Attribute->Name, $this->Language->Text("EF_ERROR_CAN_NOT_DUPLICATED")) : "Can not duplicated value of " . $Attribute->Name;
							$State->ErrorNr = 1002;
							
							array_push($result, $State);
						}
					}
					
					if($Attribute->IsFK && isset($ObjectKeyValue[$Attribute->Name]) && $ObjectKeyValue[$Attribute->Name] != null && $Attribute->DefaultValue == null)
					{
						if(!$this->CheckForeignKey($Attribute->FK_RefTable, $Attribute->FK_RefColumn, $ObjectKeyValue[$Attribute->Name], $Attribute->Type))
						{
							$State->Success = false;
							$State->Error = ($this->Language != null) ? str_replace("[s]", $Attribute->FK_RefTable."(".$ObjectKeyValue[$Attribute->Name].")", $this->Language->Text("EF_ERROR_FK_NOT_FOUND")) : "Foreign key for column " . $Attribute->Name."(" . $ObjectKeyValue[$Attribute->Name] . ")" . " in the table " . $Attribute->FK_RefTable . " not found";
							$State->ErrorNr = 1003;
							
							array_push($result, $State);
						}
					}
				}
			}
			
			if(count($result) <= 0)
			{
				$result = new \stdClass;
				$result->Success = true;
			}
		}
		
		return $result;
	}

	public function Insert($Object, $Table)
	{
		$result = null;
		
		if($Object != null && $Table != null && $Table->Attributes != null && count($Table->Attributes) > 0)
		{
			$db = $this->GetDb();
			
			if($db)			
			{
				$ObjectKeyValue = $this->GetObjectKeyValue($Object);
				
				if($ObjectKeyValue != null && count($ObjectKeyValue) > 0)
				{
					$query = "";
					$columns = "";
					$values = "";				
					
					foreach($Table->Attributes as $Attribute)
					{
						if(array_key_exists(strtolower($Attribute->Name), $ObjectKeyValue) && !$Attribute->IsAutoIncrement && $ObjectKeyValue[$Attribute->Name] !== null)
						{
							$value = $this->GetValueByType($db, $Attribute->Type, $ObjectKeyValue[$Attribute->Name]);

							if($value != null && $value != "null")
							{
								$columns = $columns . $db->real_escape_string($Attribute->Name) . ", ";
								$values = $values. $value . ", ";
							}
						}
					}
					
					$columns = rtrim($columns, " ");
					$columns = rtrim($columns, ",");
					$columns = "(" . $columns . ")";
					
					$values = rtrim($values, " ");
					$values = rtrim($values, ",");
					$values = " values(" . $values . ")";
					
					$query = "insert into " . $db->real_escape_string($this->TablePrefix .$Table->Name) . " " . $columns . $values;
					
					if($db->query($query))
					{						
						$result = $db->insert_id;
					}	
				}

				$db->close();
			}
		}	
		
		return $result;
	}

	public function Remove($Object, $Table)
	{
		$result = false;
		
		if($Object != null && $Table != null && $Table->Attributes != null && count($Table->Attributes) > 0)
		{
			$PKS = array();
			
			$ObjectKeyValue = $this->GetObjectKeyValue($Object);
				
			if($ObjectKeyValue != null && count($ObjectKeyValue) > 0)
			{
				$Table_PKS = $Table->PKS();
				
				if($Table_PKS != null && count($Table_PKS) > 0)
				{
					foreach($Table_PKS as $Attribute)
					{
						if(isset($ObjectKeyValue[$Attribute->Name]) && $ObjectKeyValue[$Attribute->Name] != null)
						{
							$pk = new \stdClass();
							$pk->name = $Attribute->Name;
							$pk->value = $ObjectKeyValue[$Attribute->Name];
							$pk->type = $Attribute->Type;
							
							array_push($PKS, $pk);
						}
					}
				}
				
				if($this->RemoveByPKS($PKS, $Table))
				{
					$result = true;
				}
			}
		}	
		
		return $result;
	}

	public function RemoveByWhere($Where, $Table)
	{
		$result = false;
	
		if($Where != null && $Table != null && $Table->Attributes != null && count($Table->Attributes) > 0 && count($Where) > 0)
		{
			$db = $this->GetDb();
			
			if($db)			
			{
				$qr_where = $this->GetQueryWhere($Where, $db);
		
				if($qr_where != null && !empty($qr_where))
				{
					$query = "delete from " . $db->real_escape_string($this->TablePrefix .$Table->Name) . $qr_where;
					
					if($db->query($query))
					{
						$result = true;
					}
				}
			
				$db->close();
			}
		}	
		
		return $result;
	}
	
	public function RemoveById($Id, $Table)
	{
		$result = false;
		
		if($Id != null && $Table != null && $Table->Attributes != null && count($Table->Attributes) > 0)
		{
			$PKS = array();
			
			foreach($Table->Attributes as $Attribute)
			{
				if($Attribute->IsPK)
				{
					$pk = new \stdClass();
					$pk->name = $Attribute->Name;
					$pk->value = $Id;
					$pk->type = $Attribute->Type;
					array_push($PKS, $pk);
					break;
				}
			}
			
			if($this->RemoveByPKS($PKS, $Table))
			{
				$result = true;
			}
		}	
		
		return $result;
	}

	public function Update($Object, $Table)
	{
		$result = false;

		if($Object != null && $Table != null && $Table->Attributes != null && count($Table->Attributes) > 0)
		{
			$Table_PK = $Table->PK();

			if($Table_PK != null)
			{
				$PK = $this->GetPropertyNameByString($Object, $Table_PK->Name);

				if($PK != null && isset($Object->$PK))
				{
					$DbObject = $this->GetByPK($Object->$PK, $Table);

					if($DbObject != null)
					{
						$DbObjectKeyValue = $this->GetObjectKeyValue($DbObject);
						$ObjectKeyValue = $this->GetObjectKeyValue($Object);

						if($DbObjectKeyValue != null && count($DbObjectKeyValue) > 0 && $ObjectKeyValue != null && count($ObjectKeyValue) > 0)
						{
							$ChangedAttributes = array();

							foreach($DbObjectKeyValue as $key => $value)
							{
								if(isset($ObjectKeyValue[$key]))
								{
									if($DbObjectKeyValue[$key] != $ObjectKeyValue[$key])
									{
										$ChangedAttributes[$key] = $ObjectKeyValue[$key];
									}
								}
							}

							// Update only changed columns
							if($ChangedAttributes != null && count($ChangedAttributes) > 0)
							{
								$db = $this->GetDb();

								if($db)			
								{
									$query = "";
									$columns = "";		
									$where = "";
									
									foreach($Table->Attributes as $Attribute)
									{
										if(isset($ChangedAttributes[$Attribute->Name]) && !$Attribute->IsAutoIncrement && $ChangedAttributes[$Attribute->Name] !== null)
										{
											$columns = $columns . $db->real_escape_string($Attribute->Name) . "=" . $this->GetValueByType($db, $Attribute->Type, $ChangedAttributes[$Attribute->Name]) . ", ";
										}
									}
									
									$columns = rtrim($columns, " ");
									$columns = rtrim($columns, ",");
									$columns = " set " . $columns;
									
									$PKS = $Table->PKS();
									
									if($PKS != null && count($PKS) > 0)
									{
										$run = 0;
										
										foreach($PKS as $pk)
										{
											if(isset($ObjectKeyValue[$pk->Name]) && $ObjectKeyValue[$pk->Name] != null && !empty($ObjectKeyValue[$pk->Name]))
											{
												if($run > 0)
												{
													$where = $where . "and " .  $db->real_escape_string($pk->Name) . "=" . $this->GetValueByType($db, $pk->Type, $ObjectKeyValue[$pk->Name]) .", ";
												}
												else
												{
													$where = $where . "where " .  $db->real_escape_string($pk->Name) . "=" . $this->GetValueByType($db, $pk->Type, $ObjectKeyValue[$pk->Name]) .", ";
												}
												
												$run++;
											}
										}
										
										$where = ltrim($where, " ");
										$where = ltrim($where, "and");
										$where = rtrim($where, " ");
										$where = rtrim($where, ",");
										
										if($columns != null && !empty($columns) && $where != null && !empty($where))
										{
											$query = "update " . $db->real_escape_string($this->TablePrefix .$Table->Name) . " " . $columns . " " . $where;
											
											if($db->query($query))
											{						
												$result = true;
											}
										}													
									}
									
									$db->close();
								}
							}
						}
					}
				}
			}
		}	
		
		return $result;
	}	

	public function UpdateColumns($Columns, $Table, $Where = null)
	{
		$result = false;

		if($Columns != null && $Table != null && count($Columns) > 0 && $Table->Attributes != null && count($Table->Attributes) > 0)
		{
			$db = $this->GetDb();
			
			if($db)			
			{
				$query = "";
				$qr_columns = "";		
				$qr_where = $this->GetQueryWhere($Where, $db);
				
				foreach($Columns as $Column)
				{
					$Attribute = $Table->GetAttribute($Column->Column);
					
					if($Attribute != null)
					{
						$qr_columns = $qr_columns . $db->real_escape_string($Attribute->Name) . "=" . $this->GetValueByType($db, $Attribute->Type, $Column->Value) . ", ";
					}
				}
				
				$qr_columns = rtrim($qr_columns, " ");
				$qr_columns = rtrim($qr_columns, ",");
				$qr_columns = " set " . $qr_columns;
				
				if($qr_columns != null && !empty($qr_columns))
				{
					$query = "update " . $db->real_escape_string($this->TablePrefix .$Table->Name) . " " . $qr_columns . " " . $qr_where;
				
					if($db->query($query))
					{						
						$result = true;
					}	
				}

				$db->close();
			}
		}	
		
		return $result;
	}

	public function Get($Table, $Columns = null, $Distincts = null, $Where = null, $Limit = null, $Sort = null, $Groups = null)
	{
		$result = null;

		if($Table != null)
		{
			$db = $this->GetDb();

			if($db)
			{
				$qr_distincts = $this->GetQueryDistincts($Distincts, $db);
				$qr_columns = $this->GetQueryColumns($Columns, $db);
				$qr_where = $this->GetQueryWhere($Where, $db);
				$qr_limit = $this->GetQueryLimit($Limit, $db);
				$qr_sort = $this->GetQuerySort($Sort, $db);
				$qr_group = $this->GetQueryGroup($Groups, $db);
				
				if($qr_distincts != null && !empty($qr_distincts))
				{
					$qr_distincts = $qr_distincts . ", ";

					if($qr_columns == "*")
					{
						$qr_columns = $db->real_escape_string($this->TablePrefix.$Table->Name) . ".*";
					}
				}

				$query = "select " . $qr_distincts. " " . $qr_columns . " from ". 
						$db->real_escape_string($this->TablePrefix.$Table->Name).
						$qr_where.
						$qr_group.
						$qr_sort.
						$qr_limit;
				
				if($db_result = $db->query($query))
				{
					$num_rows = $db_result->num_rows;

					if($num_rows > 0)
					{
						if($num_rows > 1)
						{
							$result = array();
							
							while($db_res = $db_result->fetch_object())
							{
								array_push($result, $db_res);
							}
						}
						else
						{
							$result = $db_result->fetch_object();
						}
					}
				}
				
				$db->close();
			}
		}
		
		return $result;
	}

	public function Gets($Table, $Columns = null, $Distincts = null, $Where = null, $Limit = null, $Sort = null, $Groups = null)
	{
		$result = null;

		if($Table != null)
		{
			$db = $this->GetDb();

			if($db)
			{
				$qr_distincts = $this->GetQueryDistincts($Distincts, $db);
				$qr_columns = $this->GetQueryColumns($Columns, $db);
				$qr_where = $this->GetQueryWhere($Where, $db);
				$qr_limit = $this->GetQueryLimit($Limit, $db);
				$qr_sort = $this->GetQuerySort($Sort, $db);
				$qr_group = $this->GetQueryGroup($Groups, $db);
				
				if($qr_distincts != null && !empty($qr_distincts))
				{
					$qr_distincts = $qr_distincts . ", ";

					if($qr_columns == "*")
					{
						$qr_columns = $db->real_escape_string($this->TablePrefix.$Table->Name) . ".*";
					}
				}

				$query = "select " . $qr_distincts. " " . $qr_columns . " from ". 
						$db->real_escape_string($this->TablePrefix.$Table->Name).
						$qr_where.
						$qr_group.
						$qr_sort.
						$qr_limit;
				
				if($db_result = $db->query($query))
				{
					$num_rows = $db_result->num_rows;

					if($num_rows > 0)
					{
						$result = array();

						while($row = $db_result->fetch_object())
						{
							array_push($result, $row);
						}
					}
				}
				
				$db->close();
			}
		}
		
		return $result;
	}

	public function GetByPK($PK, $Table, $Columns = null)
	{
		$result = null;

		if($PK != null && !empty($PK) && $Table != null)
		{
			$Table_PK = $Table->PK();

			if($Table_PK != null)
			{
				$obj_where = new \stdClass();
				$obj_where->Column = $Table_PK->Name;
				$obj_where->Operator = "=";
				$obj_where->Value = $PK;
				
				$where = array();				
				array_push($where, $obj_where);
				$result = $this->Get($Table, $Columns, null, $where);
			}
		}
		
		return $result;
	}

	public function Count($Table, $Columns = null, $Distincts = null, $Where = null, $Limit = null, $Sort = null, $Groups = null, $QueryString = null)
	{
		$result = null;

		if($Table != null)
		{
			$db = $this->GetDb();

			if($db)
			{
				$qr_distincts = $this->GetQueryDistincts($Distincts, $db);
				$qr_columns = $this->GetQueryColumns($Columns, $db);
				$qr_where = $this->GetQueryWhere($Where, $db);
				$qr_limit = $this->GetQueryLimit($Limit, $db);
				$qr_sort = $this->GetQuerySort($Sort, $db);
				$qr_group = $this->GetQueryGroup($Groups, $db);

				if($qr_distincts != null && !empty($qr_distincts))
				{
					$qr_distincts = $qr_distincts . ", ";

					if($qr_columns == "*")
					{
						$qr_columns = $db->real_escape_string($this->TablePrefix.$Table->Name) . ".*";
					}
				}
				
				$query = "select " . $qr_distincts. " " . $qr_columns . " from ". 
						$db->real_escape_string($this->TablePrefix.$Table->Name).
						$qr_where.
						$qr_group.
						$qr_sort.
						$qr_limit;

				if($db_result = $db->query($query))
				{
					$result = $db_result->num_rows;				
				}
				
				$db->close();
			}
		}
		
		return $result;
	}

	public function GetSQL($Table, $Columns = null, $Distincts = null, $Where = null, $Limit = null, $Sort = null, $Groups = null)
	{
		$result = null;

		if($Table != null)
		{
			$db = $this->GetDb();

			if($db)
			{
				$qr_distincts = $this->GetQueryDistincts($Distincts, $db);
				$qr_columns = $this->GetQueryColumns($Columns, $db);
				$qr_where = $this->GetQueryWhere($Where, $db);
				$qr_limit = $this->GetQueryLimit($Limit, $db);
				$qr_sort = $this->GetQuerySort($Sort, $db);
				$qr_group = $this->GetQueryGroup($Groups, $db);
				
				if($qr_distincts != null && !empty($qr_distincts))
				{
					$qr_distincts = $qr_distincts . ", ";

					if($qr_columns == "*")
					{
						$qr_columns = $db->real_escape_string($this->TablePrefix.$Table->Name) . ".*";
					}
				}

				$result = "select " . $qr_distincts. $qr_columns . " from ". 
						$db->real_escape_string($this->TablePrefix.$Table->Name).
						$qr_where.
						$qr_group.
						$qr_sort.
						$qr_limit;

				$db->close();
			}
		}
		
		return $result;
	}

	public function SQL($Query)
	{
		$result = null;

		if($Query != null && !empty($Query))
		{
			$db = $this->GetDb();

			if($db)
			{
				if($db_result = $db->query($Query))
				{
					$num_rows = $db_result->num_rows;
					
					if($num_rows > 0)
					{
						if($num_rows > 1)
						{
							$result = array();
						}

						while($row = $db_result->fetch_object())
						{
							if($num_rows > 1)
							{
								array_push($result, $row);
							}
							else
							{
								$result = $row;
								break;
							}
						}
					}
				}

				$db->Close();
			}
		}

		return $result;
	}

	public function RealEscapeString($String)
	{
		$db = $this->GetDb();

		if($db)
		{
			return $db->real_escape_string($String);
		}
		else
		{
			return null;
		}
	}
}

?>