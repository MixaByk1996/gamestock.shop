<?php
// /includes/price_calculator.php

class PriceCalculator {
    
    /**
     * Рассчитывает конечную цену с наценкой
     */
    public static function calculatePrice($base_price, $markup_type, $markup_value) {
        $base_price = (float) $base_price;
        $markup_value = (float) $markup_value;
        
        if ($markup_type === 'percent') {
            // Формула: цена × (1 + наценка/100)
            // Пример: 100 × (1 + 150/100) = 100 × 2.5 = 250
            $multiplier = 1 + ($markup_value / 100);
            $final_price = $base_price * $multiplier;
            $markup_amount = $final_price - $base_price;
        } else {
            // Фиксированная наценка
            $markup_amount = $markup_value;
            $final_price = $base_price + $markup_value;
        }
        
        return [
            'final_price' => round($final_price, 2),
            'markup_amount' => round($markup_amount, 2),
            'base_price' => $base_price,
            'markup_percent' => ($markup_type === 'percent') ? $markup_value : null,
            'markup_fixed' => ($markup_type === 'fixed') ? $markup_value : null,
            'multiplier' => isset($multiplier) ? $multiplier : null
        ];
    }
    
    /**
     * Форматирует цену для отображения
     */
    public static function formatPrice($price, $currency = '₽') {
        return number_format($price, 2, '.', ' ') . ' ' . $currency;
    }
    
    /**
     * Тестирование расчетов
     */
    public static function test() {
        $tests = [
            ['base' => 100, 'type' => 'percent', 'value' => 150, 'expected' => 250],
            ['base' => 100, 'type' => 'percent', 'value' => 200, 'expected' => 300],
            ['base' => 100, 'type' => 'percent', 'value' => 50, 'expected' => 150],
            ['base' => 100, 'type' => 'fixed', 'value' => 50, 'expected' => 150],
        ];
        
        $results = [];
        foreach ($tests as $test) {
            $result = self::calculatePrice($test['base'], $test['type'], $test['value']);
            $results[] = [
                'test' => $test,
                'result' => $result,
                'passed' => ($result['final_price'] == $test['expected'])
            ];
        }
        
        return $results;
    }
}
?>