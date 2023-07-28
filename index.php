<?php
// Include any necessary libraries or dependencies
// ...

$modelDirectory    = '/var/ai/models';
$ggmlDirectory     = '/var/ai/ggml';
$openLLMDirectrory = '/var/ai/openllm';

checkDatabase($modelDirectory, $ggmlDirectory, $openLLMDirectrory);

// Call the main function to handle the incoming request
handle_request($modelDirectory, $ggmlDirectory, $openLLMDirectrory);




// Function to handle incoming HTTP requests
function handle_request($modelDirectory, $ggmlDirectory, $openLLMDirectrory) {
    //First check if the server request method and request uri variables are set
    if(!(isset($_SERVER['REQUEST_METHOD'])) || !(isset($_SERVER['REQUEST_URI']))){
        http_response_code(400); // Bad Request
        echo json_encode(array("error" => "Invalid request"));
        return;
    }
    
    // Get the HTTP method (GET, POST, etc.)
    $method = $_SERVER['REQUEST_METHOD'];

    // Get the request URI (e.g., /models, /chat/completions, etc.)
    $request_uri = $_SERVER['REQUEST_URI'];

    // Parse the request URI to extract the endpoint and any additional parameters
    $uri_parts = explode('/', $request_uri);
    $endpoint = $uri_parts[1];
    $model_id = null;
    if (isset($uri_parts[2])) {
        $model_id = $uri_parts[2];
    }

    // Route the request to the appropriate handler based on the endpoint and HTTP method
    switch ($method) {
        case 'GET':
            handle_get_request($modelDirectory, $ggmlDirectory, $openLLMDirectrory, $endpoint, $model_id);
            break;
        case 'POST':
            handle_post_request($modelDirectory, $ggmlDirectory, $openLLMDirectrory, $endpoint);
            break;
        default:
            http_response_code(405); // Method Not Allowed
            echo json_encode(array("error" => "Method not allowed"));
            break;
    }
}


// Function to check if a model name is valid
function isValidModel($modelDirectory, $ggmlDirectory, $openLLMDirectrory, $modelName){
    //First check if it's a real model
    $found = false;
    $list_of_models = array();
    $models = scandir($modelDirectory);
    foreach ($models as $model) {
        if ($model != "." && $model != "..") {
            //Check if the model is a binary file
            if(substr($model,-4)==".bin") {
                $model = substr($model,0,(strlen($model)-4));
                //Check if the model is the one we're looking for
                if($model == $modelName){
                    $found = true;
                    break;
                }
            }
        }
    }
    return $found;
}
// Function to handle GET requests
function handle_get_request($modelDirectory, $ggmlDirectory, $openLLMDirectrory, $endpoint, $model_id) {
    // Check the endpoint and handle the corresponding request
    if ($endpoint === 'models' && !empty($model_id)) {
        
        $Valid = isValidModel($modelDirectory, $ggmlDirectory, $openLLMDirectrory, $model_id);
        
        if($Valid){
            // Handle the "retrieve model" request
            retrieve_model($modelDirectory, $ggmlDirectory, $openLLMDirectrory, $model_id);
        }else{
            //Let them know this is not a supported model and encourage them to add it to the /var/ai directory
            http_response_code(404); // Not Found
            echo json_encode(array("error" => "Model not found. Please add it to the /var/ai directory."));
        }
        
    } elseif ($endpoint === 'models') {
        // Handle the "list models" request
        list_models($modelDirectory, $ggmlDirectory, $openLLMDirectrory);
    } elseif ($endpoint === 'update') {
        // Handle the "update" request
        showUpdate();
    } elseif ($endpoint === 'ggml') {
        // Handle the "update" request
        showGGML($modelDirectory, $ggmlDirectory, $openLLMDirectrory);
    } elseif ($endpoint === '') {
        // Handle the home page
        showHomePage();
    } elseif ($endpoint === 'document-completion') {
        // Handle the document completion page
        showDocumentCompletion();
    } else {
        http_response_code(404); // Not Found
        echo json_encode(array("error" => "Endpoint not found for GET request: $endpoint"));
    }
}


