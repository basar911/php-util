<?php

namespace basar911\phpUtil;

// 逆波兰运算
use SplStack;

class RPN
{
    //将中缀表达式转换成后缀表达式
    public function toRPNExpression($express)
    {
        $str_center = $this->getItem($express);
        $stack      = new SplStack();
        $arr        = explode(" ", $str_center);
        $res        = [];

        for ($i = 0; $i < count($arr); $i++) {
            if (is_numeric($arr[$i])) {
                //如果是个数字，直接放入数组，输出
                $res[] = $arr[$i];
            } else {
                if ($arr[$i] == "(") {
                    //如果是左括号，则入栈
                    $stack->push($arr[$i]);
                } else if ($arr[$i] == ")") {
                    //如果是右括号，则弹出堆栈中最上的"("之前的所有运算符并输出，然后删除堆栈中的"("
                    while (true) {
                        $s = $stack->pop();
                        if ($s == "(") {
                            break;
                        } else {
                            $res[] = $s;
                        }
                    }
                } else if ($arr[$i] == "+" || $arr[$i] == "-") {
                    //当当前字符为运算符时，则依次弹出堆栈中优先级大于等于当前运算符的(到"("之前为止)，输出，再将当前运算符压栈
                    while ($stack->count() > 0) {
                        $s = $stack->top();

                        if ($s == "(") {
                            break;
                        } else {
                            $s      = $stack->pop();
                            $res[] = $s;
                        }
                    }

                    $stack->push($arr[$i]);
                } else if ($arr[$i] == "*" || $arr[$i] == "/") {

                    //当当前字符为运算符时，则依次弹出堆栈中优先级大于等于当前运算符的(到"("之前为止)，输出，再将当前运算符压栈
                    while ($stack->count() > 0) {
                        $s = $stack->top();

                        if ($s == "(") {
                            break;
                        } else if ($s == "+" || $s == "-") {  // + - 优先级低于 * / ,跳出while
                            break;
                        } else {
                            $s     = $stack->pop();
                            $res[] = $s;
                        }
                    }

                    //将当前运算符压入栈
                    $stack->push($arr[$i]);
                }

            }
        }

        //最后弹出栈中的全部内容
        while ($stack->count() > 0) {
            $res[] = $stack->pop();
        }

        return implode(' ', $res);
    }

    //将数字项和符号项用空格分隔开
    protected function getItem($str)
    {
        $arr       = [];
        $num       = '';
        $str   = preg_replace("/(\s|　)+/", '', $str);  // 去除空格
        $lenth = strlen($str);

        for ($i = 0; $i < $lenth; $i++) {

            if($str[$i] == '-'){  // 处理负数
                if(
                    (
                        $i == 0
                        || in_array($str[ $i - 1 ], ['+', '-', '*', '/', '('])
                    )
                    && is_numeric($str[ $i + 1 ])
                ){  // 是负数标识
                    $num = $str[$i] . $str[ $i + 1 ];
                    $i++; // 跳过一次循环
                    continue;
                }
            }

            //如果是个数字，并且不为最后一项
            if (is_numeric($str[$i]) || $str[$i] == '.') {
                $num .= $str[$i];

                if ($i == $lenth - 1) {//最后一位是数字直接放入数组中
                    $arr[] = $num;
                    $num   = '';
                }
            } else {
                //如果不是个数字，并且num不为空，则放入数组中
                if (' ' !== $num) {
                    $arr[] = $num;
                    $num   = '';
                }

                //如果是符号，或者最后一项，直接放入数组中
                $arr[] = $str[$i];
            }
        }

        return implode(" ", $arr);
    }

    //计算后缀式
    public function calculate($express, $scale = 10)
    {
        $arr   = explode(' ', $express);
        $stack = new SplStack();

        for ($i = 0; $i < count($arr); $i++) {
            if (is_numeric($arr[$i])) {
                //如果是数字，直接压入栈中
                $stack->push($arr[$i]);
            } else {
                //如果是符号，则弹出栈顶的两个数，进行运算，然后将运算结果压入栈中
                $n2 = $stack->pop();
                $n1 = $stack->pop();
                $stack->push($this->calcdata($n1, $n2, $arr[$i], $scale));
            }
        }

        return $stack->pop() * 1;//弹出最后结果
    }

    protected function calcdata($data1, $data2, $opeator, $scale = 10){
        switch ($opeator) {
            case '+':
                $calcResult = bcadd($data1, $data2, $scale);
                break;
            case '-':
                $calcResult = bcsub($data1, $data2, $scale);
                break;
            case '*':
                $calcResult = bcmul($data1, $data2, $scale);
                break;
            case '/':
                $calcResult = bcdiv($data1, $data2, $scale);
                break;
            default:
                throw new \Exception("运算符异常无法计算！");
        }

        return $calcResult;
    }

}