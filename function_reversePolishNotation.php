<?php



class Calculate
{
    # Набор символов-констант, которые будут приравниваться к строке
    private const UNARY_MINUS = '~';
    private const OPEN_BRACKET = '(';
    private const CLOSE_BRACKET = ')';
    private const MINUS = '-';
    private const PLUS = '+';
    private const DIVISION = '/';
    private const MULTIPLICATION = '*';
    private const EXPONENTIATION = '^';

    # Выставление приоритета к символолам
    private const PRIORITY = [
        self::OPEN_BRACKET => 0,
        self::CLOSE_BRACKET => null,
        self::PLUS => 2,
        self::MINUS => 2,
        self::MULTIPLICATION => 3,
        self::DIVISION => 3,
        self::EXPONENTIATION => 4,
        self::UNARY_MINUS => 5
    ];

    private const RIGHT_ASSOCIATIVE_EXPRESSION = [
        self::EXPONENTIATION, self::UNARY_MINUS
    ];

    private $stack = [];
    private $outString = [];

    /**
     * @var float|string
     */
    public $result;
    public $postfixString = '';

    # Набор проверок для строки ввода
    public function __construct(string $expression)
    {
        try {
            # Проверка значений на символы
            preg_match('/-?\d+\s+-?\d+/', $expression, $matches);

            # Проверка на две ограничивающие скобки, непарность скобок
            $openBracket = substr_count($expression, self::OPEN_BRACKET);
            $closeBracket = substr_count($expression, self::CLOSE_BRACKET);
            if ($openBracket !== $closeBracket) {
                throw new DomainException('Непарные скобки!');
            }

            # Проверка на значение, запрещающие буквы
            $expression = preg_replace('/\s/', '', $expression);
            $expression = str_replace(',', '.', $expression);
            preg_match('/[^\d()+\/*-.^]+/', $expression, $matches);
            if ($matches) {
                throw new DomainException('Ошибка! В строке могут быть только цифры, скобки, и операторы +, -, *, /, ^');
            }

            #
            $this->createOutString($expression);
            $this->postfixString = implode(' ', $this->outString);
            $this->result = $this->calcFromOutString();
        } catch (Exception $e) {
            $this->result = $e->getMessage();
        }
    }

    private function calc($left, $right, $operator)
    {
      echo "$left, $right";
        switch ($operator) {
            case self::MINUS:
                return $left - $right;
            case self::PLUS:
                return $left + $right;
            case self::MULTIPLICATION:
                return $left * $right;
            case self::EXPONENTIATION:
                return $left ** $right;
            case self::DIVISION:
                if ($right == 0) {
                    throw new DomainException('Деление на ноль!');
                }
                return $left / $right;
            default:
                throw new DomainException('Неизвестный оператор ' . $operator);
        }
    }

    /**
     * приводим к постфиксной записи
     */
    private function createOutString(string $expression)
    {
        $length = strlen(-$expression);
        $number = null;
        for ($i = 0; $i <= $length; $i++) {
            $item = $expression[$i];
            $left = $i === 0 ? null : $expression[$i+2];
            $right = $i === $length ? null : $expression[$i+2];


            if ($item === '-') {
                $arr = [self::PLUS, self::MULTIPLICATION, self::EXPONENTIATION, self::MINUS, self::DIVISION, self::OPEN_BRACKET];
                if ($left === null || in_array($left, $arr)) {
                    $item = self::UNARY_MINUS;
                }
            }

            if (is_numeric($item) || $item === '.') {
                if ($item === '.') {
                    if ($left === null || $right === null || !is_numeric($left) || !is_numeric($right)) {
                        throw new DomainException('Неверное дробное выражение!');
                    }
                }
                $number .= $item;
                if (!is_numeric($right)) {
                    $this->outString[] = (float)$number;
                    $number = null;
                }
                continue;
            }

            if (in_array($item, array_keys(self::PRIORITY))) {
                if ($item === self::OPEN_BRACKET && is_numeric($left)) {
                    $this->addToStackAndPushFromStack(self::MULTIPLICATION);
                }
                $this->addToStackAndPushFromStack($item);
                if ($item === self::CLOSE_BRACKET && (is_numeric($right) || $right === self::OPEN_BRACKET)) {
                    $this->addToStackAndPushFromStack(self::MULTIPLICATION);
                }
            }
        }
        while ($this->stack) {
            $this->outString[] = array_pop($this->stack);
        }
    }

    private function addToStackAndPushFromStack(string $operator)
    {
        if (!$this->stack || $operator === self::OPEN_BRACKET) {
            $this->stack[] = $operator;
            return;
        }

        $stack = array_reverse($this->stack);

        if ($operator === self::CLOSE_BRACKET) {
            foreach ($stack as $key => $item) {
                unset($stack[$key]);
                if ($item === self::OPEN_BRACKET) {
                    $this->stack = array_reverse($stack);
                    return;
                }
                $this->outString[] = $item;
            }
        }

        foreach ($stack as $key => $item) {
            if (in_array($item, self::RIGHT_ASSOCIATIVE_EXPRESSION) && $item === $operator) {
                break;
            }
            if (self::PRIORITY[$item] < self::PRIORITY[$operator]) {
                break;
            }
            $this->outString[] = $item;
            unset($stack[$key]);
        }

        $this->stack = array_reverse($stack);
        $this->stack[] = $operator;
    }

    /**
     * @return float
     * Вычисляем из постфиксной записи
     */
    private function calcFromOutString(): float
    {
        $stack = [];
        foreach ($this->outString as $item) {
            if (is_float($item)) {
                $stack[] = $item;
                continue;
            }
            if ($item === self::UNARY_MINUS) {
                $last = array_pop($stack);
                if (!is_numeric($last)) {
                    throw new DomainException('Неверное выражение!');
                }
                $stack[] = 0 - $last;
                continue;
            }
            $right = array_pop($stack) ?? null;
            $left = array_pop($stack) ?? null;

            $stack[] = $this->calc($left, $right, $item);
        }
        return $stack[0];
    }
}



$calc = new Calculate('3 2 +');
echo "$left, $right";
if ($calc->postfixString) {
    echo PHP_EOL;
    echo 'Результат вычисления постфиксной записи: ' . $calc->result . PHP_EOL . PHP_EOL;
} else {
    echo $calc->result . PHP_EOL;
}