// Function to handle POST requests
function handle_post_request($modelDirectory, $ggmlDirectory, $openLLMDirectrory, $endpoint) {
    // Check the endpoint and handle the corresponding request
    if ($endpoint === 'chat/completions') {
        // Handle the "chat completions" request
        create_chat_completion($modelDirectory, $ggmlDirectory, $openLLMDirectrory);
    } elseif ($endpoint === 'completions') {
        // Handle the "completions" request
        create_completion($modelDirectory, $ggmlDirectory, $openLLMDirectrory);
    } elseif ($endpoint === 'update') {
        // Handle the "update" request
        handleUpdate($modelDirectory, $ggmlDirectory, $openLLMDirectrory);
    } else {
        http_response_code(404); // Not Found
        echo json_encode(array("error" => "Endpoint not found for POST request: $endpoint"));
    }
}

// Function to list available models
function list_models($modelDirectory, $ggmlDirectory, $openLLMDirectrory) {
    // Perform the necessary logic to get the list of available models
    //The models are all binary files in the models directory
    $list_of_models = array();
    $models = scandir($modelDirectory);
    foreach ($models as $model) {
        if ($model != "." && $model != "..") {
            //Check if the model is a binary file
            if(substr($model,-4)!=".bin") {
                continue;
            }
            $model = substr($model,0,(strlen($model)-4));
            $list_of_models[] = $model;
        }
    }

    //List model files in order by size descending
    $modelsbysize = array();
    foreach ($list_of_models as $model) {
        $modelsbysize[$model] = filesize($modelDirectory . "/" . $model . ".bin");
    }
    arsort($modelsbysize);
    $list_of_models = array_keys($modelsbysize);
    
    // Respond with the list of models in JSON format
    echo json_encode($list_of_models);
}

// Function to retrieve information about a specific model
function retrieve_model($modelDirectory, $ggmlDirectory, $openLLMDirectrory, $model_id) {
    // Perform the necessary logic to retrieve information about the specified model
    // These values are stored in an SQLite3 database in the same directory as the models
    $model_info = array();
    $db = new SQLite3($modelDirectory . "/models.db");
    $results = $db->query("SELECT * FROM models WHERE id = '$model_id'");
    while ($row = $results->fetchArray()) {
        $model_info = $row;
    }
    
    //Check if the model was found and add it if it's not
    if (empty($model_info)) {
        //Add the model to the database
        $db->exec("INSERT INTO models (id) VALUES ('$model_id')");
        $results = $db->query("SELECT * FROM models WHERE id = '$model_id'");
        while ($row = $results->fetchArray()) {
            $model_info = $row;
            
        }
    }

    //Make the json file to return
    $model_info = array(
        "id" => $model_info["id"],
        "object" => "model",
        "owned_by" => $model_info["owned_by"],
        "license" => $model_info["license"],
        "release_date" => $model_info["release_date"],
        "documentation_uri" => $model_info["documentation_uri"],
        "quantization_bits" => $model_info["quantization_bits"],
        "quantization_method" => $model_info["quantization_method"],
        "quantization_description" => $model_info["quantization_description"],
        'context_size' => $model_info['context_size'],
        'deafult_prompt' => $model_info['default_prompt'],
        'size_gb' => $model_info['size'],
        'ram_gb' => $model_info['ram'],
        'ggml_build' => $model_info['ggml_build'],
        'metric_avg' => $model_info['metric_avg'],
        'metric_arc' => $model_info['metric_arc'],
        'metric_hellaswag' => $model_info['metric_hellaswag'],
        'metric_mmlu' => $model_info['metric_mmlu'],
        'metric_truthfulqa' => $model_info['metric_truthfulqa'],
        'permission' => array(),
        "note" => "You can add any missing fields to the database on the /completion.html page."
    );

    $db->close();

    // Respond with the model information in JSON format
    echo json_encode($model_info);
}

