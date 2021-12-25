<?php

namespace Entity\Model;

if(!defined("EntityFramework")){ die("Access Denied!"); }

class Table
{
	private $Columns;
	private $Distincts;
	private $Limit;
	private $Sort;
	private $Groups;
	private $Where;

	public $Entity;
	public $Class;
	public $Key;
	public $Name;
	public $LowerCase;
	public $Pluralize;
	public $DefaultCollation;
	public $Attributes;

	public $FormAction;
	public $FormEnctype;
	public $FormType;
	public $FormReset;
	public $FormSubmit;
	public $FormLoading;

	public function __construct($Key, $Class, $Entity, $LowerCase = true, $Pluralize = false, $Collation = "utf8_general_ci")
	{
		$this->Entity = $Entity;
		$this->Key = $Key;
		$this->Class = $Class;
		$this->Pluralize = $Pluralize;
		$this->LowerCase = $LowerCase;
		$this->Collation = $Collation;
		
		$this->Initialite();
	}
	
	private function Initialite()
	{
		$Reflector = new \ReflectionClass($this->Class);
		
		if($Reflector != null)
		{
			$classHeader = $Reflector->getDocComment();
			$className = ($this->LowerCase) ? strtolower($Reflector->getShortName()) : $Reflector->getShortName();

			if($classHeader != null)
			{
				$classHeader = str_replace("/*", "", $classHeader);
				$classHeader = str_replace("*/", "", $classHeader);
				$classHeader = str_replace("*", "", $classHeader);
				$classHeader = str_replace("\\", "", $classHeader);

				$this->FormAction = $this->HeaderValue($classHeader, "form_action");
				$this->FormEnctype = $this->HeaderValue($classHeader, "form_enctype");
				$this->FormType = $this->HeaderValue($classHeader, "form_type");
				$this->FormReset = $this->HeaderValue($classHeader, "form_reset");
				$this->FormSubmit = $this->HeaderValue($classHeader, "form_submit");
				$this->FormLoading = $this->HeaderValue($classHeader, "form_loading");
			}
			
			if($this->Pluralize)
			{
				echo "YES";
				$className = $className."s";
			}
			
			$this->Name = $className;
			
			$properties = $Reflector->getProperties();
		
			if($properties != null && count($properties) > 0)
			{
				$this->Attributes = array();
				
				foreach($properties as $property)
				{
					$attribute = $this->GetAttributeByProperty($property);
					
					if($attribute != null)
					{
						array_push($this->Attributes, $attribute);
					}
				}
			}
		}
	}
	
