<?php

namespace SFS;

use SMWQueryProcessor as QueryProcessor;
use Parser;

/**
 * @license GNU GPL v2+
 * @since 1.3
 *
 * @author Jason Zhang
 * @author Toni Hermoso Pulido
 */
class SemanticFormsSelect {

	/**
	 * Internal data container
	 *
	 * @var array
	 */
	private static $data = array();

	/**
	 * @var Parser
	 */
	private $parser;

	/**
	 * @since 1.3
	 *
	 * @param Parser $parser
	 */
	public function __construct( &$parser ) {
		$this->parser = $parser;
	}

	/**
	 * @since 1.3
	 *
	 * @return string
	 */
	public static function init( $value, $inputName, $isMandatory, $isDisabled, $otherArgs ) {

		$instance = new self( $GLOBALS['wgParser'] );

		return $instance->select( $value, $inputName, $isMandatory, $isDisabled, $otherArgs );
	}

	public function select ( $cur_value, $input_name, $is_mandatory, $is_disabled, $other_args ) {
		global $wgScriptSelectCount, $sfgFieldNum, $wgUser;

		$selectField = array();
		$values = null;
		$staticvalue = false;

		if ( array_key_exists( "query", $other_args ) ) {
			$query = $other_args["query"];

			$query = str_replace(
				array( "~", "(", ")" ),
				array( "=", "[", "]" ),
				$query
			);

			$selectField["query"] = $query;

			if ( strpos ($query, '@@@@') === false ) {
				$params = explode(";", $query);
				$params[0] = $this->parser->replaceVariables( $params[0] );
				$values = QueryProcessor::getResultFromFunctionParams($params,SMW_OUTPUT_WIKI);
				$staticvalue = true;
			}

		} elseif ( array_key_exists( "function", $other_args ) ) {
			$query = $other_args["function"];
			$query = '{{#'.$query.'}}';

			$query = str_replace(
				array( "~", "(", ")" ),
				array( "=", "[", "]" ),
				$query
			);

			$selectField["function"] = $query;

			if ( strpos( $query, '@@@@' ) === false ) {
				$f = str_replace( ";", "|", $query );
				$values = $this->parser->replaceVariables( $f );
				$staticvalue = true;
			}
		}
		

		$data = array();

		if ( ! $staticvalue ) {
			if ($wgScriptSelectCount == 0 ) {
				Output::addModule( 'ext.sf_select.scriptselect' );
			}
			
			$wgScriptSelectCount++;

		}
		
		$data['label'] = array_key_exists( 'label', $other_args );


		$data["selectismultiple"] = array_key_exists( "part_of_multiple", $other_args );

		$index = strpos($input_name, "[");
		$data['selecttemplate'] = substr($input_name, 0, $index);

		// Does hit work for multiple template?
		$index = strrpos($input_name, "[");
		$data['selectfield'] = substr( $input_name, $index+1, strlen( $input_name ) - $index - 2 );

		$valueField = array();
		$data["valuetemplate"] = array_key_exists( "sametemplate", $other_args ) ? $data['selecttemplate'] : $other_args["template"];
		$data["valuefield"] = $other_args["field"];

		$data['selectrm'] = array_key_exists( 'rmdiv', $other_args );
		$data['label'] = array_key_exists( 'label', $other_args );
		$data['sep'] = array_key_exists( 'sep', $other_args ) ? $other_args["sep"] : ',';

		$values = explode( $data['sep'] , $values);
		$values = array_map("trim", $values);
		$values = array_unique($values);

		if (array_key_exists("query", $selectField ) ) {
			$data['selectquery'] = $selectField['query'];
		} else{
			$data['selectfunction'] = $selectField['function'];
		}

		// Avoids repeating data info
		if ( self::notInArray( "selectfield", $data["selectfield"], self::$data ) && ! $staticvalue ) {
				
			self::$data[] = $data;

		}

		$extraatt="";
		$is_list=false;

		// TODO This needs clean-up

		if (array_key_exists('is_list', $other_args) && $other_args['is_list']==true){
			$is_list=true;
		}
		if ($is_list){
			$extraatt=' multiple="multiple" ';
		}
		if(array_key_exists("size", $other_args)){
			$extraatt.=" size=\"{$other_args['size']}\"";
		}
		$classes=array();
		if($is_mandatory){
			$classes[]="mandatoryField";
		}
		if (array_key_exists("class", $other_args)){
			$classes[]=$other_args['class'];
		}
		if ($classes){
			$cstr=implode(" ",$classes);
			$extraatt.=" class=\"$cstr\"";
		}
		$inname=$input_name;
		if ($is_list){
			$inname.='[]';
		}

		// TODO Use Html::

		$spanextra=$is_mandatory?'mandatoryFieldSpan':'';

		$curvaluesStr = "";
		$curvalues = array();
		
		if ( $cur_value ){
			if ( $cur_value==='current user' ){
				$curvaluesStr=$wgUser->getName();
			}
			if ( is_array( $cur_value ) ){
				$curvaluesStr=implode( $data["sep"], $cur_value );
				$curvalues = $cur_value;

			} else{
				// Added sep values
				$curvaluesStr=trim( $cur_value );
				$curvalues=array_map("trim", explode($data['sep'], $cur_value));
			}

		}
		
		// Separator
		$extraatt = $extraatt." data-sep='".$data['sep']."'";
		
		// Curvalues
		$extraatt = $extraatt." data-curvalues='".str_replace( "'", "\'", $curvaluesStr )."'";

		// Putting cur values here
		$ret="<span class=\"inputSpan $spanextra\"><select name='$inname' id='input_$sfgFieldNum' $extraatt>";
		
		$labelArray = array();
		
		if ( array_key_exists( "label", $data ) && $values ) {
			$labelArray = self::getLabels( $values );
		}
		
		
		// TODO handle empty value case.
		$ret.="<option></option>";

		if ( $staticvalue ){
			foreach( $values as $val ){
				
				$selected = "";
				if ( in_array( $val, $curvalues ) ) {
					$selected = " selected='selected'";
				}
				
				if ( array_key_exists( $val, $labelArray ) ) {
					$ret.="<option".$selected." value='".$labelArray[ $val ][0]."'>".$labelArray[ $val ][1]."</option>";
				} else {
					$ret.="<option".$selected.">$val</option>";
				}
			}
		}

		$ret.="</select></span>";
		$ret.="<span id=\"info_$sfgFieldNum\" class=\"errorMessage\"></span>";

		if ($other_args["is_list"]){
			$hiddenname=$input_name.'[is_list]';
			$ret.="<input type='hidden' name='$hiddenname' value='1' />";
		}

		if ( !$staticvalue ){
			Output::addToHeadItem( 'sf_select', self::$data );
		}

		Output::commitToParserOutput( $this->parser->getOutput() );

		return $ret;
	}
	
