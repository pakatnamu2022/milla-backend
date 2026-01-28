<?php

namespace App\Http\Services\gp\gestionhumana\payroll;

use Exception;

/**
 * Safe formula parser using Shunting-yard algorithm + RPN evaluation
 * Supports basic arithmetic operations and common functions
 */
class FormulaParserService
{
  // Allowed operators with their precedence and associativity
  private const OPERATORS = [
    '+' => ['precedence' => 2, 'associativity' => 'left'],
    '-' => ['precedence' => 2, 'associativity' => 'left'],
    '*' => ['precedence' => 3, 'associativity' => 'left'],
    '/' => ['precedence' => 3, 'associativity' => 'left'],
    '%' => ['precedence' => 3, 'associativity' => 'left'],
    '^' => ['precedence' => 4, 'associativity' => 'right'],
  ];

  // Allowed functions and their argument counts
  private const FUNCTIONS = [
    'ROUND' => 2,
    'FLOOR' => 1,
    'CEIL' => 1,
    'ABS' => 1,
    'MIN' => 2,
    'MAX' => 2,
    'IF' => 3,
  ];

  // Comparison operators for IF function
  private const COMPARISONS = ['>=', '<=', '!=', '==', '>', '<'];

  /**
   * Evaluate a formula with given variables
   *
   * @param string $formula The formula to evaluate
   * @param array $variables Key-value pairs of variable names and their values
   * @return float The result rounded to 2 decimal places
   * @throws Exception If formula is invalid or contains disallowed elements
   */
  public function evaluate(string $formula, array $variables): float
  {
    // 1. Validate formula syntax
    $this->validateFormula($formula);

    // 2. Replace variables with their values
    $formula = $this->replaceVariables($formula, $variables);

    // 3. Tokenize the formula
    $tokens = $this->tokenize($formula);

    // 4. Convert to RPN (Reverse Polish Notation) using Shunting-yard
    $rpn = $this->toRPN($tokens);

    // 5. Evaluate RPN expression
    $result = $this->evaluateRPN($rpn);

    // 6. Return result rounded to 2 decimal places
    return round($result, 2);
  }

  /**
   * Validate formula syntax for security
   */
  private function validateFormula(string $formula): void
  {
    // Check for dangerous patterns
    $dangerousPatterns = [
      '/\$/',           // PHP variables
      '/`/',            // Backticks
      '/exec/i',        // exec function
      '/system/i',      // system function
      '/shell/i',       // shell functions
      '/eval/i',        // eval function
      '/file/i',        // file functions
      '/include/i',     // include
      '/require/i',     // require
      '/[\\\\]/',       // Backslashes
      '/;/',            // Semicolons (multiple statements)
    ];

    foreach ($dangerousPatterns as $pattern) {
      if (preg_match($pattern, $formula)) {
        throw new Exception('Invalid formula: contains disallowed characters or functions');
      }
    }

    // Check for balanced parentheses
    $depth = 0;
    for ($i = 0; $i < strlen($formula); $i++) {
      if ($formula[$i] === '(') $depth++;
      if ($formula[$i] === ')') $depth--;
      if ($depth < 0) {
        throw new Exception('Invalid formula: unbalanced parentheses');
      }
    }
    if ($depth !== 0) {
      throw new Exception('Invalid formula: unbalanced parentheses');
    }
  }

  /**
   * Replace variable names with their values
   */
  private function replaceVariables(string $formula, array $variables): string
  {
    // Sort by length descending to avoid partial replacements
    uksort($variables, function ($a, $b) {
      return strlen($b) - strlen($a);
    });

    foreach ($variables as $name => $value) {
      // Only replace whole words
      $pattern = '/\b' . preg_quote($name, '/') . '\b/';
      $formula = preg_replace($pattern, (string) $value, $formula);
    }

    return $formula;
  }