	private function GetAttributeByProperty($Property)
	{
		$result = null;
		
		if($Property != null && isset($Property->name) && $Property->isPublic())
		{
			$result = new \Entity\Model\Attribute();
			$result->Name = ($this->LowerCase) ? strtolower($Property->name) : $Property->name;
			$result->Table = $this->Name;
			
			if($header = $Property->getDocComment())
			{
				$header = str_replace("/*", "", $header);
				$header = str_replace("*/", "", $header);
				$header = str_replace("*", "", $header);
				$header = str_replace("\\", "", $header);
				
				if($this->HeaderIsTrue($header, "ignore"))
				{
					return null;
				}
				
				$result->IsPK = $this->HeaderIsTrue($header, "pk");		
				$result->IsUnique = $this->HeaderIsTrue($header, "unique");		
				$result->IsIndex = $this->HeaderIsTrue($header, "index");					
				$result->IsAutoIncrement = $this->HeaderIsTrue($header, "auto_increment");				
				$result->IsFK = $this->HeaderIsTrue($header, "fk");
				$result->AllowNull = !$this->HeaderIsTrue($header, "not null");
				$result->FormIgnore = $this->HeaderIsTrue($header, "form_ignore");
				$result->FormRequired = $this->HeaderIsTrue($header, "form_required");
				$result->FormReadOnly = $this->HeaderIsTrue($header, "form_readonly");
				$result->FormDisabled = $this->HeaderIsTrue($header, "form_disabled");
				$result->FormAutoComplete = $this->HeaderIsTrue($header, "form_autocomplete");
				$result->FormAutoFocus = $this->HeaderIsTrue($header, "form_autofocus");
				$result->FormIsOptions = $this->HeaderIsTrue($header, "form_is_options");
				$result->FormOptionsIsTable = $this->HeaderIsTrue($header, "form_options_is_table");
				$result->FormSendOnEnter = $this->HeaderIsTrue($header, "form_send_on_enter");				
				
				if($result->IsPK)
				{
					$result->AllowNull = false;
				}
				
				$result->Type = $this->HeaderValue($header, "type");
				$result->Length = $this->HeaderValue($header, "length");
				$result->Collation = $this->HeaderValue($header, "collation");
				$result->FK_RefTable = $this->HeaderValue($header, "fk_table");
				$result->FK_RefColumn = $this->HeaderValue($header, "fk_column");
				$result->DefaultValue = $this->HeaderValue($header, "default");
				$result->FormType = $this->HeaderValue($header, "form_type");
				$result->FormMin = $this->HeaderValue($header, "form_min");
				$result->FormMax = $this->HeaderValue($header, "form_max");
				$result->FormStep = $this->HeaderValue($header, "form_step");
				$result->FormPattern = $this->HeaderValue($header, "form_pattern");
				$result->FormOptions = $this->HeaderValue($header, "form_options");			
				$result->FormOptionsType = $this->HeaderValue($header, "form_options_type");
				$result->FormOptionValue = $this->HeaderValue($header, "form_option_value");
				$result->FormOptionName = $this->HeaderValue($header, "form_option_name");
				$result->FormPlaceholder = $this->HeaderValue($header, "form_placeholder");
				$result->FormLabel = $this->HeaderValue($header, "form_label");
				$result->FormLabelPosition = $this->HeaderValue($header, "form_label_position");
				$result->FormImageUrl = $this->HeaderValue($header, "image_url");
				$result->FormClass = $this->HeaderValue($header, "form_class");
				$result->FormStyle = $this->HeaderValue($header, "form_style");
				$result->FormValue = $Property->getValue(new $Property->class());
				$result->FormClickEvent = $this->HeaderValue($header, "form_click_event");
				$result->SubForm = $this->HeaderValue($header, "sub_form");
				$result->SubFormKey = $this->HeaderValue($header, "sub_form_key");
				
				if($result->Type == null || empty($result->Type))
				{
					$result->Type = "varchar";
				}
				
				if($result->Type == "int")
				{
					if($result->Length == null || empty($result->Length))
					{
						$result->Length = 11;
					}
				}
				if($result->Type == "char" || $result->Type == "varchar" || $result->Type == "text")
				{
					if($result->Length == null || empty($result->Length))
					{
						$result->Length = 255;
					}
					
					if($result->Collation == null || empty($result->Collation))
					{
						$result->Collation = $this->Collation;
					}
				}
				else if($result->Type == "float" || $result->Type == "double" || $result->Type == "int" || $result->Type == "decimal" || $result->Type == "bigint" || $result->Type == "real")
				{
					if($result->Length == null || empty($result->Length))
					{
						$result->Length = 11;
					}
				}
			}
			else	
			{
				$result->Type = "varchar";
				$result->Length = 255;
				$result->AllowNull = true;
				$result->Collation = $this->Collation;
			}
		}
		
		return $result;
	}
	
	private function HeaderIsTrue($header, $attribute)
	{
		$result = false;
				
		if($header != null && !empty($header))
		{
			$result = (strpos(strtolower($header), strtolower($attribute)) !== false);
		}
	
		return $result;
	}
	
	private function HeaderValue($header, $attribute)
	{
		$result = null;
		
		if($header != null && !empty($header))
		{
			$values = explode(",", $header);
			
			if($values != null && count($values) > 0)
			{
				foreach($values as $value)
				{
					$pos = strpos(strtolower($header), strtolower($attribute));
					
					if($pos > 0)
					{
						$value = substr($header, $pos);
						$posStart = strpos(strtolower($value), ":");
						$posEnd = strpos(strtolower($value), ",");
						
						if($posStart > 0)
						{	
							if($posEnd > 0)
							{
								$value = substr($value, $posStart, $posEnd - $posStart);
							}
							else
							{
								$value = substr($value, $posStart);
							}
							
							$value = str_replace(":","", $value);
							$value = str_replace(",","", $value);
							$value = str_replace(" ","", $value);
							
							$result = $value;
						}
						
						break;
					}
				}
			}
		}
		
		return $result;
	}

	private function ResetQuery()
	{
		$this->Columns = null;
		$this->Where = null;
		$this->Limit = null;
		$this->Sort = null;
		$this->Groups = null;
		$this->QueryString = null;
	}
	
	public function GetValue()
	{
		return json_encode($this);
	}
	
	public function GetMD5Value()
	{
		return md5($this->GetValue());
	}
	
	public function AutoIncrements()
	{
		$result = null;
		
		if($this->Attributes != null && count($this->Attributes) > 0)
		{
			foreach($this->Attributes as $Attribute)
			{
				if($Attribute->IsAutoIncrement)
				{
					if($result == null)
					{
						$result = array();
					}
					
					array_push($result, $Attribute);
				}
			}
		}
		
		return $result;
	}
	
	public function AutoIncrement()
	{
		$result = null;
		
		$AutoIncrements = $this->AutoIncrements();
		
		if($AutoIncrements != null && count($AutoIncrements) == 1)
		{
			$result = $AutoIncrements[0];
		}
		
		return $result;
	}
	
