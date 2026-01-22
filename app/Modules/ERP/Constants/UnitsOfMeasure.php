<?php

namespace App\Modules\ERP\Constants;

/**
 * Common Units of Measure
 * Comprehensive list of units used in ERP systems
 */
class UnitsOfMeasure
{
    /**
     * Get all available units of measure grouped by category
     *
     * @return array<string, array<string, string>>
     */
    public static function all(): array
    {
        return [
            'Weight' => [
                'kg' => 'Kilogram (kg)',
                'g' => 'Gram (g)',
                'mg' => 'Milligram (mg)',
                'lb' => 'Pound (lb)',
                'oz' => 'Ounce (oz)',
                'ton' => 'Ton (ton)',
                'metric_ton' => 'Metric Ton (t)',
            ],
            'Length' => [
                'm' => 'Meter (m)',
                'cm' => 'Centimeter (cm)',
                'mm' => 'Millimeter (mm)',
                'km' => 'Kilometer (km)',
                'inch' => 'Inch (in)',
                'ft' => 'Foot (ft)',
                'yard' => 'Yard (yd)',
                'mile' => 'Mile (mi)',
            ],
            'Volume' => [
                'liter' => 'Liter (L)',
                'ml' => 'Milliliter (mL)',
                'gallon' => 'Gallon (gal)',
                'quart' => 'Quart (qt)',
                'pint' => 'Pint (pt)',
                'cup' => 'Cup (cup)',
                'fl_oz' => 'Fluid Ounce (fl oz)',
                'm3' => 'Cubic Meter (m³)',
                'cm3' => 'Cubic Centimeter (cm³)',
            ],
            'Area' => [
                'm2' => 'Square Meter (m²)',
                'cm2' => 'Square Centimeter (cm²)',
                'km2' => 'Square Kilometer (km²)',
                'sq_ft' => 'Square Foot (ft²)',
                'sq_inch' => 'Square Inch (in²)',
                'acre' => 'Acre (acre)',
                'hectare' => 'Hectare (ha)',
            ],
            'Quantity' => [
                'piece' => 'Piece (pc)',
                'box' => 'Box (box)',
                'pack' => 'Pack (pack)',
                'carton' => 'Carton (carton)',
                'pallet' => 'Pallet (pallet)',
                'set' => 'Set (set)',
                'pair' => 'Pair (pair)',
                'dozen' => 'Dozen (dozen)',
                'unit' => 'Unit (unit)',
                'case' => 'Case (case)',
                'bundle' => 'Bundle (bundle)',
            ],
            'Time' => [
                'hour' => 'Hour (hr)',
                'minute' => 'Minute (min)',
                'day' => 'Day (day)',
                'week' => 'Week (week)',
                'month' => 'Month (month)',
                'year' => 'Year (year)',
            ],
        ];
    }

    /**
     * Get all units as a flat array [code => label]
     *
     * @return array<string, string>
     */
    public static function flat(): array
    {
        $all = self::all();
        $flat = [];
        
        foreach ($all as $category => $units) {
            foreach ($units as $code => $label) {
                $flat[$code] = $label;
            }
        }
        
        return $flat;
    }

    /**
     * Get all valid unit codes
     *
     * @return array<string>
     */
    public static function codes(): array
    {
        return array_keys(self::flat());
    }

    /**
     * Check if a unit code is valid
     *
     * @param string $code
     * @return bool
     */
    public static function isValid(string $code): bool
    {
        return in_array($code, self::codes());
    }

    /**
     * Get label for a unit code
     *
     * @param string $code
     * @return string|null
     */
    public static function getLabel(string $code): ?string
    {
        return self::flat()[$code] ?? null;
    }
}