  /**
   * Tokenize the formula into individual tokens
   */
  private function tokenize(string $formula): array
  {
    $tokens = [];
    $formula = str_replace(' ', '', $formula);
    $length = strlen($formula);
    $i = 0;

    while ($i < $length) {
      $char = $formula[$i];

      // Number (including decimals)
      if (is_numeric($char) || ($char === '.' && $i + 1 < $length && is_numeric($formula[$i + 1]))) {
        $number = '';
        while ($i < $length && (is_numeric($formula[$i]) || $formula[$i] === '.')) {
          $number .= $formula[$i];
          $i++;
        }
        $tokens[] = ['type' => 'number', 'value' => (float) $number];
        continue;
      }

      // Negative number at start or after operator/open paren
      if ($char === '-' && ($i === 0 || $this->isOperatorOrOpenParen(end($tokens)))) {
        $i++;
        $number = '-';
        while ($i < $length && (is_numeric($formula[$i]) || $formula[$i] === '.')) {
          $number .= $formula[$i];
          $i++;
        }
        $tokens[] = ['type' => 'number', 'value' => (float) $number];
        continue;
      }

      // Function name
      if (ctype_alpha($char)) {
        $name = '';
        while ($i < $length && (ctype_alnum($formula[$i]) || $formula[$i] === '_')) {
          $name .= $formula[$i];
          $i++;
        }
        $upperName = strtoupper($name);
        if (array_key_exists($upperName, self::FUNCTIONS)) {
          $tokens[] = ['type' => 'function', 'value' => $upperName];
        } else {
          // Unknown variable - should have been replaced
          throw new Exception("Unknown variable or function: {$name}");
        }
        continue;
      }

      // Comparison operators (for IF function)
      $twoCharOp = substr($formula, $i, 2);
      if (in_array($twoCharOp, self::COMPARISONS)) {
        $tokens[] = ['type' => 'comparison', 'value' => $twoCharOp];
        $i += 2;
        continue;
      }

      // Single char comparison operators
      if ($char === '>' || $char === '<') {
        $tokens[] = ['type' => 'comparison', 'value' => $char];
        $i++;
        continue;
      }

      // Operators
      if (array_key_exists($char, self::OPERATORS)) {
        $tokens[] = ['type' => 'operator', 'value' => $char];
        $i++;
        continue;
      }

      // Parentheses
      if ($char === '(') {
        $tokens[] = ['type' => 'left_paren', 'value' => '('];
        $i++;
        continue;
      }

      if ($char === ')') {
        $tokens[] = ['type' => 'right_paren', 'value' => ')'];
        $i++;
        continue;
      }

      // Comma (function argument separator)
      if ($char === ',') {
        $tokens[] = ['type' => 'comma', 'value' => ','];
        $i++;
        continue;
      }

      // Unknown character
      throw new Exception("Invalid character in formula: {$char}");
    }

    return $tokens;
  }

  /**
   * Check if token is an operator or open parenthesis
   */
  private function isOperatorOrOpenParen($token): bool
  {
    if ($token === false) return true;
    return $token['type'] === 'operator' || $token['type'] === 'left_paren' || $token['type'] === 'comma';
  }

  /**
   * Convert tokens to Reverse Polish Notation using Shunting-yard algorithm
   */
  private function toRPN(array $tokens): array
  {
    $output = [];
    $operatorStack = [];

    foreach ($tokens as $token) {
      switch ($token['type']) {
        case 'number':
          $output[] = $token;
          break;

        case 'function':
          $operatorStack[] = $token;
          break;

        case 'comma':
          while (!empty($operatorStack) && end($operatorStack)['type'] !== 'left_paren') {
            $output[] = array_pop($operatorStack);
          }
          break;

        case 'operator':
          while (
            !empty($operatorStack) &&
            end($operatorStack)['type'] === 'operator' &&
            ($this->shouldPopOperator($token['value'], end($operatorStack)['value']))
          ) {
            $output[] = array_pop($operatorStack);
          }
          $operatorStack[] = $token;
          break;

        case 'comparison':
          $operatorStack[] = $token;
          break;

        case 'left_paren':
          $operatorStack[] = $token;
          break;

        case 'right_paren':
          while (!empty($operatorStack) && end($operatorStack)['type'] !== 'left_paren') {
            $output[] = array_pop($operatorStack);
          }
          array_pop($operatorStack); // Remove the left paren
          if (!empty($operatorStack) && end($operatorStack)['type'] === 'function') {
            $output[] = array_pop($operatorStack);
          }
          break;
      }
    }

    while (!empty($operatorStack)) {
      $output[] = array_pop($operatorStack);
    }

    return $output;
  }

  /**
   * Determine if operator should be popped based on precedence and associativity
   */
  private function shouldPopOperator(string $current, string $top): bool
  {
    $currentOp = self::OPERATORS[$current];
    $topOp = self::OPERATORS[$top];

    if ($currentOp['associativity'] === 'left') {
      return $currentOp['precedence'] <= $topOp['precedence'];
    }
    return $currentOp['precedence'] < $topOp['precedence'];
  }