	public function PKS()
	{
		$result = null;
		
		if($this->Attributes != null && count($this->Attributes) > 0)
		{
			foreach($this->Attributes as $Attribute)
			{
				if($Attribute->IsPK)
				{
					if($result == null)
					{
						$result = array();
					}
					
					array_push($result, $Attribute);
				}
			}
		}
		
		return $result;
	}
	
	public function PK()
	{
		$result = null;
		
		$PKS = $this->PKS();
		
		if($PKS != null && count($PKS) == 1)
		{
			$result = $PKS[0];
		}
		
		return $result;
	}
	
	public function FKS()
	{
		$result = null;
		
		if($this->Attributes != null && count($this->Attributes) > 0)
		{
			foreach($this->Attributes as $Attribute)
			{
				if($Attribute->IsFK)
				{
					if($result == null)
					{
						$result = array();
					}
					
					array_push($result, $Attribute);
				}
			}
		}
		
		return $result;
	}
	
	public function Uniques()
	{
		$result = null;
		
		if($this->Attributes != null && count($this->Attributes) > 0)
		{
			foreach($this->Attributes as $Attribute)
			{
				if($Attribute->IsUnique)
				{
					if($result == null)
					{
						$result = array();
					}
					
					array_push($result, $Attribute);
				}
			}
		}
		
		return $result;
	}
	
	public function Indexes()
	{
		$result = null;
		
		if($this->Attributes != null && count($this->Attributes) > 0)
		{
			foreach($this->Attributes as $Attribute)
			{
				if($Attribute->IsIndex)
				{
					if($result == null)
					{
						$result = array();
					}
					
					array_push($result, $Attribute);
				}
			}
		}
		
		return $result;
	}

	public function HasAttribute($Name)
	{
		$result = false;
		
		if($this->Attributes != null && count($this->Attributes) > 0)
		{
			foreach($this->Attributes as $Attribute)
			{
				if(strtolower($Attribute->Name) == strtolower($Name))
				{
					$result = true;
					break;
				}
			}
		}
		
		return $result;
	}

	public function GetAttribute($Name)
	{
		$result = null;
		
		if($this->Attributes != null && count($this->Attributes) > 0)
		{
			foreach($this->Attributes as $Attribute)
			{
				if(strtolower($Attribute->Name) == strtolower($Name))
				{
					$result = $Attribute;
					break;
				}
			}
		}
		
		return $result;
	}
	
	public function HasPK($Name)
	{
		$result = false;
		
		if($this->Attributes != null && count($this->Attributes) > 0)
		{
			foreach($this->Attributes as $Attribute)
			{
				if(strtolower($Attribute->Name) == strtolower($Name) && $Attribute->IsPK)
				{
					$result = true;
					break;
				}
			}
		}
		
		return $result;
	}
	
	public function HasFK($Name)
	{
		$result = false;
		
		if($this->Attributes != null && count($this->Attributes) > 0)
		{
			foreach($this->Attributes as $Attribute)
			{
				if(strtolower($Attribute->Name) == strtolower($Name) && $Attribute->IsFK)
				{
					$result = true;
					break;
				}
			}
		}
		
		return $result;
	}

	public function Columns($Columns = null)
	{
		$params = func_get_args();
		
		if($params != null && count($params) > 0)
		{
			$this->Columns = array();

			foreach($params as $key => $value)
			{
				array_push($this->Columns, $value);
			}
		}
	
		return $this;
	}

	public function Distinct($Distincts = null)
	{
		$params = func_get_args();
		
		if($params != null && count($params) > 0)
		{
			$this->Distincts = array();

			foreach($params as $key => $value)
			{
				array_push($this->Distincts, $value);
			}
		}
	
		return $this;
	}

	public function GroupBy($Groups = null)
	{
		$params = func_get_args();
		
		if($params != null && count($params) > 0)
		{
			$this->Groups = array();

			foreach($params as $key => $value)
			{
				array_push($this->Groups, $value);
			}
		}
	
		return $this;
	}

	public function Where($Column, $Operator, $Value)
	{
		if($Column != null && $Operator != null && !empty($Column) && !empty($Operator))
		{
			if($this->Where == null)
			{
				$this->Where = array();
			}

			$obj_where = new \stdClass();
			$obj_where->Column = $Column;
			$obj_where->Operator = $Operator;			
			$obj_where->Value = $Value;	

			array_push($this->Where, $obj_where);
		}

		return $this;
	}

	public function WhereString($String)
	{
		if($String != null && !empty($String))
		{
			$this->Where = $String;
		}

		return $this;
	}

	public function And()
	{
		if($this->Where != null && count($this->Where) > 0)
		{
			array_push($this->Where, "and");
		}

		return $this;
	}