// Function to handle "chat completions" request
function create_chat_completion($modelDirectory, $ggmlDirectory, $openLLMDirectrory) {
    // Parse the incoming JSON payload from the POST request
    $request_data = json_decode(file_get_contents('php://input'), true);

    // Validate and extract the necessary parameters from the request data
    $model_id = $request_data['model'];
    $messages = $request_data['messages'];
    if(!(isset($request_data['temperature']))){
        $request_data['temperature'] = 1;
    }else{
        $temperature = $request_data['temperature'];
    }

    // Sanitize the messages before passing them to GGML
    foreach ($messages as &$message) {
        $message['content'] = sanitize_prompt($message['content']);
    }

    // Get the path to the GGML model binary file
    $model_file = $modelDirectory . "/" . $model_id;

    // Validate that the model file exists
    $Valid = isValidModel($modelDirectory, $ggmlDirectory, $openLLMDirectrory, $model_id);
    if (!($Valid)) {
        http_response_code(404); // Not Found
        echo json_encode(array("error" => "Model not found during chat completion"));
        return;
    }

    // Prepare the input data for GGML
    $input_data = array(
        "messages" => $messages,
        "temperature" => $temperature
        // Add any other necessary input data here based on the GGML requirements
    );

    // Convert the input data to JSON format
    $input_json = json_encode($input_data);

    // Prepare the command to execute GGML with the model
    $ggml_command = "/path/to/ggml-executable -m $model_file -i '$input_json'";

    // Execute the GGML command and capture the output
    exec($ggml_command, $output, $return_code);

    // Check if the GGML command was successful
    if ($return_code !== 0) {
        http_response_code(500); // Internal Server Error
        echo json_encode(array("error" => "GGML execution failed"));
        return;
    }

    // Parse the GGML output, assuming it's in JSON format
    $chat_completion_result = json_decode(implode("", $output), true);

    // Respond with the chat completion result in JSON format
    echo json_encode($chat_completion_result);
}


// Function to handle "completions" request
function create_completion($modelDirectory, $ggmlDirectory, $openLLMDirectrory) {
    // Parse the incoming JSON payload from the POST request
    $request_data = json_decode(file_get_contents('php://input'), true);

    // Validate and extract the necessary parameters from the request data
    $model_id = $request_data['model'];
    $prompt = $request_data['prompt'];

    //Get the details of the model from the database
    $db = new SQLite3($modelDirectory . "/models.db");
    $results = $db->query("SELECT * FROM models WHERE id = '$model_id'");
    while ($row = $results->fetchArray()) {
        $model_info = $row;
    }
    $db->close();


    if(!(isset($request_data['temperature']))){
        $temperature = 0.8;
    }else{
        $temperature = $request_data['temperature'];
    }

    if(!(isset($request_data['max_tokens']))){
        $tokens = 200;
    }else{
        $tokens = $request_data['max_tokens'];
    }

    //Figure out what binary this model is set to use
    $build = $model_info['ggml_build'];

    //TODO add gqa value for the model from database
    $gqa = 8;

    //TODO add threads value for the machine from database
    $threads = 12;

    $n_ctx = $model_info['context_size'];

    // Sanitize the prompt before passing it to GGML
    $prompt = sanitize_prompt($prompt);

    // Get the path to the GGML model binary file
    $model_file = $modelDirectory . "/" . $model_id.'.bin';

    // Validate that the model file exists
    $Valid = isValidModel($modelDirectory, $ggmlDirectory, $openLLMDirectrory, $model_id);
    if (!($Valid)) {
        http_response_code(404); // Not Found
        echo json_encode(array("error" => "Model ".$model_id." not found during completion"));
        return;
    }

    // Prepare the input data for GGML
    $input_data = array(
        "prompt" => $prompt,
        "temperature" => $temperature
        // Add any other necessary input data here based on the GGML requirements
    );

    // Convert the input data to JSON format
    $input_json = json_encode($input_data);

    // Prepare the command to execute with the selected binary
    if($build == 'llama'){
        $command = '/var/ai/llama.cpp/main -m '.$model_file.' -gqa '.$gqa.' -t '.$threads.' -c '.$n_ctx.' --temp '.$temperature.' -n '.$tokens.' -p "'.$prompt.'"';
    }else{
        $ctxParameter = '';
        if($n_ctx > 2048){
            $ctxParameter = '-c '.$n_ctx.' ';
        }

        $build = str_replace('ggml_', '', $build);

        $command = '/var/ai/ggml/build/bin/'.$build.' -m '.$model_file.' -t '.$threads.' '.$ctxParameter.'-t '.$temperature.' -n '.$tokens.' -p "'.$prompt.'"';
    }

    // Execute the GGML command and capture the output
    $result = shell_exec($command);

    //Return result
    echo $result;
    exit;
}

