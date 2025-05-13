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
                url: bbLocationFinderVars.ajaxurl,
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
        
        // Handle search form submission
        $(document).on('submit', '#bb-location-search-form', function(e) {
            e.preventDefault();
            
            var $form = $(this);
            var $results = $('#bb-location-results');
            
            // Show loading indicator
            $results.addClass('loading').html('<div class="loading-indicator">Searching...</div>');
            
            // Get form data
            var formData = {
                action: 'bb_location_search',
                nonce: $form.find('[name="search_nonce"]').val(),
                location: $form.find('[name="location"]').val(),
                radius: $form.find('[name="radius"]').val(),
                unit: $form.find('[name="unit"]').val()
            };
            
            // Make AJAX request
            $.ajax({
                url: bbLocationFinderVars.ajaxurl,
                type: 'POST',
                data: formData,
                success: function(response) {
                    $results.removeClass('loading');
                    
                    if (response.success) {
                        displaySearchResults(response.data);
                    } else {
                        $results.html('<div class="search-error">' + response.data.message + '</div>');
                    }
                },
                error: function() {
                    $results.removeClass('loading');
                    $results.html('<div class="search-error">' + bbLocationFinderVars.strings.search_error + '</div>');
                }
            });
        });
        
        function displaySearchResults(data) {
            var $results = $('#bb-location-results');
            var $resultCount = $('<div class="result-count"></div>');
            var $resultContainer = $('<div class="result-container"></div>');
            
            // Clear previous results
            $results.empty();
            
            // Add result count
            var countText = data.count + ' ' + 
                (data.count === 1 ? bbLocationFinderVars.strings.member : bbLocationFinderVars.strings.members) + 
                ' ' + bbLocationFinderVars.strings.found;
            $resultCount.text(countText);
            $results.append($resultCount);
            
            // Create map and user results containers
            var $map = $('<div id="bb-location-map" style="height: 400px;"></div>');
            var $userResults = $('<div id="bb-location-users" class="user-results"></div>');
            
            $resultContainer.append($map).append($userResults);
            $results.append($resultContainer);
            
            // Show no results message if needed
            if (data.count === 0) {
                $userResults.html('<div class="no-results">' + bbLocationFinderVars.strings.no_results + '</div>');
            } else {
                // Add user results
                $.each(data.users, function(index, user) {
                    var locationDisplay = user.location.join(', ');
                    var distanceText = user.distance + ' ' + (data.unit === 'mi' ? 'miles' : 'km') + ' away';
                    
                    var $userItem = $('<div class="user-item"></div>').attr('data-id', user.id);
                    var $avatar = $('<div class="user-avatar"><img src="' + user.avatar + '" alt=""></div>');
                    var $info = $('<div class="user-info"></div>');
                    
                    $info.append('<h4><a href="' + user.profile_url + '">' + user.name + '</a></h4>');
                    $info.append('<p class="user-location">' + locationDisplay + '</p>');
                    $info.append('<p class="user-distance">' + distanceText + '</p>');
                    
                    $userItem.append($avatar).append($info);
                    $userResults.append($userItem);
                });
            }
            
            // Initialize map
            if (typeof google !== 'undefined' && typeof google.maps !== 'undefined') {
                initMap(data);
            }
        }
        
        function initMap(data) {
            // Create map
            var map = new google.maps.Map(document.getElementById('bb-location-map'), {
                zoom: 10,
                center: {
                    lat: parseFloat(data.center.lat),
                    lng: parseFloat(data.center.lng)
                }
            });
            
            // Add center marker
            new google.maps.Marker({
                position: {
                    lat: parseFloat(data.center.lat),
                    lng: parseFloat(data.center.lng)
                },
                map: map,
                icon: {
                    path: google.maps.SymbolPath.CIRCLE,
                    scale: 10,
                    fillColor: '#4285F4',
                    fillOpacity: 0.8,
                    strokeColor: '#FFFFFF',
                    strokeWeight: 2
                },
                title: bbLocationFinderVars.strings.search_center
            });
            
            // Add user markers
            $.each(data.users, function(index, user) {
                var marker = new google.maps.Marker({
                    position: {
                        lat: parseFloat(user.lat),
                        lng: parseFloat(user.lng)
                    },
                    map: map,
                    title: user.name
                });
                
                // Add info window
                var infoWindow = new google.maps.InfoWindow({
                    content: '<div class="map-info-window">' +
                        '<h4>' + user.name + '</h4>' +
                        '<p>' + user.location.join(', ') + '</p>' +
                        '<p>' + user.distance + ' ' + (data.unit === 'mi' ? 'miles' : 'km') + ' away</p>' +
                        '<p><a href="' + user.profile_url + '">' + bbLocationFinderVars.strings.view_profile + '</a></p>' +
                        '</div>'
                });
                
                marker.addListener('click', function() {
                    infoWindow.open(map, marker);
                });
            });
        }
    });
    
    // Define global function
    window.bbLocationFinder = {
        initMap: function() {
            // This is a placeholder function that will be overridden when search results are displayed
        }
    };
    
})(jQuery);