<?php

namespace TorrentPier\Twig\Extension;

use TorrentPier\Twig\Node\Expression\Binary\Not;
use Twig_ExpressionParser;

class CoreTorrentPier extends \Twig_Extension
{
    public function getFunctions()
    {
        return [
            new \Twig_Function('lang', [$this, 'lang']),
            new \Twig_Function('html_insert', [$this, 'htmlInsert'], ['is_safe' => ['html']]),
        ];
    }

    public function lang($name)
    {
        global $lang;

        return $lang[$name];
    }

    public function htmlInsert($path)
    {
        return file_get_contents($path);
    }

    public function getOperators()
    {
        return [
            [],
            [
                'eq' => [
                    'precedence' => 20,
                    'class' => 'Twig_Node_Expression_Binary_Equal',
                    'associativity' => Twig_ExpressionParser::OPERATOR_LEFT
                ],
                'ne' => [
                    'precedence' => 20,
                    'class' => 'Twig_Node_Expression_Binary_NotEqual',
                    'associativity' => Twig_ExpressionParser::OPERATOR_LEFT
                ],
                'neq' => [
                    'precedence' => 20,
                    'class' => 'Twig_Node_Expression_Binary_NotEqual',
                    'associativity' => Twig_ExpressionParser::OPERATOR_LEFT
                ],
                'lt' => [
                    'precedence' => 20,
                    'class' => 'Twig_Node_Expression_Binary_Less',
                    'associativity' => Twig_ExpressionParser::OPERATOR_LEFT
                ],
                'le' => [
                    'precedence' => 20,
                    'class' => 'Twig_Node_Expression_Binary_LessEqual',
                    'associativity' => Twig_ExpressionParser::OPERATOR_LEFT
                ],
                'lte' => [
                    'precedence' => 20,
                    'class' => 'Twig_Node_Expression_Binary_LessEqual',
                    'associativity' => Twig_ExpressionParser::OPERATOR_LEFT
                ],
                'gt' => [
                    'precedence' => 20,
                    'class' => 'Twig_Node_Expression_Binary_Greater',
                    'associativity' => Twig_ExpressionParser::OPERATOR_LEFT
                ],
                'ge' => [
                    'precedence' => 20,
                    'class' => 'Twig_Node_Expression_Binary_GreaterEqual',
                    'associativity' => Twig_ExpressionParser::OPERATOR_LEFT
                ],
                'gte' => [
                    'precedence' => 20,
                    'class' => 'Twig_Node_Expression_Binary_GreaterEqual',
                    'associativity' => Twig_ExpressionParser::OPERATOR_LEFT
                ],
                '&&' => [
                    'precedence' => 15,
                     'class' => 'Twig_Node_Expression_Binary_And',
                    'associativity' => Twig_ExpressionParser::OPERATOR_LEFT
                ],
                '||' => [
                    'precedence' => 10,
                    'class' => 'Twig_Node_Expression_Binary_Or',
                    'associativity' => Twig_ExpressionParser::OPERATOR_LEFT
                ],
                'mod' => [
                    'precedence' => 60,
                    'class' => 'Twig_Node_Expression_Binary_Mod',
                    'associativity' => Twig_ExpressionParser::OPERATOR_LEFT
                ],
                '!' => [
                    'precedence' => 20,
                    'class' => Not::class,// 'Twig_Node_Expression_Binary_Not',
                    'associativity' => Twig_ExpressionParser::OPERATOR_RIGHT
                ],
            ],
        ];
    }
}
