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
        $("#inputText").val(inputText + response.substring(promptIndex + inputText.length));

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
});