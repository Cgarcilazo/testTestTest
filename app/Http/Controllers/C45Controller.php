<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Node;

class C45Controller extends Controller
{

    public $leftPartitionQuantity = 0;
    public $rightPartitionQuantity = 0;
    public $useGainRatio = false;

    public function executeC45(Request $request)
    {
        $dataTest = [
            [
                'x' => 2,
                'y' => 7,
                'class' => 'cross'
            ],
            [
                'x' => 2,
                'y' => 10,
                'class' => 'circle'
            ],
            [
                'x' => 4,
                'y' => 10,
                'class' => 'cross'
            ],
            [
                'x' => 4,
                'y' => 7,
                'class' => 'cross'
            ]
        ];

        if (empty($request->file('file'))) {
            return response()->json([
                'message' => 'Debe ingresar un archivo de texto'
            ], 400);
        }

        if (!empty($request->selected) && ($request->selected == 'gainRatio')) {
            $this->useGainRatio = true;
        }

        $request->file('file');
        $path = $request->file('file')->store('files');

        $file = fopen(storage_path('app/'.$path), 'r');

        $dataSet = [];

        for ($i=1; $i < 200; $i++) { 
                $line = fgets($file);
            $explodedLine = explode(';', $line);
    
            $myData = [
                'x' => $explodedLine[0],
                'y' => $explodedLine[1],
                'class' => $explodedLine[2]
            ];
            array_push($dataSet, $myData);
            
        }
        fclose($file);

        if (empty($request->threshold)) {
            $threshold = 0.2;
        } else {
            $threshold = $request->threshold;
        }

        return response()->json([
            'message' => 'Árbol creado correctamente',
            'results' => $this->C45($dataSet, $threshold)]);
    }

    public function C45($data, $threshold)
    {
        if ($this->allEqualClasses($data)) {
            $node = new Node();
            $confidence = $confidence = count($data) . "/" . count($data);
            return $node->makeLeaf($data[0]['class'], $confidence);

        }

        $bestAttribute = $this->getBestAttribute($data);

        if ($bestAttribute['gain'] <= 0.02) {
            $node = new Node();
            $mostFrecuentClass =  $this->getMostFrecuentClass($data);

            return $node->makeLeaf($mostFrecuentClass['class'], $mostFrecuentClass['confidence']);
        }

        $rootNode = new Node();
        $rootNode->isLeaf = false;
        $rootNode->value = $bestAttribute['value'];
        $rootNode->class = $bestAttribute['class'];
        $rootNode->attributeName = $bestAttribute['name'];

        $leftPartition = [];
        $rightPartition = [];

        foreach ($data as $element) {
            if ($element[$bestAttribute['name']] <= $bestAttribute['value']) {
                array_push($leftPartition, $element);
            } else {
                array_push($rightPartition, $element);
            }
        }

        if (count($leftPartition) > 0) {
            $rootNode->leftPartition = $this->C45($leftPartition, $threshold);
        }

        if (count($rightPartition) > 0) {
            $rootNode->rightPartition = $this->C45($rightPartition, $threshold);
        }

        return $rootNode;
    }

    public function getMostFrecuentClass($data)
    {
        $countClass1 = 0;
        $countClass2 = 0;

        $class1 = $data[0]['class'];

        foreach ($data as $element) {
            if ($element['class'] == $class1) {
                $countClass1 += 1;
            } else {
                $countClass2 += 1;
                $class2 = $element['class'];
            }
        }

        if ($countClass1 >= $countClass2) {
            return [
                'class' => $class1,
                'confidence' => $countClass1  . '/' . count($data)
            ];
        }
        return [
            'class' => $class2,
            'confidence' => $countClass2 . '/' . count($data)
        ];
    }

    public function allEqualClasses($data)
    {
        $class = $data[0]['class'];
        foreach ($data as $element) {
            if ($element['class'] !== $class) {
                return false;
            }
        }
        return true;
    }

    public function getBestAttribute($data, $attributes = ['x', 'y'])
    {
        $betterGain = 0;
        $attributeBestGain = null;
        $bestAttribute = '';

        foreach ($attributes as $attribute) {
            $attributeGain = $this->calculateGain($data, $attribute);

            if ($attributeGain['maxGain'] > $betterGain) {
                $betterGain = $attributeGain['maxGain'];
                $attributeBestGain = $attributeGain;
                $bestAttribute = $attribute;
            }
        }

        return [
            'name' => $bestAttribute,
            'gain' => $attributeBestGain['maxGain'],
            'value' => $attributeBestGain['valueToSplit'],
            'class' => $attributeBestGain['class']
        ];
    }

    public function makeLeaf($data)
    {
        $confidence = count($data) . "/" . count($data);
        $node = new Node();
        $node->makeLeaf($data, $confidence, $data[0]);
        return $node;
    }

    public function calculateEntropy($data)
    {
        $totalQuantity = count($data);

        if ($totalQuantity > 0) {

            $countClass1 = 0;
            $firstClass = $data[0]['class'];
            foreach ($data as $element) {
                if ($element['class'] == $firstClass) {
                    $countClass1 += 1; 
                }
            }

            $countClass2 = $totalQuantity - $countClass1;
            $prClass1 = $countClass1 / $totalQuantity;
            $prClass2 = $countClass2 / $totalQuantity;
            $entropy = ($prClass1 * $this->calculateLogBase2($prClass1)) + ($prClass2 * $this->calculateLogBase2($prClass2));

            return - $entropy;
        }

        return 0;
    }

    public function calculateAtributeEntropy($data, $attribute, $value)
    {
        $totalQuantity = count($data);

        if ($totalQuantity > 0) {
            $leftPartition = [];
            $rightPartition = [];

            foreach ($data as $element) {
                if ($element[$attribute] <= $value) {
                    array_push($leftPartition, $element);
                } else {
                    array_push($rightPartition, $element);
                }
            }

            $prLeft = count($leftPartition) / $totalQuantity;
            $prRight = count($rightPartition) / $totalQuantity;
            $this->leftPartitionQuantity = $prLeft;
            $this->rightPartitionQuantity = $prRight;

            return ($prLeft * $this->calculateEntropy($leftPartition)) + ($prRight * $this->calculateEntropy($rightPartition));

        }

        return 0;
    }

    public function calculateGain($data, $attribute)
    {
        $totalQuantity = count($data);

        $maxGain = -1;
        $valueToSplit = 0;
        if ($totalQuantity > 0) {
            foreach ($data as $element) {
                $gain = $this->calculateEntropy($data) - $this->calculateAtributeEntropy($data, $attribute, $element[$attribute]);

                if ($this->useGainRatio) {
                    $divisor = $this->leftPartitionQuantity * $this->calculateLogBase2($this->leftPartitionQuantity) + 
                        $this->rightPartitionQuantity * $this->calculateLogBase2($this->rightPartitionQuantity);
                        if ($divisor != 0) {
                            $gain = - ($gain / $divisor);
                        }
                }
                
                if ($gain > $maxGain) {
                    $maxGain = $gain;
                    $valueToSplit = $element[$attribute];
                    $class = $element['class'];
                }
            }
            return [
                'maxGain' => $maxGain,
                'valueToSplit' => $valueToSplit,
                'class' => $class
            ];
        }
        return 0;
    }

    public function calculateLogBase2($prClass)
    {
        if ($prClass === 0) {
            return 0;
        }

        return (log($prClass) / log(2));
    }
}
