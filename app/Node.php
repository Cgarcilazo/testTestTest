<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Node extends Model
{
    public $fillable = [
        'isLeaf',
        'value',
        'class',
        'leftPartition',
        'rightPartition',
        'confidence',
        'attributeName'
    ];

    public function makeLeaf($class, $confidence)
    {
        $this->isLeaf = true;
        $this->confidence = $confidence;
        $this->class = $class;
        return $this;
    }
}