	public function OR()
	{
		if($this->Where != null && count($this->Where) > 0)
		{
			array_push($this->Where, "or");
		}

		return $this;
	}

	public function BeginGroup()
	{
		if($this->Where == null)
		{
			$this->Where = array();
		}

		array_push($this->Where, "(");
		
		return $this;
	}

	public function EndGroup()
	{
		if($this->Where == null)
		{
			$this->Where = array();
		}

		array_push($this->Where, ")");

		return $this;
	}

	public function First()
	{
		$this->Limit = array();
		$this->Limit["start"] = 0;
		$this->Limit["end"] = 1;
		return $this;
	}

	public function Last()
	{
		$this->Limit = array();
		$this->Limit["start"] = ($this->CountRows() - 1);
		$this->Limit["end"] = 1;
		return $this;
	}

	public function At($Position)
	{
		$this->Limit = array();
		$this->Limit["start"] = ($Position - 1);
		$this->Limit["end"] = 1;
		return $this;
	}

	public function GetByPK($PK)
	{
		if($PK != null && !empty($PK))
		{
			$Table_PK = $this->PK();
			
			if($Table_PK != null)
			{
				$obj_where = new \stdClass();
				$obj_where->Column = $Table_PK->Name;
				$obj_where->Operator = "=";
				$obj_where->Value = $PK;
				
				$where = array();				
				array_push($where, $obj_where);
				return $this->Entity->Get($this, null, null, $where);
			}
		}
		
		return null;
	}

	public function Limit($Start = null, $End = null)
	{
		if($Start >= 0 || $End > 0)
		{
			$this->Limit = array();
			$this->Limit["start"] = $Start;

			if($End > 0)
			{
				$this->Limit["end"] = $End;
			}
		}

		return $this;
	}

	public function Range($Start = null, $End = null)
	{
		if($Start >= 0 || $End > 0)
		{
			$this->Limit = array();
			$this->Limit["start"] = $Start;

			if($End > 0)
			{
				$this->Limit["end"] = ($End - $Start);
			}
		}

		return $this;
	}

	public function Sort($Column, $Direction)
	{
		if($Column != null && $Direction != null && !empty($Column) && !empty($Direction))
		{
			if($this->Sort == null)
			{
				$this->Sort = array();
			}

			$this->Sort[$Column] = $Direction;
		}

		return $this;
	}

	public function Min($Column)
	{
		if($Column != null && !empty($Column))
		{
			$this->Columns = array("min(" . $this->Entity->RealEscapeString($Column) . ") as db_function");
		}

		return $this;
	}

	public function Average($Column)
	{
		if($Column != null && !empty($Column))
		{
			$this->Columns = array("avg(" . $this->Entity->RealEscapeString($Column) . ") as db_function");
		}

		return $this;
	}
	
	public function Max($Column)
	{
		if($Column != null && !empty($Column))
		{
			$this->Columns = array("max(" . $this->Entity->RealEscapeString($Column) . ") as db_function");
		}

		return $this;
	}

	public function Count($Column)
	{
		if($Column != null && !empty($Column))
		{
			$this->Columns = array("count(" . $this->Entity->RealEscapeString($Column) . ") as db_function");
		}

		return $this;
	}

	public function Sum($Column)
	{
		if($Column != null && !empty($Column))
		{
			$this->Columns = array("sum(" . $this->Entity->RealEscapeString($Column) . ") as db_function");
		}

		return $this;
	}

	public function Get($PK = null)
	{
		$result = null;
		
		if($PK != null)
		{
			$result = $this->GetByPK($PK);
		}
		else
		{
			$result = $this->Entity->Get($this, $this->Columns, $this->Distincts, $this->Where, $this->Limit, $this->Sort, $this->Groups);
			
			$this->ResetQuery();
		}

		return $result;
	}

	public function GetSQL()
	{
		if($this->Entity != null)
		{
			$result = $this->Entity->GetSQL($this, $this->Columns, $this->Distincts, $this->Where, $this->Limit, $this->Sort, $this->Groups);

			$this->ResetQuery();
			
			return $result;
		}
		else	
		{
			return null;
		}
	}

	public function CountRows()
	{
		if($this->Entity != null)
		{
			$result = $this->Entity->Count($this, $this->Columns, $this->Distincts, $this->Where, $this->Limit, $this->Sort, $this->Groups);
			
			$this->ResetQuery();

			return $result;
		}
		else	
		{
			return 0;
		}
	}

	public function Add($Object)
	{
		$result = null;
		
		if($Object != null)
		{
			$Class = get_class($Object);
			
			$result = $this->Entity->Add($Object, $this);
				
			if($result != null && is_numeric($result))
			{
				$PK = $this->PK();
				$AI = $this->AutoIncrement();
				
				if($PK != null && $AI != null && $PK->Name != null && !empty($PK->Name) && $PK->Name == $AI->Name)
				{
					$propertyName = $this->Entity->GetPropertyNameByString($Object, $AI->Name);
					$Object->$propertyName = $result;
				}
			}
		}
		
		return $result;
	}