//Check if the database exists and create a blank database containing all the models if it doesn't
function checkDatabase($modelDirectory, $ggmlDirectory, $openLLMDirectrory){
    if (!file_exists($modelDirectory . "/models.db")) {
        $db = new SQLite3($modelDirectory . "/models.db");
        $db->exec("CREATE TABLE models (id TEXT UNIQUE, owned_by TEXT, license TEXT, release_date TEXT, documentation_uri TEXT, quantization_bits TEXT, quantization_method TEXT, quantization_description TEXT, context_size INTEGER, default_prompt TEXT, size_gb REAL, ram_gb REAL, ggml_build TEXT, metric_avg REAL, metric_arc REAL, metric_hellaswag REAL, metric_mmlu REAL, metric_truthfulqa REAL)");
        $models = scandir($modelDirectory);
        foreach ($models as $model) {
            if ($model != "." && $model != "..") {
                //Check if the model is a binary file
                $fileType = mime_content_type($modelDirectory . "/" . $model);
                if ($fileType != "application/octet-stream") {
                    continue;
                }
                
                //remove anything after the final period, assuming there may be more than one period in the filename.
                $model = preg_replace('/\.[^.]*$/', '', $model);

                $db->exec("INSERT INTO models (id) VALUES ('$model')");
            }
        }
        $db->close();
    }
}

//Update a model to complete all the database fields assuming only the model field is completed
//"CREATE TABLE models (id TEXT, owned_by TEXT, license TEXT, release_date TEXT, documentation_uri TEXT, quantization_bits TEXT, quantization_method TEXT, quantization_description TEXT)"
function updateModel($model_filename, $owned_by, $license, $release_date, $documentation_uri, $quantization_bits, $quantization_method, $quantization_description, $context_size, $default_prompt, $size_gb, $ram_gb, $ggml_build, $metric_avg, $metric_arc, $metric_hellaswag, $metric_mmlu, $metric_truthfulqa){
    $db = new SQLite3($modelDirectory . "/models.db");
    $model_id = preg_replace('/\.[^.]*$/', '', $model_filename);
    $db->exec("UPDATE models SET owned_by = '$owned_by', license = '$license', release_date = '$release_date', documentation_uri = '$documentation_uri', quantization_bits = '$quantization_bits', quantization_method = '$quantization_method', quantization_description = '$quantization_description', context_size = '.$context_size', default_prompt = '$default_prompt', size_gb = '$size_gb', ram_gb = '$ram_gb', ggml_build = '$ggml_build', metric_avg = '$metric_avg', metric_arc = '$metric_arc', metric_hellaswag = '$metric_hellaswag', metric_mmlu = '$metric_mmlu', metric_truthfulqa = '$metric_truthfulqa' WHERE id = '$model_id'");
    $db->close();
}

//Save url to pdf using wkhtmltopdf
function savePDF($url, $filename){
    $command = "wkhtmltopdf $url $filename";
    exec($command, $output, $return_code);
    if ($return_code !== 0) {
        http_response_code(500); // Internal Server Error
        echo json_encode(array("error" => "PDF creation failed"));
        return;
    }
}

