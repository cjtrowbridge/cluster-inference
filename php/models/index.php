<?php

$modelDirectory    = '/var/ai/models';

function list_models($modelDirectory) {
    // Perform the necessary logic to get the list of available models
    //The models are all binary files in the models directory
    $list_of_models = array();
    $models = scandir($modelDirectory);
    foreach ($models as $model) {
        if ($model != "." && $model != "..") {
            //Check if the model is a binary file
            if(right($model,4)!=".bin") {
                continue;
            }
            $list_of_models[] = $model;
        }
    }
    
    // Respond with the list of models in JSON format
    echo json_encode($list_of_models);
}

list_models($modelDirectory);