	public function Validate($Object)
	{
		$result = null;
		
		if($Object != null)
		{			
			$result = $this->Entity->Validate($Object, $this);
		}
		
		return $result;
	}

	public function Remove($Object = null, $PK = null)
	{
		$result = null;
		
		if($Object != null)
		{
			$result = $this->Entity->Remove($Object, $this);
		}
		else if($PK != null)
		{
			$result = $this->Entity->RemoveByPK($PK, $this);
		}
		else if($this->Where != null)
		{
			$result = $this->Entity->RemoveByWhere($this->Where, $this);
		}

		$this->ResetQuery();
		
		return $result;
	}

	public function Update($Object)
	{
		$result = false;
		
		if($Object != null)
		{
			$result = $this->Entity->Update($Object, $this, $this->Where);

			$this->ResetQuery();
		}
		
		return $result;
	}

	public function Form($Object = null, $ReturnArray = false, $Columns = null, $ParentForm = null, $ParentFormAjaxId = null, $Class = null, $OnCompleteCallBack = null, $options = null, $translations = null, $form_reset = null, $form_submit = null, $form_action = null, $form_method = null, $form_enctype = null, $form_type = null, $form_loading = null)
	{
		$result = "";

		$form_name = ($ParentForm != null && !empty($ParentForm)) ? $ParentForm . "_" . $this->Key : $this->Key;

		$ajax_form_id = md5($form_name . date("Y-m-d H:i:s"));
		$ajax_function_name = "send" . $ajax_form_id . "()";
		
		if($form_action == null)
		{
			$form_action = ($this->FormAction != null && !empty($this->FormAction)) ? $this->FormAction : "#";
		}
		
		if($form_enctype == null)
		{
			$form_enctype = ($this->FormEnctype != null && !empty($this->FormEnctype)) ? $this->FormEnctype : "multipart/form-data";
		}
		
		if($form_type == null)
		{
			$form_type = ($this->FormType != null && !empty($this->FormType)) ? $this->FormType : "html";
		}
		
		if($ParentForm == null)
		{
			$result = "<form name=\"" . $form_name . "\" id=\"" . $ajax_form_id . "\" action=\"" . $form_action . "\" method=\"post\" enctype=\"" . $form_enctype . "\" data-type=\"" . $form_type . "\">";
		}
		else
		{
			$result = "<div id=\"" . $form_name . "\">";
		}

		if($this->Attributes != null && count($this->Attributes) > 0)
		{
			foreach($this->Attributes as $Attribute)
			{
				if($Columns != null && is_array($Columns) && count($Columns) > 0)
				{
					if(!in_array($Attribute->Name, $Columns))
					{
						continue;
					}
				}
				
				if(!$Attribute->FormIgnore)
				{
					$id = $form_name . "_" . strtolower($Attribute->Name);
					$type = (($Attribute->FormType != null && !empty($Attribute->FormType)) ? $Attribute->FormType : "text");
					$form_image_url = (($Attribute->FormImageUrl != null && !empty($Attribute->FormImageUrl)) ? $Attribute->FormImageUrl : "");
					$required = (($Attribute->FormRequired) ? "required=\"required\"" : "");
					$readonly = (($Attribute->FormReadOnly) ? "readonly" : "");
					$disabled = (($Attribute->FormDisabled) ? "disabled" : "");
					$form_send_on_enter_function = ($ParentFormAjaxId != null && !empty($ParentFormAjaxId)) ? $ParentFormAjaxId."()" :  $ajax_function_name;
					$form_send_on_enter = (($Attribute->FormSendOnEnter) ? "onkeydown='if(event.keyCode == 13) { " . $form_send_on_enter_function . "; }'" : "");
					$autocomplete = (($Attribute->FormAutoComplete) ? "autocomplete" : "");
					$autofocus = (($Attribute->FormAutoFocus) ? "autofocus" : "");
					$min = (($Attribute->FormMin != null && !empty($Attribute->FormMin)) ? "min=\"" . $Attribute->FormMin . "\"" : "");
					$max = (($Attribute->FormMax != null && !empty($Attribute->FormMax)) ? "max=\"" . $Attribute->FormMax . "\"" : "");
					$step = (($Attribute->FormStep != null && !empty($Attribute->FormStep)) ? "step=\"" . $Attribute->FormStep . "\"" : "");
					$pattern = (($Attribute->FormPattern != null && !empty($Attribute->FormPattern)) ? "pattern=\"" . $Attribute->FormPattern . "\"" : "");
					$attribute_class = (($Attribute->FormClass != null && !empty($Attribute->FormClass)) ? $Attribute->FormClass : "");
					$class = (($Class != null && !empty($Class)) ? "class=\"" . $Class . " ". $attribute_class . "\"" : ($attribute_class != null && !empty($attribute_class) ?  "class=\"" . $attribute_class . "\"" : ""));
					$style = ($Attribute->FormStyle != null && !empty($Attribute->FormStyle) ?  "style=\"" . str_replace("=", ":", $Attribute->FormStyle) . "\"" : "");
					$form_click_event = (($Attribute->FormClickEvent != null && !empty($Attribute->FormClickEvent)) ? "onclick=\"". $Attribute->FormClickEvent . "\"" : "");
					$sub_form = (($Attribute->SubForm != null && !empty($Attribute->SubForm)) ? $Attribute->SubForm : null);
					$sub_form_key = (($Attribute->SubFormKey != null && !empty($Attribute->SubFormKey)) ? $Attribute->SubFormKey : null);
					
					if($sub_form != null && $sub_form_key != null && !empty($sub_form) && !empty($sub_form_key))
					{
						$SubFormObject = null;
						$SubTable = new \Entity\Model\Table($sub_form_key, $sub_form, $this->Entity, $this->Entity->LowerCase, $this->Entity->Pluralize, $this->Entity->Collation);
						$result = $result . $SubTable->Form($SubFormObject, $ReturnArray, $Columns, $form_name, $ajax_form_id, $Class, null, $options, $translations);	
						continue;
					}
					
					$form_label = "";

					if($translations != null && $Attribute->FormPlaceholder != null && is_array($translations) && isset($translations[$Attribute->FormPlaceholder]))
					{
						$placeholder = (($Attribute->FormPlaceholder != null && !empty($Attribute->FormPlaceholder)) ? "placeholder=\"" . $translations[$Attribute->FormPlaceholder] . "\"" : "");
					}
					else
					{
						$placeholder = (($Attribute->FormPlaceholder != null && !empty($Attribute->FormPlaceholder)) ? "placeholder=\"" . $Attribute->FormPlaceholder . "\"" : "");
					}

					if($translations != null && $Attribute->FormLabel != null && is_array($translations) && isset($translations[$Attribute->FormLabel]))
					{
						$form_label = "<label class=\"ef-input-label\" for=\"" . $id . "\">" . $translations[$Attribute->FormLabel] . "</label>";
					}
					else
					{
						if($Attribute->FormLabel != null && !empty($Attribute->FormLabel))
						{
							$form_label = "<label class=\"ef-input-label\" for=\"" . $id . "\">" . $Attribute->FormLabel . "</label>";
						}
					}

					if(!$Attribute->FormIsOptions)
					{
						$value = "";

						if($Object != null)
						{
							$prop_name = $this->Entity->GetPropertyNameByString($Object, $Attribute->Name);
							
							if(isset($Object->$prop_name) && $Object->$prop_name != null && !empty($Object->$prop_name))
							{
								$value = $Object->$prop_name;
							}
						}

						if($value == null || empty($value) && $Attribute->FormValue != null && !empty($Attribute->FormValue))
						{
							$value = $Attribute->FormValue;

							if($translations != null && is_array($translations) && isset($translations[$Attribute->FormValue]))
							{
								$value = $translations[$Attribute->FormValue];
							}
						}

						if($form_label != null && $Attribute->FormLabelPosition != "after")
						{
							$result = $result . $form_label;
						}

						if(strtolower($type) == "image" && $form_image_url != null && !empty($form_image_url))
						{
							$result = $result . "<img src=\"" . $form_image_url . "\" id=\"" . $id . "\" " . $class . " " . $style . " name=\"" . $id . "\" " . $form_click_event ." />";
						}
						else if(strtolower($type) == "div" || strtolower($type) == "span" || strtolower($type) == "p" || strtolower($type) == "script" || strtolower($type) == "style")
						{
							$result = $result . "<" . $type . " id=\"" . $id . "\" " . $class . " " . $style . " name=\"" . $id . "\" " . $form_click_event ." >" . $value . "</" . $type . ">";
						}
						else
						{
							$result = $result . "<input type=\"" . $type . "\" id=\"" . $id . "\" name=\"" . $id . "\" maxlength=\"" . $Attribute->Length . "\" " . $required . " " . $readonly . " " . $disabled . " " . $autocomplete . " " . $autofocus . " " . $min . " " . $max . " " . $step . " " . $pattern . " " . $class . " " . $style . " " . $placeholder . " value=\"" . $value . "\" " . $form_click_event ." " . $form_send_on_enter . "/>";
						}

						if($form_label != null && $Attribute->FormLabelPosition == "after")
						{
							$result = $result . $form_label;
						}
					}
					else if($Attribute->FormIsOptions && $Attribute->FormOptions != null && !empty($Attribute->FormOptions))
					{
						$form_options = null;

						if($Attribute->FormOptionsIsTable && $Attribute->FormOptionValue != null && $Attribute->FormOptionName != null && !empty($Attribute->FormOptionValue) && !empty($Attribute->FormOptionName))
						{
							$db_table = $this->Entity->Table($Attribute->FormOptions);
							
							if($db_table != null)
							{
								$db_objects = $db_table->Get();
								
								$db_options = array();
							
								if($db_objects != null)
								{
									if(is_array($db_objects))
									{
										$db_options = $db_objects;
									}
									else
									{
										array_push($db_options, $db_objects);
									}
								}

								if(count($db_options) > 0)
								{
									$form_options = array();

									foreach($db_options as $db_option)
									{
										$option = new \stdClass();
										
										$prop_value = $this->Entity->GetPropertyNameByString($db_option, $Attribute->FormOptionValue);
										$prop_name =  $this->Entity->GetPropertyNameByString($db_option, $Attribute->FormOptionName);
										
										if(isset($db_option->$prop_value) && isset($db_option->$prop_name))
										{
											$option->value = $db_option->$prop_value;
											$option->name = $db_option->$prop_name;	
											array_push($form_options, $option);
										}
									}
								}
							}
						}
						else if($options != null && count($options) > 0 && isset($options[$Attribute->FormOptions]) && count($options[$Attribute->FormOptions]) > 0)
						{
							$form_options = $options[$Attribute->FormOptions];
							
						}

						if($form_options != null && count($form_options) > 0)
						{
							if($Attribute->FormOptionsType == "checkbox" || $Attribute->FormOptionsType == "radio")
							{
								foreach($form_options as $option)
								{
									$selected = ((isset($option->selected) && $option->selected) ? "checked=\"checked\"" : "");
									$result = $result . "<input type=\"" . $Attribute->FormOptionsType . "\" name=\"" . $id. (($Attribute->FormOptionsType == "checkbox") ? "[]" : "") . "\" value=\"" . $option->value . "\" " . $selected . " /> <span class=\"ef-option-label\">" . $option->name . "</span>";
								}
							}
							else
							{
								$result = $result . "<select id=\"" . $id . "\" name=\"" . $id . "\" " . $required . " " . $readonly . " " . $disabled . " " . $autocomplete . " " . $autofocus . " " . $class . " >";
	
								foreach($form_options as $option)
								{
									$selected = ((isset($option->selected) && $option->selected) ? "selected=\"selected\"" : "");
									$result = $result . "<option value=\"" . $option->value . "\" " . $selected . ">" . $option->name . "</option>";
								}
		
								$result = $result . "</select>";
							}
						}
					}
				}
			}
		}		

		if($ParentForm == null)
		{
			if($form_reset == null)
			{
				$form_reset =  ($this->FormReset != null && !empty($this->FormReset)) ? $this->FormReset : "reset";
			}

			if($form_reset != null && $translations != null && isset($translations[$form_reset]))
			{
				$form_reset = $translations[$form_reset];
			}

			if($form_reset != null && $form_reset != "hide")
			{
				$result = $result ."<button type=\"reset\">" . $form_reset . "</button>";
			}

			if($form_submit == null)
			{
				$form_submit =  ($this->FormSubmit != null && !empty($this->FormSubmit)) ? $this->FormSubmit : "submit";
			}

			if($form_submit != null && $translations != null && isset($translations[$form_submit]))
			{
				$form_submit = $translations[$form_submit];
			}

			if($form_submit != null && $form_submit != "hide")
			{
				if($form_type == "ajax")
				{
					$result = $result ."<button type=\"button\" style=\"cursor:pointer;\" onclick=\"". $ajax_function_name . "\">" . $form_submit . "</button>";
				}
				else
				{
					$result = $result ."<button type=\"submit\" style=\"cursor:pointer;\">" . $form_submit . "</button>";
				}
			}

			$ajax_response_name = "response" . $ajax_form_id;
			$result = $result ."<div id=\"" . $ajax_response_name . "\"></div>";

			$result = $result ."<input type=\"hidden\" name=\"ef-post-data\" value=\"" . strtolower($this->Key) . "\">";
			$result = $result ."</form>";

			if($form_loading == null)
			{
				$form_loading = ($this->FormLoading != null && !empty($this->FormLoading)) ? $this->FormLoading : "";
			}

			if($form_type == "ajax")
			{
				$ajax_oncomplete_callback = "";

				if($OnCompleteCallBack != null && !empty($OnCompleteCallBack))
				{
					$ajax_oncomplete_callback = $OnCompleteCallBack."(response);";
				}

				$result = $result . "<script>".
				"function " . $ajax_function_name . " { ".
				"if(typeof jQuery == 'undefined') { ".
				"alert('jQuery not found')".	
				"}".
				"else".
				"{".
					"var loading = document.createElement('img');".
					"jQuery(loading).addClass('ef-loading');".
					"jQuery(loading).attr('src', '" . $form_loading . "');".
					"jQuery('#" . $ajax_response_name . "').html(loading);".
					
					"var form_data = new FormData($('#" . $ajax_form_id . "')[0]);".
					"jQuery.ajax({".
					"type: 'post',".
					"cache: false, ".
					"contentType: false, ".
					"processData: false, ".
					"url: '" . $form_action . "', ".
					"data: form_data,".
					"complete: function(response) { ".					
						"jQuery('#" . $ajax_response_name . "').html('');".
							"if(response != undefined && response.responseText != undefined) { ".
								"jQuery('#" . $ajax_response_name . "').html(response.responseText);".
							"}".
						$ajax_oncomplete_callback .
						"}".
					"});".
				"}".
				"}".
				"</script>";
			}
		}
		else
		{
			$result = $result . "</div>";
		}

		return $result;
	}

