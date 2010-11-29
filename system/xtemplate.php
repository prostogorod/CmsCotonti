<?php
/**
 * XTemplate 2.2 class library. Fast and lightweight block template engine
 * written specially for Cotonti.
 *
 * @package Cotonti
 * @version 0.6.2
 * @author Vladimir Sibirov a.k.a. Trustmaster
 * @copyright Copyright (c) 2009 Cotonti Team
 * @license BSD
 */

/**
 * Minimalistic XTemplate implementation for Cotonti
 */
class XTemplate
{
	/**
	 * @var array Assigned template vars
	 */
	public $vars = array();
	/**
	 * @var array Blocks
	 */
	public $blocks = array();
	/**
	 * @var string Template file name
	 */
	public $filename = '';

	/**
	 * Simplified constructor
	 *
	 * @param string $path Template file name
	 */
	public function __construct($path = NULL)
	{
		$this->vars['PHP'] =& $GLOBALS;
		if (is_string($path)) $this->restart($path);
	}

	/**
	 * Assigns a template variable or an array of them
	 *
	 * @param mixed $name Variable name or array of values
	 * @param mixed $val Tag value if $name is not an array
	 */
	public function assign($name, $val = NULL)
	{
		if (is_array($name)) foreach ($name as $key => $val) $this->vars[$key] = $val;
		else $this->vars[$name] = $val;
	}

	/**
	 * Evaluates logical expression
	 *
	 * @param string $expr Expression
	 * @return mixed Evaluation result
	 */
	public function evaluate($expr)
	{
		// Apply logical operators
		if (mb_strpos($expr, ' OR ') !== FALSE)
		{
			$res = FALSE;
			$subs = explode(' OR ', $expr);
			foreach ($subs as $sub) $res |= $this->evaluate($sub);
			return $res;
		}
		if (mb_strpos($expr, ' AND ') !== FALSE)
		{
			$res = TRUE;
			$subs = explode(' AND ', $expr);
			foreach ($subs as $sub) $res &= $this->evaluate($sub);
			return $res;
		}
		// Get the first operand which must be a variable
		if (preg_match('`^(!?)\{([\w_\.]+)\}`', $expr, $m))
		{
			$inv = $m[1] == '!';
			$val = $this->get_var($m[2]);
			if (preg_match('`'. preg_quote($m[0]) .'\s*(==|!=|>=|<=|>|<|%|HAS|CONTAINS)\s*(.*)$`', $expr, $m2))
			{
				// Get the operator and second operand
				$val2 = trim($m2[2]);
				if (preg_match('`^\{([\w_\.]+)\}$`', $val2, $m3)) $val2 = $this->get_var($m3[1]);
				elseif (preg_match('`^(\'|")(.*)\\1$`', $val2, $m3)) $val2 = $m3[2];
				// Apply operator
				switch ($m2[1])
				{
					case '==': $res = $val == $val2; break;
					case '!=': $res = $val != $val2; break;
					case '>': $res = $val > $val2; break;
					case '<': $res = $val < $val2; break;
					case '>=': $res = $val >= $val2; break;
					case '<=': $res = $val <= $val2; break;
					case '%':
						$var2 = substr($val2, 0, strpos($val2, ' ')); $var3 = substr($val2, strrpos($val2, ' '));
						$operator = trim(substr($val2, strpos($val2, ' '), strrpos($val2, ' ')));
						$allowed = array('==','!=','>=','<=','>','<');
						if(!is_numeric($val) || !is_numeric($var2) || !is_numeric($var3) || !in_array($operator, $allowed)) $res = FALSE;
						else eval("\$res = $val % $val2;"); break;
					case 'CONTAINS':
						$res = (is_string($val) && is_string($val2) && strpos($val, $val2) !== FALSE) ? TRUE : FALSE;
					break;
					case 'HAS':
						$res = (is_array($val) && is_string($val2) && array_search($val2, $val) !== FALSE) ? TRUE : FALSE;
					break;
					default: $res = FALSE;
				}
				return $inv ? !$res : $res;
			}
			else return $inv ? !$val : $val;
		}
		else return $expr;
	}

	/**
	 * Gets a template variable
	 *
	 * @param string $name Variable name
	 * @return mixed Variable value or NULL if variable was not found
	 */
	public function get_var($name)
	{
		if (mb_strpos($name, '.') !== false)
		{
			$sub = explode('.', $name);
			$var =& $this->vars[$sub[0]];
			$lim = count($sub) - 1;
			for ($i = 1; $i < $lim; $i++)
			{
				if (is_array($var)) $var =& $var[$sub[$i]];
				elseif (is_object($var)) $var =& $var->{$sub[$i]};
				else return NULL;
			}

			if (is_array($var)) return $var[$sub[$i]];
			elseif (is_object($var)) return $var->{$sub[$i]};
		}
		elseif (isset($this->vars[$name])) return $this->vars[$name];
		else return NULL;
	}

