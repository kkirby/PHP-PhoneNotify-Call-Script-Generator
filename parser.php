<?php
require_once('nikic-PHP-Parser/lib/bootstrap.php');
class Printer {
	public $functions;
	public $functionStack = array();
	
	public function pushFunctionToCallStack($name,$calledNode){
		$this->functionStack[] = array($name,$calledNode);
	}
	
	public function popFunctionInCallStack(){
		array_pop($this->functionStack);
	}
	
	public function isFunctionInCallStack($function){
		foreach($this->functionStack as $stackItem){
			if($stackItem[0] == $function){
				return true;
			}
		}
		return false;
	}
	
	public static function getUID($namespace = ''){
		return $namespace . strtoupper(
			substr(
				preg_replace(
					'/[^a-z]/i',
					'',
					base64_encode(
						md5(
							uniqid($namespace)
						)
					)
				)
				,0,5
			)
		);
	}
	
	public function __construct($script){
		$this->script = $script;
		$parser = new PHPParser_Parser(new PHPParser_Lexer);
		try {
			$output = $this->printStatements(
				$parser->parse(file_get_contents($script))
			);
			$this->output = $output;
		}
		catch (PHPParser_Error $e) {
			echo 'Parse Error: ', $e->getMessage();
		}
	}
	
	public function printStatements($statements,$returnVar = false){
		$ret = array();
		foreach($statements as $k => $v){
			$temp = $this->printStatement($v,$returnVar);
			if(is_array($temp)){
				$temp = $temp[1];
			}
			if(!empty($temp))$ret[] = $temp;
		}
		return implode("\n",$ret);
	}
	
	public function printStatement($statementNode,$returnVar = false){
		return $this->{'print_' . $statementNode->getType()}($statementNode,$returnVar);
	}
	
	public function print_Stmt_Echo($node){
		$res = $this->print_Stmt_Echo_Helper($node->exprs[0]);
		return trim(implode("\n",$res));
	}
	
	protected function print_Stmt_Echo_Helper($node){
		$preExpressions = array();
		$ret = array();
		$type = $node->getType();
		if($type == 'Expr_Variable'){
			$ret[] = '~\VariableToTTS(' . $this->printStatement($node) . ')~';
		}
		else if($type == 'Scalar_Encapsed'){
			foreach($node->parts as $part){
				if(!is_object($part)){
					$ret[] = $part;
				}
				else {
					$res = $this->print_Stmt_Echo_Helper($part);
					if(!empty($res[0])){
						$preExpressions[] = $res[0];
					}
					$ret[] = $res[1];
				}
			}
		}
		else if($type == 'Expr_Concat'){
			$res = $this->print_Stmt_Echo_Helper($node->left);
			if(!empty($res[0])){
				$preExpressions[] = $res[0];
			}
			$ret[] = $res[1];
			$res = $this->print_Stmt_Echo_Helper($node->right);
			if(!empty($res[0])){
				$preExpressions[] = $res[0];
			}
			$ret[] = $res[1];
		}
		else if($type == 'Expr_FuncCall'){
			list($varName,$statement) = $this->printStatement($node);
			$preExpressions[] = $statement;
			$ret[] = '~\VariableToTTS(' . $varName . ')~';
		}
		else {
			$ret[] = $this->printStatement($node);
		}
		return array(implode("\n",$preExpressions),implode("\n",$ret));
	}
	
	public function print_Expr_Assign($node,$returnName = false){
		$retVar = $node->var->name;
		$expression = $node->expr;
		if($expression->getType() == 'Expr_FuncCall'){
			$ret = end($this->processMethod($expression,$retVar));
		}
		else {
			return '~\SetVar(' . $retVar . '|' . $this->printStatement($expression) . ')~';
		}
		if($returnName){
			return array($retVar,$ret);
		}
		else {
			return $ret;
		}
	}
	
	public function print_Expr_FuncCall($node){
		return $this->processMethod($node);
	}
	
	public function print_Expr_Variable($node){
		return $node->name;
	}
	
