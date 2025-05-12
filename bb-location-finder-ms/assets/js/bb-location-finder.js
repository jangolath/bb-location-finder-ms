// assets/js/bb-location-finder.js

(function($) {
    'use strict';
    
    // Initialize location autocomplete
    function initAutocomplete() {
        if (typeof google === 'undefined' || typeof google.maps === 'undefined') {
            return;
        }
        
        // Location fields in profile form
        var cityField = document.getElementById('bb_location_city');
        var stateField = document.getElementById('bb_location_state');
        var countryField = document.getElementById('bb_location_country');
        
        if (cityField) {
            var cityAutocomplete = new google.maps.places.Autocomplete(cityField, {
                types: ['(cities)']
            });
            
            cityAutocomplete.addListener('place_changed', function() {
                var place = cityAutocomplete.getPlace();
                
                if (!place.geometry) {
                    return;
                }
                
                // Get address components
                var addressComponents = place.address_components;
                var city = '';
                var state = '';
                var country = '';
                
                for (var i = 0; i < addressComponents.length; i++) {
                    var component = addressComponents[i];
                    var types = component.types;
                    
                    if (types.indexOf('locality') !== -1) {
                        city = component.long_name;
                    } else if (types.indexOf('administrative_area_level_1') !== -1) {
                        state = component.long_name;
                    } else if (types.indexOf('country') !== -1) {
                        country = component.long_name;
                    }
                }
                
                // Update fields
                $(cityField).val(city);
                if (stateField) $(stateField).val(state);
                if (countryField) $(countryField).val(country);
                
                // Update hidden coordinates
                $('#bb_location_lat').val(place.geometry.location.lat());
                $('#bb_location_lng').val(place.geometry.location.lng());
            });
        }
        
        // Search location field
        var searchField = document.getElementById('bb_search_location');
        
        if (searchField) {
            var searchAutocomplete = new google.maps.places.Autocomplete(searchField);
            
            searchAutocomplete.addListener('place_changed', function() {
                var place = searchAutocomplete.getPlace();
                
                if (!place.geometry) {
                    return;
                }
                
                // Auto-submit the form
                $('#bb-location-search-form').submit();
            });
        }
    }
    
    // Initialize on document ready
    $(document).ready(function() {
        // Initialize autocomplete
        if (typeof google !== 'undefined' && typeof google.maps !== 'undefined' && typeof google.maps.places !== 'undefined') {
            initAutocomplete();
        }
        
        // Handle location setter form submission
        $(document).on('submit', '#bb-location-setter', function(e) {
            e.preventDefault();
            
            var $form = $(this);
            var $message = $('#bb-location-message');
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'bb_location_update',
                    nonce: $form.find('[name="bb_location_nonce"]').val(),
                    city: $form.find('[name="bb_location_city"]').val(),
                    state: $form.find('[name="bb_location_state"]').val(),
                    country: $form.find('[name="bb_location_country"]').val(),
                    lat: $form.find('[name="bb_location_lat"]').val(),
                    lng: $form.find('[name="bb_location_lng"]').val(),
                    searchable: $form.find('[name="bb_location_searchable"]').is(':checked') ? 'yes' : 'no',
                    redirect: $form.find('[name="redirect"]').val()
                },
                beforeSend: function() {
                    $form.find('button').prop('disabled', true).text('Updating...');
                    $message.html('').hide();
                },
                success: function(response) {
                    $form.find('button').prop('disabled', false).text('Update Location');
                    
                    if (response.success) {
                        $message.html('<div class="success">' + response.data.message + '</div>').fadeIn();
                        
                        if (response.data.redirect) {
                            window.location.href = response.data.redirect;
                        }
                    } else {
                        $message.html('<div class="error">' + response.data.message + '</div>').fadeIn();
                    }
                },
                error: function() {
                    $form.find('button').prop('disabled', false).text('Update Location');
                    $message.html('<div class="error">An error occurred. Please try again.</div>').fadeIn();
                }
            });
        });
    });
    
})(jQuery);