	/**
	 * Loads template file structure into memory
	 *
	 * @param string $path Template file path
	 */
	public function restart($path)
	{
		global $cfg;
		if (!file_exists($path))
		{
			throw new Exception("Template file not found: $path");
			return FALSE;
		}
		$this->filename = $path;
		$cache = $cfg['cache_dir'] . '/templates/' . str_replace(array('./', '/'), '_', $path);
		if (!$cfg['xtpl_cache'] || !file_exists($cache) || filemtime($path) > filemtime($cache))
		{
			$this->blocks = array();
			$data = file_get_contents($path);
			// Remove BOM if present
			if ($data[0] == chr(0xEF) && $data[1] == chr(0xBB) && $data[2] == chr(0xBF)) $data = mb_substr($data, 0);
			// FILE includes
			if (preg_match_all('`\{FILE\s+("|\')?(.+?)\\1\}`', $data, $mt, PREG_SET_ORDER))
				foreach ($mt as $m)
					if (preg_match('`\.tpl$`i', $m[2]) && file_exists($m[2]))
						$data = str_replace($m[0], file_get_contents($m[2]), $data);
			// Get root-level blocks
			while (preg_match('`<!--\s*BEGIN:\s*([\w_]+)\s*-->(.*?)<!--\s*END:\s*\1\s*-->`s', $data, $mt))
			{
				$name = $mt[1];
				$bdata = trim($mt[2], " \r\n\t");
				$this->blocks[$name] = new Xtpl_block($bdata);
				$data = str_replace($mt[0], '', $data);
			}
			if ($cfg['xtpl_cache'])
			{
				if (is_writeable($cfg['cache_dir'] . '/templates/'))
					file_put_contents($cache, serialize($this->blocks));
				else
					throw new Exception('Your "' . $cfg['cache_dir'] . '/templates/" is not writable');
			}
		}
		else $this->blocks = unserialize(file_get_contents($cache));
	}

	/**
	 * Prints a parsed block
	 *
	 * @param string $block Block name
	 */
	public function out($block = 'MAIN')
	{
		echo $this->text($block);
	}

	/**
	 * Parses a block
	 *
	 * @param string $block Block name
	 */
	public function parse($block = 'MAIN')
	{
		if (mb_strpos($block, '.') !== FALSE)
		{
			$path = explode('.', $block);
			$block = array_shift($path);
		}
		else $path = array();
		if (is_object($this->blocks[$block])) $this->blocks[$block]->parse($this, $path);
		//else throw new Exception("Block $block is not found in " . $this->filename);
	}

	/**
	 * Clears a parset block data
	 *
	 * @param string $block Block name
	 */
	public function reset($block = 'MAIN')
	{
		if (mb_strpos($block, '.') !== FALSE)
		{
			$path = explode('.', $block);
			$block = array_shift($path);
		}
		else $path = array();
		if (is_object($this->blocks[$block])) $this->blocks[$block]->reset($path);
		//else throw new Exception("Block $block is not found in " . $this->filename);
	}

	/**
	 * Returns parsed block HTML
	 *
	 * @param string $block Block name
	 * @return string
	 */
	public function text($block = 'MAIN')
	{
		if (mb_strpos($block, '.') !== FALSE)
		{
			$path = explode('.', $block);
			$block = array_shift($path);
		}
		else $path = array();
		if (is_object($this->blocks[$block])) return $this->blocks[$block]->text(0, $path);
		else
		{
			//throw new Exception("Block $block is not found in " . $this->filename);
			return '';
		}
	}
}

/**
 * A simple nameless block of data which may parse variables
 */
class Xtpl_data
{
	/**
	 * @var array Block data (HTML/TPL)
	 */
	public $data = '';

	/**
	 * Block constructor
	 *
	 * @param string $data TPL contents
	 */
	public function __construct($data)
	{
		global $cfg;
		$this->data = $cfg['html_cleanup'] ? $this->cleanup($data) : $data;
	}

