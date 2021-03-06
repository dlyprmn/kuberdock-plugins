var renderDefaults = function() {
    var selectedPackage = $('#packageId').val();
    $('#packageId').empty().append(jQuery('<option>', {text: 'Choose package'}));
    $('#kubeType').empty().append(jQuery('<option>', {text: 'Choose Kube Type'}));

    if(typeof(defaults) == 'undefined' || typeof(packagesKubes) == 'undefined') {
        return;
    }

    $.each(packagesKubes, function(k, v) {
        selectedPackage = selectedPackage ? selectedPackage : defaults.packageId;
        $('#packageId').append(jQuery('<option>', {value: v.id, text: v.name, selected: selectedPackage == v.id}));

        if(defaults.packageId == v.id){
            $('label[for="packageId"]').html('Default package <span class="grey">(' + v.name + ')</span>');
        }

        if(selectedPackage == v.id) {
            $.each(v.kubes, function(kKube, vKube) {
                var selectedKube = vKube.id == defaults.kubeType;
                $('#kubeType').append(jQuery('<option>', {
                    value: vKube.id, text: vKube.name, selected: selectedKube
                }));
                if(selectedKube) {
                    $('label[for="kubeType"]').html('Default Kube Type <span class="grey">(' + vKube.name + ')</span>');
                }
            });
        }
    });
};

var toggleSubmit = function (){
    var submitDisabled = isNaN($('#packageId').val()) || isNaN($('#kubeType').val());
    $('button[type="submit"].save-defaults').prop('disabled', submitDisabled);
};

$(document).on('change', '#packageId', function() {
    renderDefaults();
    toggleSubmit();
});

$(document).on('change', '#kubeType', function() {
    toggleSubmit();
});

$(document).ready(function() {
    renderDefaults();

    $('a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
        if (typeof(editor) !== 'undefined') {
            editor.refresh();
        }
    });

    if(location.hash) {
        $('a[href="' + location.hash + '"]').tab('show');
    } else if(typeof(activeTab) !== 'undefined') {
        $('a[href="#' + activeTab + '"]').tab('show');
    }

    $('a[data-toggle="tab"]').on('click', function (e) {
        location.hash = $(this).attr('href');
    });

    if (typeof(Storage) !== "undefined") {
        var messages = localStorage.getItem("flash");
        if (messages) {
            localStorage.setItem("flash", "");
            messages = $.parseJSON(messages);

            $.each(messages, function (key, value) {
                $('#admin-plugin').prepend('<div role="alert" class="alert alert-'+value+'">'+key+'</div>');
            });
        }
    }
});