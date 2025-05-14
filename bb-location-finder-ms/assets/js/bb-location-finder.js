// assets/js/bb-location-finder.js

(function($) {
    'use strict';
    
    // Debug logging function
    function bbLogDebug(message, data) {
        if (typeof console !== 'undefined') {
            console.log('[BB Location] ' + message, data || '');
        }
    }
    
    // Initialize location autocomplete
    function initAutocomplete() {
        bbLogDebug('Initializing autocomplete');
        
        if (typeof google === 'undefined' || typeof google.maps === 'undefined' || typeof google.maps.places === 'undefined') {
            bbLogDebug('Google Maps Places API not loaded');
            return;
        }
        
        // Location fields in profile form
        var cityField = document.getElementById('bb_location_city');
        var stateField = document.getElementById('bb_location_state');
        var countryField = document.getElementById('bb_location_country');
        
        if (cityField) {
            bbLogDebug('Setting up city field autocomplete');
            var cityAutocomplete = new google.maps.places.Autocomplete(cityField, {
                types: ['(cities)']
            });
            
            cityAutocomplete.addListener('place_changed', function() {
                var place = cityAutocomplete.getPlace();
                
                if (!place.geometry) {
                    bbLogDebug('No place geometry', place);
                    return;
                }
                
                bbLogDebug('City place selected', place);
                
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
                
                bbLogDebug('Updated fields with place data', {
                    city: city,
                    state: state,
                    country: country,
                    lat: place.geometry.location.lat(),
                    lng: place.geometry.location.lng()
                });
            });
        }
        
        // Search location field
        var searchField = document.getElementById('bb_search_location');
        
        if (searchField) {
            bbLogDebug('Setting up search field autocomplete');
            var searchAutocomplete = new google.maps.places.Autocomplete(searchField, {
                types: ['(cities)']
            });
            
            // Add place_changed listener for search field too
            searchAutocomplete.addListener('place_changed', function() {
                var place = searchAutocomplete.getPlace();
                
                if (!place.geometry) {
                    bbLogDebug('No search place geometry', place);
                    return;
                }
                
                bbLogDebug('Search place selected', place.formatted_address);
            });
        }
    }
    
    // Initialize on document ready
    $(document).ready(function() {
        bbLogDebug('Document ready');
    
        // Check Google Maps API status with more detailed logging
        if (typeof google === 'undefined') {
            bbLogDebug('Google is not defined - will try to load dynamically');
            
            // If API key is available, try to load Google Maps dynamically
            if (bbLocationFinderVars.apiKey) {
                bbLogDebug('Loading Google Maps API dynamically with key length: ' + bbLocationFinderVars.apiKey.length);
                
                var script = document.createElement('script');
                script.src = 'https://maps.googleapis.com/maps/api/js?key=' + bbLocationFinderVars.apiKey + '&libraries=places&callback=initAutocompleteCallback';
                script.async = true;
                script.defer = true;
                script.onerror = function() {
                    bbLogDebug('Failed to load Google Maps API script');
                    console.error('Failed to load Google Maps API script');
                };
                
                // Define global callback
                window.initAutocompleteCallback = function() {
                    bbLogDebug('Google Maps loaded via callback');
                    initAutocomplete();
                };
                
                document.body.appendChild(script);
            } else {
                bbLogDebug('No API key available - cannot load Google Maps');
                console.error('No Google Maps API key provided');
            }
        } else if (typeof google.maps === 'undefined') {
            bbLogDebug('Google is defined but maps is not');
            console.error('Google object exists but maps property is undefined');
        } else if (typeof google.maps.places === 'undefined') {
            bbLogDebug('Google maps is defined but places is not');
            console.error('Google Maps loaded but Places API is not available');
        } else {
            bbLogDebug('Google Maps with Places API is loaded, initializing autocomplete');
            initAutocomplete();
        }
        
        // Check if API key is available
        bbLogDebug('API Key Length', bbLocationFinderVars.apiKey ? bbLocationFinderVars.apiKey.length : 0);
        bbLogDebug('Site URL', bbLocationFinderVars.siteUrl || 'Not available');
        
        // Check if Google Maps is available
        if (typeof google === 'undefined') {
            bbLogDebug('Google is not defined');
            
            // If API key is available, try to load Google Maps dynamically
            if (bbLocationFinderVars.apiKey) {
                bbLogDebug('Loading Google Maps API dynamically');
                
                var script = document.createElement('script');
                script.src = 'https://maps.googleapis.com/maps/api/js?key=' + bbLocationFinderVars.apiKey + '&libraries=places&callback=initAutocompleteCallback';
                script.async = true;
                script.defer = true;
                
                // Define global callback
                window.initAutocompleteCallback = function() {
                    bbLogDebug('Google Maps loaded via callback');
                    initAutocomplete();
                };
                
                document.body.appendChild(script);
            }
        } else if (typeof google.maps === 'undefined') {
            bbLogDebug('Google Maps is not defined');
        } else if (typeof google.maps.places === 'undefined') {
            bbLogDebug('Google Maps Places is not defined');
        } else {
            bbLogDebug('Google Maps with Places API is loaded');
            initAutocomplete();
        }
        
        // Handle location setter form submission
        $(document).on('submit', '#bb-location-setter', function(e) {
            e.preventDefault();
            
            var $form = $(this);
            var $message = $('#bb-location-message');
            
            bbLogDebug('Location setter form submitted', {
                city: $form.find('[name="bb_location_city"]').val(),
                state: $form.find('[name="bb_location_state"]').val(),
                country: $form.find('[name="bb_location_country"]').val()
            });
            
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
                        bbLogDebug('Location update success', response.data);
                        $message.html('<div class="success">' + response.data.message + '</div>').fadeIn();
                        
                        if (response.data.redirect) {
                            window.location.href = response.data.redirect;
                        }
                    } else {
                        bbLogDebug('Location update error', response.data);
                        $message.html('<div class="error">' + response.data.message + '</div>').fadeIn();
                    }
                },
                error: function(xhr, status, error) {
                    bbLogDebug('AJAX error in location update', {
                        status: status,
                        error: error
                    });
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
                unit: $form.find('[name="unit"]').val(),
                show_map: $form.find('[name="show_map"]').val() || 'yes',
                map_height: $form.find('[name="map_height"]').val() || '400px'
            };
            
            bbLogDebug('Search form submitted', formData);
            
            // Make AJAX request
            $.ajax({
                url: bbLocationFinderVars.ajaxurl,
                type: 'POST',
                data: formData,
                success: function(response) {
                    $results.removeClass('loading');
                    
                    if (response.success) {
                        bbLogDebug('Search success', {
                            count: response.data.count,
                            center: response.data.center,
                            showMap: response.data.show_map
                        });
                        displaySearchResults(response.data);
                    } else {
                        bbLogDebug('Search error', response.data);
                        $results.html('<div class="search-error">' + response.data.message + '</div>');
                    }
                },
                error: function(xhr, status, error) {
                    bbLogDebug('AJAX error in search', {
                        status: status,
                        error: error
                    });
                    $results.removeClass('loading');
                    $results.html('<div class="search-error">' + bbLocationFinderVars.strings.search_error + '</div>');
                }
            });
        });
        
        // Name search filter
        $(document).on('input', '#bb_name_search', function() {
            var searchValue = $(this).val().toLowerCase();
            
            bbLogDebug('Name search input', searchValue);
            
            if (window.allUsers) {
                // First filter by name
                if (searchValue.length === 0) {
                    window.filteredByName = window.allUsers.slice();
                } else {
                    window.filteredByName = window.allUsers.filter(function(user) {
                        return user.name.toLowerCase().indexOf(searchValue) !== -1;
                    });
                }
                
                // Then apply profile type filter if selected
                var selectedType = $('#bb_profile_type_filter').val();
                if (!selectedType || selectedType === '') {
                    window.filteredUsers = window.filteredByName.slice();
                } else {
                    window.filteredUsers = window.filteredByName.filter(function(user) {
                        return user.profile_type === selectedType;
                    });
                }
                
                // Reset to first page
                window.currentPage = 1;
                $('input[name="current_page"]').val(1);
                
                // Display filtered results
                displayUserResults(window.filteredUsers);
            }
        });
        
        // Profile type filter
        $(document).on('change', '#bb_profile_type_filter', function() {
            var selectedType = $(this).val();
            
            bbLogDebug('Profile type filter changed', selectedType);
            
            if (window.allUsers && window.filteredByName) {
                // First filter by name
                if ($('#bb_name_search').val().length === 0) {
                    window.filteredByName = window.allUsers.slice();
                } else {
                    var searchValue = $('#bb_name_search').val().toLowerCase();
                    window.filteredByName = window.allUsers.filter(function(user) {
                        return user.name.toLowerCase().indexOf(searchValue) !== -1;
                    });
                }
                
                // Then filter by profile type
                if (!selectedType || selectedType === '') {
                    window.filteredUsers = window.filteredByName.slice();
                } else {
                    window.filteredUsers = window.filteredByName.filter(function(user) {
                        return user.profile_type === selectedType;
                    });
                }
                
                // Reset to first page
                window.currentPage = 1;
                $('input[name="current_page"]').val(1);
                
                // Display filtered results
                displayUserResults(window.filteredUsers);
            }
        });
        
        // Pagination click handler
        $(document).on('click', '.page-number', function(e) {
            e.preventDefault();
            
            if (window.filteredUsers) {
                window.currentPage = parseInt($(this).data('page'));
                $('input[name="current_page"]').val(window.currentPage);
                
                bbLogDebug('Pagination clicked', window.currentPage);
                
                // Display current page
                displayUserResults(window.filteredUsers);
                
                // Scroll to results
                $('html, body').animate({
                    scrollTop: $('#bb-location-results').offset().top - 50
                }, 200);
            }
        });

         // Apply filters button handler
        $(document).on('click', '#bb-apply-filters', function() {
            bbLogDebug('Apply filters button clicked');
            
            if (window.allUsers) {
                applyFilters();
            }
        });
        
        // Helper function to apply all filters
        function applyFilters() {
            if (!window.allUsers) {
                bbLogDebug('No users to filter');
                return;
            }
            
            // Get filter values
            var nameFilter = $('#bb_name_search').val().toLowerCase();
            var profileTypeFilter = $('#bb_profile_type_filter').val();
            
            bbLogDebug('Applying filters', {
                nameFilter: nameFilter,
                profileTypeFilter: profileTypeFilter
            });
            
            // Start with all users
            var filtered = window.allUsers.slice();
            
            // Apply name filter if specified
            if (nameFilter.length > 0) {
                filtered = filtered.filter(function(user) {
                    return user.name.toLowerCase().indexOf(nameFilter) !== -1;
                });
            }
            
            // Apply profile type filter if specified
            if (profileTypeFilter && profileTypeFilter !== '') {
                filtered = filtered.filter(function(user) {
                    return user.profile_type === profileTypeFilter;
                });
            }
            
            // Update filtered users
            window.filteredUsers = filtered;
            
            // Reset to first page
            window.currentPage = 1;
            $('input[name="current_page"]').val(1);
            
            // Display filtered results
            displayUserResults(window.filteredUsers);
            
            // Update result count text
            var countText = window.filteredUsers.length + ' ' + 
                (window.filteredUsers.length === 1 ? bbLocationFinderVars.strings.member : bbLocationFinderVars.strings.members) + 
                ' ' + bbLocationFinderVars.strings.found;
            $('#bb-location-results .result-count').text(countText);
        }
    });
    
    // Display user results with pagination
    function displayUserResults(users) {
        if (!users) {
            bbLogDebug('No users to display');
            return;
        }
        
        bbLogDebug('Displaying user results', {
            count: users.length,
            page: window.currentPage,
            profileTypeSample: users.length > 0 ? {
                name: users[0].name,
                profile_type: users[0].profile_type,
                profile_type_label: users[0].profile_type_label
            } : null
        });
        
        var $userResults = $('#bb-location-users');
        $userResults.empty();
        
        // Show no results message if needed
        if (users.length === 0) {
            $userResults.html('<div class="no-results">' + bbLocationFinderVars.strings.no_results + '</div>');
            $('#bb-location-pagination').hide();
            return;
        }
        
        // Calculate pagination
        var resultsPerPage = parseInt($('input[name="results_per_page"]').val()) || 10;
        var currentPage = window.currentPage || 1;
        var totalPages = Math.ceil(users.length / resultsPerPage);
        var startIndex = (currentPage - 1) * resultsPerPage;
        var endIndex = Math.min(startIndex + resultsPerPage, users.length);
        var pageUsers = users.slice(startIndex, endIndex);
        
         // Add user results
        $.each(pageUsers, function(index, user) {
            var locationDisplay = user.location.join(', ');
            var distanceText = user.distance + ' ' + (user.unit === 'mi' ? 'miles' : 'km') + ' away';
            
            var $userItem = $('<div class="user-item"></div>').attr('data-id', user.id);
            if (user.profile_type) {
                $userItem.attr('data-profile-type', user.profile_type);
            }
            
            var $avatar = $('<div class="user-avatar"><img src="' + user.avatar + '" alt=""></div>');
            var $info = $('<div class="user-info"></div>');
            
            $info.append('<h4><a href="' + user.profile_url + '">' + user.name + '</a></h4>');
            
            // Add profile type badge if available
            if (user.profile_type_label) {
                $info.append('<span class="profile-type-badge">' + user.profile_type_label + '</span>');
            }
            
            $info.append('<p class="user-location">' + locationDisplay + '</p>');
            $info.append('<p class="user-distance">' + distanceText + '</p>');
            
            $userItem.append($avatar).append($info);
            $userResults.append($userItem);
        });
        
        // After all items are added, ensure proper wrapping with clearfix
        $userResults.append('<div style="clear:both;"></div>');
        
        // Update pagination
        updatePagination(totalPages, currentPage);
    }
    
    // Update pagination UI
    function updatePagination(totalPages, currentPage) {
        var $pagination = $('#bb-location-pagination');
        $pagination.empty();
        
        if (totalPages <= 1) {
            $pagination.hide();
            return;
        }
        
        $pagination.show();
        
        // Previous button
        if (currentPage > 1) {
            $pagination.append('<a href="#" class="page-number prev" data-page="' + (currentPage - 1) + '">' + '&laquo; ' + 'Previous' + '</a>');
        }
        
        // Page numbers
        var startPage = Math.max(1, currentPage - 2);
        var endPage = Math.min(totalPages, startPage + 4);
        
        if (endPage - startPage < 4 && startPage > 1) {
            startPage = Math.max(1, endPage - 4);
        }
        
        for (var i = startPage; i <= endPage; i++) {
            var $pageLink = $('<a href="#" class="page-number" data-page="' + i + '">' + i + '</a>');
            
            if (i === currentPage) {
                $pageLink.addClass('current');
            }
            
            $pagination.append($pageLink);
        }
        
        // Next button
        if (currentPage < totalPages) {
            $pagination.append('<a href="#" class="page-number next" data-page="' + (currentPage + 1) + '">' + 'Next' + ' &raquo;</a>');
        }
    }
    
    // Function to handle displaying search results
    function displaySearchResults(data) {
        var $results = $('#bb-location-results');
        var $resultCount = $('<div class="result-count"></div>');
        
        // Store data for filtering/pagination
        window.allUsers = data.users || [];
        window.filteredUsers = data.users ? data.users.slice() : [];
        window.currentPage = 1;
        
        bbLogDebug('Search results received', {
            totalUsers: window.allUsers.length,
            profileTypes: window.allUsers.map(function(u) { 
                return u.profile_type;
            }).filter(function(value, index, self) { 
                return self.indexOf(value) === index;
            })
        });
        
        // Clear previous results
        $results.empty();
        
        // Add result count
        var countText = data.count + ' ' + 
            (data.count === 1 ? bbLocationFinderVars.strings.member : bbLocationFinderVars.strings.members) + 
            ' ' + bbLocationFinderVars.strings.found;
        $resultCount.text(countText);
        $results.append($resultCount);
        
        // Create result container
        var $resultContainer = $('<div class="result-container"></div>');
        $results.append($resultContainer);
        
        // Only add map if show_map is yes
        if (data.show_map === 'yes') {
            var $map = $('<div id="bb-location-map" style="height: ' + data.map_height + ';"></div>');
            $resultContainer.append($map);
            
            // Initialize map
            if (typeof google !== 'undefined' && typeof google.maps !== 'undefined') {
                initMap(data);
            }
        }
        
        // Add users container
        var $userResults = $('<div id="bb-location-users" class="user-results"></div>');
        $resultContainer.append($userResults);
        
        // Add pagination container if it doesn't exist
        if ($('#bb-location-pagination').length === 0) {
            $results.append('<div id="bb-location-pagination" class="location-pagination"></div>');
        }
        
        // Show filters if we have results
        if (data.count > 0) {
            $('.filter-container').show();
        } else {
            $('.filter-container').hide();
        }
        
        // Display users with pagination
        displayUserResults(window.filteredUsers);
    }
    
    // Initialize map with markers
    function initMap(data) {
        bbLogDebug('Initializing map', {
            centerLat: data.center.lat,
            centerLng: data.center.lng,
            userCount: data.users ? data.users.length : 0
        });
        
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
        if (data.users && data.users.length > 0) {
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
                        (user.profile_type_label ? '<span class="profile-type-badge">' + user.profile_type_label + '</span><br>' : '') +
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
    }
    
    // Define global function
    window.bbLocationFinder = {
        initMap: function() {
            // This is a placeholder function that will be overridden when search results are displayed
        }
    };
    
})(jQuery);