	public function print_Scalar_LNumber($node){
		return $node->value;
	}
	
	public function print_Expr_ConstFetch($node){
		return $node->name->parts[0];
	}
	
	public function print_Scalar_String($node){
		return $node->value;
	}
	
	public function print_Stmt_If($node){
		$ret = array();
		$labelStatement = self::getUID('lbl_if_statement');
		$labelEnd = self::getUID('lbl_if_end');
		$labelTotalEnd = self::getUID('lbl_if_total_end');
		$methodCallLeft = $this->printStatement($node->cond->left,true);
		$methodCallRight = $this->printStatement($node->cond->right,true);
		if(is_array($methodCallLeft)){
			$ret[] = $methodCallLeft[1];
			$condVarLeft = $methodCallLeft[0];
		}
		else {
			$condVarLeft = $methodCallLeft;
		}
		if(is_array($methodCallRight)){
			$ret[] = $methodCallRight[1];
			$condVarRight = $methodCallRight[0];
		}
		else {
			$condVarRight = $methodCallRight;
		}
		if($node->cond->getType() == 'Expr_Equal'){
			$ret[] = '~\GotoIf(' . $condVarLeft . '|' . $condVarRight . '|' . $labelStatement . ')~';
			$ret[] = '~\Goto(' . $labelEnd . ')~';
		}
		else if($node->cond->getType() == 'Expr_NotEqual'){
			$ret[] = '~\GotoIf(' . $condVarLeft . '|' . $condVarRight . '|' . $labelEnd . ')~';
			$ret[] = '~\Goto(' . $labelStatement . ')~';
		}
		$ret[] = '~\Label(' . $labelStatement . ')~';
		$ret[] = $this->printStatements($node->stmts);
		$ret[] = '~\Goto(' . $labelTotalEnd . ')~';
		$ret[] = '~\Label(' . $labelEnd . ')~';
		if($node->else){
			$ret[] = $this->printStatements($node->else->stmts);
		}
		$ret[] = '~\Label(' . $labelTotalEnd . ')~';
		return implode("\n",$ret);
	}
	
	public function print_Stmt_While($node){
		$ret = array();
		$labelStart = self::getUID('lbl_while_start');
		$labelCond = self::getUID('lbl_while_cond');
		$labelEnd = self::getUID('lbl_while_end');
		$ret[] = '~\Label(' . $labelCond . ')~';
		$methodCallLeft = $this->printStatement($node->cond->left,true);
		$methodCallRight = $this->printStatement($node->cond->right,true);
		if(is_array($methodCallLeft)){
			$ret[] = $methodCallLeft[1];
			$condVarLeft = $methodCallLeft[0];
		}
		else {
			$condVarLeft = $methodCallLeft;
		}
		if(is_array($methodCallRight)){
			$ret[] = $methodCallRight[1];
			$condVarRight = $methodCallRight[0];
		}
		else {
			$condVarRight = $methodCallRight;
		}
		if($node->cond->getType() == 'Expr_NotEqual'){
			$ret[] = '~\GotoIf(' . $condVarLeft . '|' . $condVarRight . '|' . $labelEnd . ')~';
		}
		else if($node->cond->getType() == 'Expr_Equal'){
			$ret[] = '~\GotoIf(' . $condVarLeft . '|' . $condVarRight . '|' . $labelStart . ')~';
			$ret[] = '~\Goto(' . $labelEnd . ')~';
		}
		$ret[] = '~\Label(' . $labelStart . ')~';
		$ret[] = $this->printStatements($node->stmts);
		$ret[] = '~\Goto(' . $labelCond . ')~';
		$ret[] = '~\Label(' . $labelEnd . ')~'; 
		return implode("\n",$ret);
	}
	
	public function print_Stmt_Function($node){
		$name = $node->name;
		$params = array();
		foreach($node->params as $param){
			$params[] = $param->name;
		}
		$this->functions[$name] = array(
			'label' => self::getUID('function'),
			'params' => $params,
			'contents' => $node->stmts
		);
		return '';
	}
	
