<?php

namespace Entity\Model;

if(!defined("EntityFramework")){ die("Access Denied!"); }

class Attribute
{
	public $Table;
	public $Name;
	public $Type;
	public $Length;
	public $IsPK;
	public $IsUnique;
	public $IsIndex;
	public $IsFK;
	public $IsAutoIncrement;
	public $FK_RefTable;
	public $FK_RefColumn;
	public $DefaultValue;
	public $Collation;
	public $AllowNull;

	/** Form Attributes */
	public $FormIgnore;
	public $FormType;
	public $FormValue;
	public $FormRequired;
	public $FormPattern;
	public $FormReadOnly;
	public $FormDisabled;
	public $FormAutoComplete;
	public $FormAutoFocus;
	public $FormMin;
	public $FormMax;
	public $FormStep;	
	public $FormIsOptions;
	public $FormOptions;
	public $FormOptionsIsTable;
	public $FormOptionValue;
	public $FormOptionName;
	public $FormOptionsType;
	public $FormPlaceholder;
	public $FormLabel;
	public $FormLabelPosition;
	public $FormImageUrl;
	public $FormClass;
	public $FormStyle;
	public $FormClickEvent;
	public $FormSendOnEnter;
	public $SubForm;
	public $SubFormKey;
}

?>