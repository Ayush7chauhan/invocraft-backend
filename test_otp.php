<?php
$validator1 = Illuminate\Support\Facades\Validator::make(
    ['mobile_number' => 9876543210], 
    ['mobile_number' => 'required|digits:10']
);
$validator2 = Illuminate\Support\Facades\Validator::make(
    ['mobile_number' => '9876543210'], 
    ['mobile_number' => 'required|digits:10']
);
echo "Int passes: " . ($validator1->passes() ? 'Yes' : 'No') . "\n";
echo "String passes: " . ($validator2->passes() ? 'Yes' : 'No') . "\n";
