// Function to show an error message
function showError(message) {
    errorDiv = '<div class="alert alert-danger mt-4" role="alert">' + message + '</div>';
    $("#modelsList").append(errorDiv);
}

// Function to show the form and hide the list of cards on clicking a card
function showForm(model) {
    window.model = model;
    $("#modelsList").hide();
    $("#formContainer").removeClass("d-none");

    $("#thisModelLink").attr("href", "/models/" + model);
    $("#thisModelLink").text("Description of " + model);

    // Set the model name at the top of the form and make it non-editable
    $("#modelNameField").val(model);

    form = $("#form_contents");
    form.empty();

    // Fetch model details from the endpoint
    modelEndpoint = "/models/" + model;
    $.get(modelEndpoint, function (modelData) {
        try {
            modelObj = JSON.parse(modelData);
            for (key in modelObj) {
                if (modelObj.hasOwnProperty(key)) {
                    fieldLabel = key.charAt(0).toUpperCase() + key.slice(1).replace(/_/g, " ");
                    fieldValue = modelObj[key];
                    if (fieldValue === null) {
                        fieldValue = "";
                    }
                    if (key === "ggml_build") {
                        // For the "ggml_build" field, fetch options from the /ggml endpoint and create a dropdown
                        model = modelObj['id'];
                        dropdownLabel = fieldLabel.charAt(0).toUpperCase() + fieldLabel.slice(1).replace(/_/g, " ");
                        dropdownField = '<div class="mb-3">' +
                            '<label for="' + key + '" class="form-label">' + dropdownLabel + '</label>' +
                            '<select class="form-select" id="ggml_build_select" name="' + key + '" data-value="' + modelObj[key] + '" data-model="' + model + '">' +
                            '</select>' +
                            '</div>';
                        $("#form_contents").append(dropdownField);
                        // Fetch ggml options from the server
                        $.get("/ggml", function (ggmlOptions) {
                            try {
                                parsedOptions = JSON.parse(ggmlOptions);
                                model = $("#ggml_build_select").attr("data-model");
                                value = $("#ggml_build_select").attr("data-value");

                                // Populate dropdown options
                                for (key in parsedOptions) {
                                    option = parsedOptions[key];
                                    isSelected = '';
                                    if (value == option) {
                                        isSelected = 'selected="selected"';
                                    } else if (model.includes(option)) {
                                        isSelected = 'selected';
                                    }
                                    console.log("Adding option: " + option + " with isSelected: " + isSelected);
                                    $("#ggml_build_select").append('<option value="' + option + '" ' + isSelected + '>' + option + '</option>');
                                }
                            } catch (error) {
                                showError("Failed to parse ggml options.");
                            }
                        }).fail(function () {
                            showError("Failed to fetch ggml options from the server.");
                        });
                    } else if(key === "id"){
                        //do nothing
                        
                    } else {
                        // For other fields, create regular input fields
                        inputField =
                            '<div class="mb-3">' +
                            '<label for="' + key + '" class="form-label">' + fieldLabel + '</label>' +
                            '<input type="text" class="form-control" id="' + key + '" name="' + key + '" value="' + fieldValue + '">' +
                            '</div>';
                        form.append(inputField);
                    }
                }
            }
        } catch (error) {
            showError("Failed to fetch details for model: " + model);
        }
    }).fail(function () {
        showError("Failed to fetch details for model: " + model);
    });

    // Update the Google search link with the model name
    googleSearchLink = $("#googleSearchLink");
    googleSearchURL = "https://www.google.com/search?q=" + encodeURIComponent(model);
    googleSearchLink.attr("href", googleSearchURL);
}

// Function to fetch model details and populate the cards
function getModelDetails(model, missingFields) {
    modelEndpoint = "/models/" + model;
    $.get(modelEndpoint, function (modelData) {
        // Check for missing or blank fields and add to missingFields array
        modelObj = JSON.parse(modelData);
        for (key in modelObj) {
            if (modelObj.hasOwnProperty(key)) {
                if (modelObj[key] === null || modelObj[key] === "") {
                    missingFields.push(key);
                }
            }
        }

        // Create the Bootstrap card for the model
        card =
            '<div class="col-md-4 mb-4">' +
            '<div class="card" onclick="showForm(\'' + model + '\');">' +
            '<div class="card-body">' +
            '<h5 class="card-title">' + model + '</h5>' +
            '</div>' +
            '</div>' +
            '</div>';

        $("#modelsList").append(card);
    }).fail(function () {
        showError("Failed to fetch details for model: " + model);
    });
}

$(document).ready(function () {
    endpoint = "/models"; // Replace this with your actual API endpoint

    

    // Fetch the models from the endpoint
    $.get(endpoint, function (data) {
        try {
            models = JSON.parse(data);
            modelsList = $("#modelsList");

            // Iterate through the list of models and get details for each model
            models.forEach((model) => {
                getModelDetails(model, []);
            });

        } catch (error) {
            showError("Failed to load models data.");
        }
    }).fail(function () {
        showError("Failed to fetch models data from the server.");
    });
});