//Add a new model from its documentation url, save its documentation as a pdf, and add it to the database
function addModel($url){
    //check if this is a huggingface url in the format https://huggingface.co/TheBloke/MPT-7B-Instruct-GGML
    if(!(preg_match("/https:\/\/huggingface.co\/[a-zA-Z0-9-]+\/[a-zA-Z0-9-]+/", $url))){
      http_response_code(400); // Bad Request
      echo json_encode(array("error" => "Invalid URL"));
      return;
    }

    //Get the model name from the url
    $model_name = preg_replace("/https:\/\/huggingface.co\/[a-zA-Z0-9-]+\//", '', $url);
    //Get the model owner from the url
    $model_owner = preg_replace("/https:\/\/huggingface.co\//", '', $url);
    //Get the model documentation url
    $model_documentation_url = $url;
    //Get the model documentation pdf filename
    $model_documentation_filename = $modelDirectory . "/" . $model_name . ".pdf";
    
    $download_page = $url.'/tree/main';

    /*
    
    //Get the model binary filename
    $model_binary_filename = $modelDirectory . "/" . $model_name . ".bin";
    //Get the model license
    $model_license = "CC-By-SA-3.0";
    //Get the model release date
    $model_release_date = date("Y-m-d");
    //Get the model quantization bits
    $model_quantization_bits = "8";
    //Get the model quantization method
    $model_quantization_method = "8bit";
    //Get the model quantization description
    $model_quantization_description = "8-bit. Almost indistinguishable from float16. Huge resource use and slow. Not recommended for normal use.";

    //Save the model documentation as a pdf
    savePDF($model_documentation_url, $model_documentation_filename);

    //Download the model binary
    $model_binary_url = "https://huggingface.co/" . $model_owner . "/" . $model_name . "/resolve/main/pytorch_model.bin";
    $model_binary = file_get_contents($model_binary_url);
    file_put_contents($model_binary_filename, $model_binary);

    //Add the model to the database
    updateModel($model_name, $model_owner, $model_license, $model_release_date, $model_documentation_url, $model_quantization_bits, $model_quantization_method, $model_quantization_description);

    //Save the url as a pdf in the docs folder
    */
}

// Function to sanitize the prompt before passing it to GGML
function sanitize_prompt($prompt) {
    // Remove any leading or trailing whitespace
    $prompt = trim($prompt);

    // Remove any potentially harmful characters or scripts
    // For example, you can use regular expressions or other methods to sanitize the prompt


    // Add any other necessary prompt sanitization logic here

    return $prompt;
}

