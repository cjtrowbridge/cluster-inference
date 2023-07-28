// Function to populate the dropdown select with model options
function populateModelDropdown(models) {
    selectModel = $("#model");
    models.forEach((model) => {
        selectModel.append(new Option(model, model));
    });
}

// Function to update the label with the current slider value
function updateTemperatureLabel() {
    temperature = parseFloat($("#temperature").val()).toFixed(1);
    $("#temperatureLabel").text(temperature);
}

// Call the updateTemperatureLabel function when the slider value changes
$("#temperature").on("input", updateTemperatureLabel);

// Function to generate text based on selected model and temperature
async function generateText() {
    model = $("#model").val();
    if(model == ""){
        alert("Please select a model");
        return;
    }
    temperature = parseFloat($("#temperature").val());
    tokens = parseInt($("#tokens").val());
    context = parseInt($("#context").val());
    inputText = $("#inputText").val();

    // Disable the button and show the spinner
    $("#generateBtn").prop("disabled", true);
    $("#spinner").show();

    // Post a json object to the endpoint instead of sending the data as query parameters
    endpoint = "/completions";
    data = {
        model: model,
        temperature: temperature,
        max_tokens: tokens,
        prompt: inputText
    };
    //post the data as json
    response = await $.post(endpoint, JSON.stringify(data));
    
    //Check if response.choices exists
    if (response.choices == undefined) {
        // Append the generated text to the prompt
        //Find the place where the response contains the prompt
        var promptIndex = response.indexOf(inputText);
        //Append the text after the prompt to the prompt
        if(response == "" || response == undefined){
            showError("Received an empty response from the server. Please try again.");
        }else{
            $("#inputText").val(inputText + response.substring(promptIndex + inputText.length));
        }

    }else{
        // Append the generated text to the prompt
        $("#inputText").val(inputText + response.choices[0].text);
    }

    
    // Enable the button and hide the spinner after completion
    $("#generateBtn").prop("disabled", false);
    $("#spinner").hide();
}

// Fetch the models from the endpoint when the page loads
$(document).ready(function () {
    endpoint = "/models"; // Replace this with your actual API endpoint
    $.get(endpoint, function (data) {
        models = JSON.parse(data);
        populateModelDropdown(models);
    });

    // Set the text area focus by default
    $("#inputText").focus();

    $("#model").change(function () {
        //Check if this model already has a build selected so we can run it
        //Fetch the model details from the endpoint /models/model_name
        $.get("/models/" + $("#model").val(), function (modelData) {
            try {
                modelObj = JSON.parse(modelData);
                console.log(modelObj);
                console.log("Configured to run on: " + modelObj['ggml_build']);
                if (modelObj['ggml_build'] === null || modelObj['ggml_build'] === undefined || modelObj['ggml_build'] === "") {
                    console.log("Model has no build configuration. Ask the user to update the model settings.");
                    showError('The model '+modelObj['id']+' has not been configured. Please go to the <a href="/update">model settings</a> page and select the appropriate GGML/Llama build so it can run.');
                    $("#generateBtn").prop("disabled", true);
                } else {
                    //If the model does have a build selected then allow it to run
                    
                    $("#generateBtn").prop("disabled", false);
                }
            } catch (e) {
                console.log(e);
            }
        });
        
        $("#inputText").focus();
    });
});

function showError(message) {
    //If the model does not have a build selected, show a message to select a build
    errorDiv = '<div class="alert alert-danger mt-4" role="alert">'+message+'</div>';
    $("#buildMessage").append(errorDiv);
}