  /**
   * Evaluate RPN expression
   */
  private function evaluateRPN(array $rpn): float
  {
    $stack = [];

    foreach ($rpn as $token) {
      switch ($token['type']) {
        case 'number':
          $stack[] = $token['value'];
          break;

        case 'operator':
          if (count($stack) < 2) {
            throw new Exception('Invalid formula: not enough operands');
          }
          $b = array_pop($stack);
          $a = array_pop($stack);
          $stack[] = $this->applyOperator($token['value'], $a, $b);
          break;

        case 'comparison':
          if (count($stack) < 2) {
            throw new Exception('Invalid formula: not enough operands for comparison');
          }
          $b = array_pop($stack);
          $a = array_pop($stack);
          $stack[] = $this->applyComparison($token['value'], $a, $b) ? 1 : 0;
          break;

        case 'function':
          $argCount = self::FUNCTIONS[$token['value']];
          if (count($stack) < $argCount) {
            throw new Exception("Invalid formula: not enough arguments for function {$token['value']}");
          }
          $args = [];
          for ($i = 0; $i < $argCount; $i++) {
            array_unshift($args, array_pop($stack));
          }
          $stack[] = $this->applyFunction($token['value'], $args);
          break;
      }
    }

    if (count($stack) !== 1) {
      throw new Exception('Invalid formula: expression did not evaluate to a single value');
    }

    return $stack[0];
  }

  /**
   * Apply arithmetic operator
   */
  private function applyOperator(string $operator, float $a, float $b): float
  {
    switch ($operator) {
      case '+':
        return $a + $b;
      case '-':
        return $a - $b;
      case '*':
        return $a * $b;
      case '/':
        if ($b == 0) {
          return 0; // Avoid division by zero, return 0
        }
        return $a / $b;
      case '%':
        if ($b == 0) {
          return 0;
        }
        return fmod($a, $b);
      case '^':
        return pow($a, $b);
      default:
        throw new Exception("Unknown operator: {$operator}");
    }
  }

  /**
   * Apply comparison operator
   */
  private function applyComparison(string $operator, float $a, float $b): bool
  {
    switch ($operator) {
      case '>':
        return $a > $b;
      case '<':
        return $a < $b;
      case '>=':
        return $a >= $b;
      case '<=':
        return $a <= $b;
      case '==':
        return abs($a - $b) < 0.0001; // Float comparison
      case '!=':
        return abs($a - $b) >= 0.0001;
      default:
        throw new Exception("Unknown comparison operator: {$operator}");
    }
  }

  /**
   * Apply function to arguments
   */
  private function applyFunction(string $function, array $args): float
  {
    switch ($function) {
      case 'ROUND':
        return round($args[0], (int) $args[1]);
      case 'FLOOR':
        return floor($args[0]);
      case 'CEIL':
        return ceil($args[0]);
      case 'ABS':
        return abs($args[0]);
      case 'MIN':
        return min($args[0], $args[1]);
      case 'MAX':
        return max($args[0], $args[1]);
      case 'IF':
        // IF(condition, value_if_true, value_if_false)
        return $args[0] ? $args[1] : $args[2];
      default:
        throw new Exception("Unknown function: {$function}");
    }
  }

  /**
   * Test if a formula is valid
   *
   * @param string $formula The formula to test
   * @param array $testVariables Test variables to use
   * @return array ['valid' => bool, 'result' => float|null, 'error' => string|null]
   */
  public function testFormula(string $formula, array $testVariables = []): array
  {
    try {
      $result = $this->evaluate($formula, $testVariables);
      return [
        'valid' => true,
        'result' => $result,
        'error' => null,
      ];
    } catch (Exception $e) {
      return [
        'valid' => false,
        'result' => null,
        'error' => $e->getMessage(),
      ];
    }
  }

  /**
   * Extract variable names from a formula
   *
   * @param string $formula The formula to analyze
   * @return array List of variable names found in the formula
   */
  public function extractVariables(string $formula): array
  {
    $variables = [];

    // Match uppercase word patterns that are not functions
    preg_match_all('/\b([A-Z][A-Z0-9_]*)\b/', $formula, $matches);

    foreach ($matches[1] as $match) {
      if (!array_key_exists($match, self::FUNCTIONS) && !in_array($match, $variables)) {
        $variables[] = $match;
      }
    }

    return $variables;
  }
}