function handleUpdate($modelDirectory, $ggmlDirectory, $openLLMDirectrory){
    /*
        Example submission from the form:
        $_POST = array(20) { ["id"]=> string(31) "falcon-40b-instruct.ggccv1.q8_0" ["object"]=> string(5) "model" ["owned_by"]=> string(19) "https://www.tii.ae/" ["license"]=> string(10) "Apache 2.0" ["release_date"]=> string(0) "" ["documentation_uri"]=> string(56) "https://huggingface.co/TheBloke/falcon-40b-instruct-GGML" ["quantization_bits"]=> string(0) "" ["quantization_method"]=> string(4) "q8_0" ["quantization_description"]=> string(138) "Original llama.cpp quant method, 8-bit. Almost indistinguishable from float16. High resource use and slow. Not recommended for most users." ["context_size"]=> string(4) "2048" ["deafult_prompt"]=> string(0) "" ["size_gb"]=> string(5) "44.46" ["ram_gb"]=> string(5) "46.96" ["metric_avg"]=> string(4) "63.4" ["metric_arc"]=> string(4) "61.6" ["metric_hellaswag"]=> string(4) "84.3" ["metric_mmlu"]=> string(4) "55.4" ["metric_truthfulqa"]=> string(4) "52.5" }

        Update the model named "id" in the database with these values. Be sure to check if the field is set and if it's not, don't update it.
     */
    $SQL = "UPDATE models SET ";
    if(isset($_POST['owned_by'])){
        $SQL .= "owned_by = '".$_POST['owned_by']."', ";
    }
    if(isset($_POST['license'])){
        $SQL .= "license = '".$_POST['license']."', ";
    }
    if(isset($_POST['release_date'])){
        $SQL .= "release_date = '".$_POST['release_date']."', ";
    }
    if(isset($_POST['documentation_uri'])){
        $SQL .= "documentation_uri = '".$_POST['documentation_uri']."', ";
    }
    if(isset($_POST['quantization_bits'])){
        $SQL .= "quantization_bits = '".$_POST['quantization_bits']."', ";
    }
    if(isset($_POST['quantization_method'])){
        $SQL .= "quantization_method = '".$_POST['quantization_method']."', ";
    }
    if(isset($_POST['quantization_description'])){
        $SQL .= "quantization_description = '".$_POST['quantization_description']."', ";
    }
    if(isset($_POST['context_size'])){
        $SQL .= "context_size = '".$_POST['context_size']."', ";
    }
    if(isset($_POST['default_prompt'])){
        $SQL .= "default_prompt = '".$_POST['default_prompt']."', ";
    }
    if(isset($_POST['size_gb'])){
        $SQL .= "size_gb = '".$_POST['size_gb']."', ";
    }
    if(isset($_POST['ram_gb'])){
        $SQL .= "ram_gb = '".$_POST['ram_gb']."', ";
    }
    if(isset($_POST['ggml_build'])){
        $SQL .= "ggml_build = '".$_POST['ggml_build']."', ";
    }
    if(isset($_POST['metric_avg'])){
        $SQL .= "metric_avg = '".$_POST['metric_avg']."', ";
    }
    if(isset($_POST['metric_arc'])){
        $SQL .= "metric_arc = '".$_POST['metric_arc']."', ";
    }
    if(isset($_POST['metric_hellaswag'])){
        $SQL .= "metric_hellaswag = '".$_POST['metric_hellaswag']."', ";
    }
    if(isset($_POST['metric_mmlu'])){
        $SQL .= "metric_mmlu = '".$_POST['metric_mmlu']."', ";
    }
    if(isset($_POST['metric_truthfulqa'])){
        $SQL .= "metric_truthfulqa = '".$_POST['metric_truthfulqa']."', ";
    }
    $SQL = substr($SQL, 0, -2);
    $SQL .= " WHERE id = '".$_POST['modelName']."'";
    $db = new SQLite3($modelDirectory . "/models.db");
    $result = $db->exec($SQL);
    if($result===false){
        echo "Error updating model: ".$db->lastErrorMsg()."<br><br>If the database is read only, you may need to fix persmissions.";
    }else{
        echo "Model updated successfully. <a href='/update'>Return to the update page</a>";
    }
    $db->close();
    
}

function showUpdate(){
    ?><!DOCTYPE html>
    <html>
    
    <head>
        <title>ChadGPT: Incomplete Models</title>
        <!-- Include Bootstrap CSS -->
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    </head>
    
    <body class="container">
        <h1 class="mt-4"><a href="/">ChadGPT</a>: Incomplete Models</h1>
    
        <div id="modelsList" class="row">
            <!-- Cards will be populated here -->
        </div>
    
        <div id="formContainer" class="d-none">
            <h2 class="mt-4">Model Details Form</h2>
    
            <!-- Add the Google search link here -->
            <p><a href="#" id="googleSearchLink" target="_blank">Google Search</a></p>
            
            <p>
                <a href="/ggml" target="_blank">List of Available GGML Builds</a> - 
                <a href="/models" target="_blank">List of Available LLMs</a> - 
                <a id="thisModelLink" href="/models" target="_blank">Description of this model</a>
            </p>
    
            <form id="modelForm" action="/update" method="post">
                <div class="mb-3">
                    <label for="modelName" class="form-label">Model Name:</label>
                    <input type="text" class="form-control" id="modelNameField" name="modelName" readonly>
                </div>
                <div id="form_contents"><!-- Additional missing fields will be added here --></div>
                <input type="submit" class="btn btn-primary" id="submitBtn" value="Submit">
            </form>
        </div>
    
        <!-- Include Bootstrap JS and jQuery -->
        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
        <script src="js/page.completion.js"></script>
    </body>
    
    </html>
<?php
}