	private static function notInArray( $field, $value, $array ) {
		
		foreach ( $array as $item ) {
			
			if ( array_key_exists( $field, $item ) ) {
				
				if ( $item[$field] == $value ) {
					return false;
				}
			}
			
		}
		
		return true;
		
	}
	
	private static function getLabels( $labels ) {
			
		foreach ( $labels as $label ) {
			
			// Check Break
			$openBr = 0;
			$doneBr = 0;
			$num = 0;
			
			$labelArr = str_split ( $label );
			
			$end = count( $labelArr ) - 1;
			$iter = $end;
			
			$endBr = $end;
			$startBr = 0;
			
			while ( $doneBr == 0 && $iter >= 0 ) {
			
				$char = $labelArr[ $iter ];
				
				if ( $char == ")" ) {
					$openBr = $openBr - 1;
					
					if ( $num == 0 ) {
						$endBr = $iter;
						$num = $num + 1;
					}
				}
				
				if ( $char == "(" ) {
					$openBr = $openBr + 1;
					
					if ( $num > 0 && $openBr == 0 ) {
						$startBr = $iter;
						$doneBr = 1;
					}
				}
				
				$iter = $iter - 1;
				
			}
			
			$labelValue = implode( "", array_slice( $labelArr, $startBr+1, $endBr-$startBr-1 ) );			
			$labelKey = implode( "", array_slice( $labelArr, 0, $startBr-1 ) );
			
			
			$labelArray[ $label ] = [ $labelKey, $labelValue ] ;
		}
		
		return $labelArray;
		
	}

}