	public function buildFunction($name,$paramAssignments,$retVar,$calledNode){
		if($this->isFunctionInCallStack($name)){
			$stack = array();
			foreach($this->functionStack as $stackItem){
				$stack[] = 'Function ' . $stackItem[0] . '() on line ' . $stackItem[1]->getLine();
			}
			$this->_throwErrorForNode(
				"Recursive function calling is not allowed.\nCall Stack: \n".
				implode("\n",$stack),
				$calledNode
			);
		}
		$this->pushFunctionToCallStack($name,$calledNode);
		$function = $this->functions[$name];
		$contents = $this->printStatements($function['contents']);
		$this->popFunctionInCallStack();
		$contents = str_replace(
			$function['params'],
			$paramAssignments,
			$contents
		);
		$contents = str_replace('__RETURN__',$hook['returnVar'],$contents);
		return $contents;
	}
	
	public function processMethod($method,$assignTo = null){
		$retVar = $assignTo ?: self::getUID();
		$name = $method->name->parts[0];
		$text = array();
		
		$args = array();
		if($method->args){
			foreach($method->args as $argument){
				$args[] = $this->processArgument($argument);
			}
		}
		foreach($args as $arg){
			if($arg[0] != $arg[1]){
				$text[] = $arg[1];
			}
		}
		$_tempArgs = array($retVar);
		foreach($args as $arg){
			$_tempArgs[] = $arg[0];
		}
		
		if($name == 'getLength'){
			if(count($args) < 1){
				$this->_throwErrorForNode(
					'getLength requires at least one argument. No arguments passed.',
					$method
				);
			}
			else if(
				$method->args[0]->value->getType() != 'Expr_Variable' && $method->args[0]->value->getType() != 'Expr_Assign'
			){
				$this->_throwErrorForNode(
					'getLength requires argument 1 to be a variable, instead a '.
					$method->args[0]->value->getType() . ' was passed.',
					$method
				);
			}
			//else if($method->args[0]->getType() != )
			$text[] = '~\GetVariableLength(' . $args[0][0] . '|' . $retVar . ')~';
		}
		else if($name == 'getDigits'){
			$temp = '~\getDigits(';
			$temp .= implode('|',$_tempArgs);
			$temp .= ')~';
			$text[] = $temp;
		}
		else if($name == 'file_get_contents'){
			$text[] = '~\QueryExternalServer(' . $args[0][0] . '|' . $retVar . ')~';
		}
		else if(isset($this->functions[$name])){
			$def = $this->functions[$name];
			$paramAssignments = array();
			foreach($args as $key => $arg){
				if(isset($def['params'][$key])){
					if($arg[0] == $arg[1] && $method->args[$key]->value->getType() != 'Expr_Variable'){
						$_tmpUid = self::getUID();
						$text[] = '~\SetVar('.$_tmpUid.'|'.$arg[0].')~';
						$arg[0] = $_tmpUid;
					}
					$paramAssignments[] = $arg[0];
				}
			}
			$text[] = $this->buildFunction(
				$name,
				$paramAssignments,
				$retVar,
				$method
			);
		}
		else {
			$ret =  '~\\' . $name . '(';
			$_retArgs = array();
			foreach($args as $arg){
				$_retArgs[] = $arg[0];
			}
			$ret .= implode('|',$_retArgs);
			$ret .= ')~';
			$text[] = $ret;
			return implode("\n",$text);
		}
		return array($retVar,implode("\n",$text));
	}
	
	public function processArgument($arg){
		if($arg->value->getType() == 'Expr_Assign'){
			return array($arg->value->var->name,$this->print_Expr_Assign($arg->value));
		}
		else {
			$ret = $this->printStatement($arg->value);
			if(is_array($ret)){
				return $ret;
			}
			return array($ret,$ret);
		}
	}
	
	protected function _throwErrorForNode($message,$node){
		throw new Exception('PHP Error: ' . $message . "\nIn " . $this->script . ' on line ' . $node->getLine() . '.');
	}
}

require_once('test.php');
?>