function showGGML($modelDirectory, $ggmlDirectory, $openLLMDirectrory){
    //return a json object listing all the binary files in the ggml directory
    $list_of_models = array();
    $list_of_models[] = 'llama';

    $models = scandir($ggmlDirectory.'/build/bin');
    foreach ($models as $model) {
        if ($model != "." && $model != "..") {
            //Check if the model is a binary file
            $list_of_models[] = 'ggml_'.$model;
        }
    }

    //return the list of models in JSON format
    echo json_encode($list_of_models);
}

function showDocumentCompletion(){
    ?><!DOCTYPE html>
    <html>
    
    <head>
        <title>ChadGPT</title>
        <!-- Include Bootstrap CSS -->
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    </head>
    
    <body class="container">
        <h1 class="mt-4"><a href="/">ChadGPT</a></h1>
        <p><i>Free and actually-open.</i></p>
    
        <div class="row">
            <div class="col-md-4">
                <label for="model" class="form-label">Select Model:</label>
                <select id="model" class="form-select"><option value="">Choose a model</option></select>
            </div><!--/col-md-4-->
            <div class="col-md-4">
                <label for="temperature" class="form-label">Select Temperature:</label>
                <input type="range" id="temperature" name="temperature" min="0" max="2" step="0.1" value="0.8" class="form-range">
                <span id="temperatureLabel">0.8</span>
            </div><!--/col-md-4-->
            <div class="col-md-4">
                <label for="tokens" class="form-label">Tokens to Return:</label>
                <input type="text" id="tokens" name="tokens" value="10" class="form-control">
            </div><!--/col-md-4-->
        </div><!--/row-->

        <div class="row">
            <div class="col-12">
                <div id="buildMessage"></div>
            </div><!--/col-12-->
        </div><!--/row-->

    
        <div class="row">
            <div class="col-12">
                <label for="inputText" class="form-label">Enter your prompt here:</label>
                <textarea id="inputText" rows="20" class="mb-4 form-control" autofocus></textarea>
                <button onclick="generateText()" class="btn btn-primary" id="generateBtn">Generate</button>
                <img src="img/spinner.gif" id="spinner" style="display: none; height: 36px;" alt="Loading...">
            </div><!--/col-12-->
        </div>
    
        <!-- Include Bootstrap JS and jQuery -->
        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
        <script src="/js/page.document-completion.js"></script>
    </body>
    
    </html>
<?php    
}


function showHomePage(){
?><!DOCTYPE html>
    <html>
    
    <head>
        <title>ChadGPT - Home</title>
        <!-- Include Bootstrap CSS -->
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    </head>
    
    <body class="container">
        <h1 class="mt-4"><a href="/">ChadGPT</a></h1>
        <p><i>Free and actually-open.</i></p>
        
        <div class="row">
            <div class="col-12">
                
                <h3><a href="/document-completion">Document Completion</a></h3>
                <p>Use any model for document completion.</p>

                <h3><a href="/chat">Chat</a></h3>
                <p>Use any model for chat completion.</p>

                <h3><a href="/update">Model Settings</a></h3>
                <p>Edit settings for models.</p>

            </div><!--/col-12-->
        </div>
    
        <!-- Include Bootstrap JS and jQuery -->
        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
        <script src="/js/page.document-completion.js"></script>
    </body>
    
    </html>
<?php
}