	public function HasPost()
	{
		if(isset($_POST["ef-post-data"]) && isset($_POST["ef-post-data"]) && strtolower($_POST["ef-post-data"]) == strtolower($this->Key))
		{
			return true;
		}
		else
		{
			return false;
		}
	}

	public function GetObjectByPost()
	{
		$result = null;
		
		$Table = $this;

		if($Table->HasPost())
		{
			$object = new $Table->Class();
			$table_key = strtolower($Table->Key);
			$LastObject = $object;
			
			foreach($_POST as $key => $value)
			{
				$property = $key;
				$indexOf = strpos($property, "_");
				$parent_property = null;

				while($indexOf !== FALSE)
				{
					$parent_property = substr($property, 0, $indexOf);
					$property = substr($property, $indexOf + 1);
					$indexOf = strpos($property, "_");						
				}					

				if($parent_property != $table_key)
				{
					$ParentAttribute = $Table->GetAttribute($parent_property);

					if($ParentAttribute != null && $ParentAttribute->SubFormKey != null && $ParentAttribute->SubForm != null)
					{
						$parent_prop_name = $Table->Entity->GetPropertyNameByString($AttributeObject, $ParentAttribute->Name);	

						$Table = new \Entity\Model\Table($ParentAttribute->SubFormKey, $ParentAttribute->SubForm, $Table->Entity,$Table->Entity->LowerCase, $Table->Entity->Pluralize, $Table->Entity->Collation);
						$AttributeObject = new $ParentAttribute->SubForm();
						$LastObject->$parent_prop_name = $AttributeObject;
					}
				}
				else
				{
					$Table = $this;
					$AttributeObject = $object;
				}

				$LastObject = $AttributeObject;
				
				$Attribute = $Table->GetAttribute($property);
				
				if($Attribute != null)
				{
					$prop_name = $Table->Entity->GetPropertyNameByString($AttributeObject, $Attribute->Name);	
					$AttributeObject->$prop_name = $this->GetAttributeValueByPost($Attribute, $key, $value);	
				}				
			}

			if($object != null && $_FILES != null && count($_FILES) > 0)
			{
				foreach($_FILES as $key => $value)
				{
					$property = $key;
					$indexOf = strpos($property, "_");
					$parent_property = null;

					$parents = array();

					while($indexOf !== FALSE)
					{
						$parent_property = substr($property, 0, $indexOf);
						$property = substr($property, $indexOf + 1);
						$indexOf = strpos($property, "_");	
						
						if($parent_property != null && !empty($parent_property) && strtolower($parent_property) != strtolower($this->Key))
						{
							array_push($parents, $parent_property);
						}
					}

					if($parents != null && count($parents) > 0)
					{
						$obj = $object;

						foreach($parents as $parent)
						{
							$prop_name = $Table->Entity->GetPropertyNameByString($obj, $parent);
							
							if($prop_name != null)
							{
								$obj = $obj->$prop_name;
							}
						}

						$prop_name = $Table->Entity->GetPropertyNameByString($obj, $property);
						$obj->$prop_name = $value;
					}
				}
			}

			$result = $object;
		}

		return $result;
	}	

	private function GetAttributeValueByPost($Attribute, $PostKey, $PostValue)
	{
		$result = null;

		if($Attribute != null)
		{
			$type = strtolower($Attribute->Type);

			if($type == "bit" || $type == "boolean" || $type == "tinyint")
			{
				$result = isset($_POST[$PostKey]) ? true : false;
			}			
			else
			{
				$result = $PostValue;
			}
		}

		return $result;
	}
}

?>