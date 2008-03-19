<?php
/*****************************************
* File      :   $RCSfile: class.gdb.mysql.php,v $
* Project   :   Contenido
* Descr     :   MySQL Driver for GenericDB 
* Modified  :   $Date: 2006/10/05 23:44:43 $
*
* � four for business AG, www.4fb.de
*
* $Id: class.gdb.mysql.php,v 1.12 2006/10/05 23:44:43 bjoern.behrens Exp $
******************************************/
cInclude ("classes", 'drivers/class.gdb.driver.php');

class gdbMySQL extends gdbDriver
{
	function buildJoinQuery ($destinationTable, $destinationClass, $destinationPrimaryKey, $sourceClass, $primaryKey)
	{
		/* Build a regular LEFT JOIN */
		$field  = "$destinationClass.$destinationPrimaryKey";
		$tables = "";
		$join   = "LEFT JOIN $destinationTable AS $destinationClass ON $sourceClass.$primaryKey = $destinationClass.$primaryKey";
		$where  = "";
		
		return array("field" => $field, "table" => $tables, "join" => $join, "where" => $where);
	}
	
	function buildOperator ($sField, $sOperator, $sRestriction)
	{
		$sOperator = strtolower($sOperator);
		
		$sWhereStatement = "";
		
		switch ($sOperator)
		{
			case "matchbool":
				$sqlStatement = "MATCH (%s) AGAINST ('%s' IN BOOLEAN MODE)";
				$sWhereStatement = sprintf($sqlStatement, $sField, $this->_oItemClassInstance->_inFilter($sRestriction));
				break;
			case "match":
				$sqlStatement = "MATCH (%s) AGAINST ('%s')";
				$sWhereStatement = sprintf($sqlStatement, $sField, $this->_oItemClassInstance->_inFilter($sRestriction));
				break;
			case "like":
				$sqlStatement = "%s LIKE '%%%s%%'";
				$sWhereStatement = sprintf($sqlStatement, $sField, $this->_oItemClassInstance->_inFilter($sRestriction));
				break;
			case "likeleft":
				$sqlStatement = "%s LIKE '%s%%'";
				$sWhereStatement = sprintf($sqlStatement, $sField, $this->_oItemClassInstance->_inFilter($sRestriction));
				break;
			case "likeright":
				$sqlStatement = "%s LIKE '%%%s'";
				$sWhereStatement = sprintf($sqlStatement, $sField, $this->_oItemClassInstance->_inFilter($sRestriction));
				break;
			case "notlike":
				$sqlStatement = "%s NOT LIKE '%%%s%%'";
				$sWhereStatement = sprintf($sqlStatement, $sField, $this->_oItemClassInstance->_inFilter($sRestriction));
				break;
			case "notlikeleft":
				$sqlStatement = "%s NOT LIKE '%s%%'";
				$sWhereStatement = sprintf($sqlStatement, $sField, $this->_oItemClassInstance->_inFilter($sRestriction));
				break;
			case "notlikeright":
				$sqlStatement = "%s NOT LIKE '%%%s'";
				$sWhereStatement = sprintf($sqlStatement, $sField, $this->_oItemClassInstance->_inFilter($sRestriction));
				break;				
			case "diacritics":
				cInclude("classes", "class.chartable.php");
				if (!is_object($GLOBALS["_cCharTable"]))
				{
					$GLOBALS["_cCharTable"] = new cCharacterConverter;
				}

				$aliasSearch = array ();

				$metaCharacters = array ("*", "[", "]", "^", '$', "\\", "*", "'", '"', '+');

				for ($i = 0; $i < strlen($sRestriction); $i ++)
				{
					$char = substr($sRestriction, $i, 1);

					$aliases = array ();

					$aliases = array_merge($aliases, $GLOBALS["_cCharTable"]->fetchDiacriticCharactersForNormalizedChar($this->_sEncoding, $char));
					$normalizedChars = $GLOBALS["_cCharTable"]->fetchNormalizedCharsForDiacriticCharacter($this->_sEncoding, $char);

					foreach ($normalizedChars as $normalizedChar)
					{
						$aliases = array_merge($aliases, $GLOBALS["_cCharTable"]->fetchDiacriticCharactersForNormalizedChar($this->_sEncoding, $normalizedChar));
					}

					$aliases = array_merge($aliases, $normalizedChars);

					if (count($aliases) > 0)
					{
						$aliases[] = $char;
						$allAliases = array ();

						foreach ($aliases as $alias)
						{
							$alias1 = $this->_oItemClassInstance->_inFilter($alias);
							$allAliases[] = $alias1;
							$allAliases[] = $alias;
						}

						$allAliases = array_unique($allAliases);
						$aliasSearch[] = "(".implode("|", $allAliases).")";

					} else
					{
						$addChars = array();
						
						
                        
						if (in_array($char, $metaCharacters))
						{
							$addChars[] = "\\\\".$char;
						} else
						{
                            $addChars[] = $char;
                            
							$vChar = $this->_oItemClassInstance->_inFilter($char);
                            
                            if ($char != $vChar)
                            {
                                if (in_array($vChar, $metaCharacters))
                                {
                                    $addChars[] = "\\\\".$vChar;
                                } else {
                                    $addChars[] = $vChar;	
                                }
                            }
						}
                        
                        $aliasSearch[] = "(".implode("|", $addChars).")";
					}
				}

				$restriction = "'".implode("", $aliasSearch)."'";
				$sWhereStatement = implode(" ", array ($sField, "REGEXP", $restriction));

				break;
			case "fulltext":
				
				break;
			case "in":
				if (is_array($sRestriction))
				{
					$items = array();
					
					foreach ($sRestriction as $key => $sRestrictionItem)
					{
						$items[] = "'".$this->_oItemClassInstance->_inFilter($sRestrictionItem)."'";
					}
					
					$sRestriction = implode(", ", $items);
				} else {
					$sRestriction = "'" . $sRestriction ."'";	
				}
				
				$sWhereStatement = implode(" ", array($sField, "IN (", $sRestriction, ")"));
				break; 
			default :
				$sRestriction = "'".$this->_oItemClassInstance->_inFilter($sRestriction)."'";
				
				$sWhereStatement = implode(" ", array ($sField, $sOperator, $sRestriction));
				
		}
		
		return $sWhereStatement;
		
	}	
}
?>
