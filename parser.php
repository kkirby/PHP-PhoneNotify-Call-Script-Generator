<?php
require_once('nikic-PHP-Parser/lib/bootstrap.php');
class Printer {
	public $statements;
	
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
			$this->output = $this->printStatements(
				$parser->parse(file_get_contents($script))
			);
		}
		catch (PHPParser_Error $e) {
			echo 'Parse Error: ', $e->getMessage();
		}
	}
	
	public function printStatements($statements){
		$ret = array();
		foreach($statements as $k => $v){
			$ret[] = $this->printStatement($v);
		}
		return implode("\n",$ret);
	}
	
	public function printStatement($statementNode){
		return $this->{'print_' . $statementNode->getType()}($statementNode);
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
		$labelStatement = self::getUID('if_statement');
		$labelEnd = self::getUID('if_end');
		$labelTotalEnd = self::getUID('if_total_end');
		$methodCallLeft = $this->printStatement($node->cond->left);
		$methodCallRight = $this->printStatement($node->cond->right);
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
		$labelStart = self::getUID('while_start');
		$labelCond = self::getUID('while_cond');
		$labelEnd = self::getUID('while_end');
		$ret[] = '~\Label(' . $labelCond . ')~';
		$methodCallLeft = $this->printStatement($node->cond->left);
		$methodCallRight = $this->printStatement($node->cond->right);
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
	
	public function processCond($cond){
		$variable = uniqid();
		$method = $cond->name->parts[0];
		$methodCall = $this->processMethod($cond);
		var_dump($cond);
		return end($methodCall);
		
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
			else if($method->args[0]->value->getType() != 'Expr_Variable'){
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
		throw new Exception('PHP Error: ' . $message . ' In ' . $this->script . ' on line ' . $node->getLine() . '.');
	}
}

require_once('test.php');
?>