	/**
	 * Returns parsed block contents
	 *
	 * @param XTemplate $xtpl Reference to XTemplate object
	 * @return string Block data
	 */
	public function text($xtpl)
	{
		$data = $this->data;
		// Apply logical operators
		while (($p1 = mb_strpos($data, '<!-- IF ')) !== FALSE)
		{
			$p2 = mb_strpos($data, ' -->', $p1 + 8);
			$expr = mb_substr($data, $p1 + 8, $p2 - $p1 - 8);
			$p3 = mb_strpos($data, '<!-- ENDIF -->');
			if ($p3 === FALSE) throw new Exception('Logical block "'.htmlspecialchars($expr)
				.'" is not closed correctly in ' . $xtpl->filename);
			$bdata = mb_substr($data, $p2 + 4, $p3 - $p2 - 4);
			if (($p4 = mb_strpos($bdata, '<!-- ELSE -->')) !== FALSE)
			{
				$bdata1 = mb_substr($bdata, 0, $p4);
				$bdata2 = mb_substr($bdata, $p4 + 13);
				if ($xtpl->evaluate($expr))
					$data = mb_substr($data, 0, $p1) . $bdata1 . mb_substr($data, $p3 + 14);
				else
					$data = mb_substr($data, 0, $p1) . $bdata2 . mb_substr($data, $p3 + 14);
			}
			else
			{
				if ($xtpl->evaluate($expr))
					$data = mb_substr($data, 0, $p1) . $bdata . mb_substr($data, $p3 + 14);
				else
					$data = mb_substr($data, 0, $p1) . mb_substr($data, $p3 + 14);
			}
		}
		if (preg_match_all('`\{([\w_.]+)\}`', $data, $mt, PREG_SET_ORDER))
			foreach ($mt as $m) $data = str_replace($m[0], $xtpl->get_var($m[1]), $data);
		return $data;
	}

	/**
	 * Trims spaces before and after tags
	 *
	 * @param string $html Source HTML
	 * @return string Cleaned HTML
	 */
	private function cleanup($html)
	{
		$html = preg_replace('#\n\s+#', ' ', $html);
		$html = preg_replace('#[\r\n\t]+<#', '<', $html);
		$html = preg_replace('#>[\r\n\t]+#', '>', $html);
		$html = preg_replace('# {2,}#', ' ', $html);
		return $html;
	}
}

/**
 * XTemplate block class
 */
class Xtpl_block
{
	/**
	 * @var array Parsed block instances
	 */
	public $data = array();
	/**
	 * @var array<Xtpl_data> Contained blocks
	 */
	public $blocks = array();

	/**
	 * Block constructor
	 *
	 * @param array $blk Reference to XTemplate blocks hashtable
	 * @param string $data TPL contents
	 * @param string $name Block name
	 */
	public function __construct($data)
	{
		// Split the data into nested blocks
		while (!empty($data))
		{
			if (preg_match('`<!--\s*BEGIN:\s*([\w_]+)\s*-->(.*?)<!--\s*END:\s*\1\s*-->`s', $data, $mt))
			{
				// Save plain data
				$pos = mb_strpos($data, $mt[0]);
				$chunk = trim(mb_substr($data, 0, $pos), " \r\n\t");
				$data = mb_substr($data, $pos);
				if (!empty($chunk)) $this->blocks[] = new Xtpl_data($chunk);
				// Get a nested block
				$name = $mt[1];
				$bdata = trim($mt[2], " \r\n\t");
				// Create block object and link to it
				$this->blocks[$name] = new Xtpl_block($bdata);
				// Procceed with less data
				$data = str_replace($mt[0], '', $data);
				$data = trim($data, " \r\n\t");
			}
			else
			{
				$this->blocks[] = new Xtpl_data($data);
				break;
			}
		}
	}

	/**
	 * Parses block contents
	 *
	 * @param XTemplate $xtpl Reference to XTemplate object
	 * @param array $path Recursive tree path
	 */
	public function parse($xtpl, $path = array())
	{
		if (count($path) > 0)
		{
			$block = array_shift($path);
			if (is_object($this->blocks[$block])) $this->blocks[$block]->parse($xtpl, $path);
		}
		else
		{
			foreach ($this->blocks as $block) $data .= $block->text($xtpl);
			$this->data[] = $data;
		}
	}

	/**
	 * Clears parsed block data
	 *
	 * @param array $path Recursive tree path
	 */
	public function reset($path = array())
	{
		if (count($path) > 0)
		{
			$block = array_shift($path);
			if (is_object($this->blocks[$block])) $this->blocks[$block]->reset($path);
		}
		else $this->data = array();
	}

	/**
	 * Returns parsed block HTML
	 *
	 * @param array $path Recursive tree path
	 * @return string
	 */
	public function text($dummy, $path = array())
	{
		if (count($path) > 0)
		{
			$block = array_shift($path);
			return is_object($this->blocks[$block]) ? $this->blocks[$block]->text(0, $path) : '';
		}
		else
		{
			$text = implode('', $this->data);
			$this->data = array();
			return $text;
		}
	}
}
?>